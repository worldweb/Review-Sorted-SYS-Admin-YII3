<?php

declare(strict_types=1);

namespace App\Domain\Review\Sync;

use App\Domain\SocialConnection\ServiceConfig;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * FacebookReviewSyncClient.
 * Fetches page reviews and recommendations from Meta Graph API v25.0.
 * Replicates the exact request endpoints, fields, and response parsing of legacy FacebookResolver.
 */
final class FacebookReviewSyncClient implements FacebookReviewSyncClientInterface
{
    private const GRAPH_API_VERSION = 'v25.0';
    private const GRAPH_API_BASE = 'https://graph.facebook.com/';

    private ClientInterface $httpClient;
    private LoggerInterface $logger;

    public function __construct(
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->httpClient = $httpClient ?? new Client(['timeout' => 25.0, 'connect_timeout' => 10.0]);
        $this->logger = $logger ?? new NullLogger();
    }

    public function fetchReviews(ServiceConfig|array $config, int $maxPages = 2): array
    {
        $configId = $config instanceof ServiceConfig ? $config->getId() : (int) ($config['id'] ?? 0);
        $pageToken = $config instanceof ServiceConfig ? $config->getPageToken() : (string) ($config['page_token'] ?? '');
        $pageId = $config instanceof ServiceConfig ? $config->getPageId() : (string) ($config['page_id'] ?? '');
        $label = $config instanceof ServiceConfig ? $config->getLabel() : (string) ($config['label'] ?? "Page #{$pageId}");

        if (trim($pageToken) === '') {
            $msg = "Facebook Page {$label} has no access token configured. Please connect Facebook in Settings.";
            $this->logger->warning("[FacebookReviewSync] ServiceConfig #{$configId} ({$label}): {$msg}");
            throw new ReviewSyncException($msg);
        }

        if (trim($pageId) === '') {
            $msg = "Facebook Page {$label} has no page_id configured. Please reconnect Facebook in Settings.";
            $this->logger->warning("[FacebookReviewSync] ServiceConfig #{$configId} ({$label}): {$msg}");
            throw new ReviewSyncException($msg);
        }

        $endpoint = self::GRAPH_API_BASE . self::GRAPH_API_VERSION . '/' . rawurlencode($pageId) . '/ratings';
        $reviews = [];
        $nextUrl = null;
        $pageCounter = 0;

        do {
            $pageCounter++;
            try {
                if ($nextUrl !== null && $nextUrl !== '') {
                    $response = $this->httpClient->request('GET', $nextUrl);
                } else {
                    $response = $this->httpClient->request('GET', $endpoint, [
                        'headers' => [
                            'Accept' => 'application/json',
                        ],
                        'query' => [
                            'fields' => 'reviewer,open_graph_story{id,data,start_time,message,type,comments{id,message,from,created_time,attachment}}',
                            'limit' => 100,
                            'access_token' => $pageToken,
                        ],
                    ]);
                }

                $statusCode = $response->getStatusCode();
                if ($statusCode !== 200) {
                    $msg = "Facebook Graph API returned HTTP status {$statusCode} for {$label} on page {$pageCounter}.";
                    $this->logger->warning("[FacebookReviewSync] {$msg}");
                    throw new ReviewSyncException($msg);
                }

                $body = json_decode((string) $response->getBody(), true);
                if (!is_array($body)) {
                    $msg = "Facebook Graph API returned non-JSON response for {$label} on page {$pageCounter}.";
                    $this->logger->warning("[FacebookReviewSync] {$msg}");
                    throw new ReviewSyncException($msg);
                }

                $rawItems = (array) ($body['data'] ?? []);
                foreach ($rawItems as $item) {
                    $parsed = $this->parseFacebookRating($item, $pageId);
                    if ($parsed !== null) {
                        $reviews[] = $parsed;
                    }
                }

                $nextUrl = !empty($body['paging']['next']) ? (string) $body['paging']['next'] : null;

                // Safety guard against exceeding requested pagination limit
                if ($pageCounter >= $maxPages) {
                    if ($nextUrl !== null) {
                        $this->logger->info("[FacebookReviewSync] Reached pagination limit ({$maxPages} page(s)) for {$label}. Additional historical reviews remain available.");
                    }
                    break;
                }
            } catch (RequestException $e) {
                $errMsg = $e->getMessage();
                $response = method_exists($e, 'getResponse') ? $e->getResponse() : null;
                $statusCode = $response !== null ? $response->getStatusCode() : 0;
                $fbCode = null;
                $fbType = null;
                if ($response !== null) {
                    $errJson = json_decode((string) $response->getBody(), true);
                    if (is_array($errJson) && isset($errJson['error'])) {
                        $fbErr = $errJson['error'];
                        $errMsg = (string) ($fbErr['message'] ?? $errMsg);
                        $fbCode = isset($fbErr['code']) ? (int) $fbErr['code'] : null;
                        $fbType = isset($fbErr['type']) ? (string) $fbErr['type'] : null;
                    }
                }

                if ($fbCode === 190 || $fbType === 'OAuthException' || $statusCode === 401 || $statusCode === 403) {
                    $codeStr = $fbCode !== null ? " (Error {$fbCode}: {$fbType})" : " (HTTP {$statusCode})";
                    $msg = "Facebook session expired or authorization revoked{$codeStr} for {$label}: {$errMsg}. Please reconnect Facebook in Settings.";
                } else {
                    $msg = "Facebook Graph API request failed for {$label} on page {$pageCounter}: {$errMsg}";
                }
                $this->logger->error("[FacebookReviewSync] {$msg}");
                throw new ReviewSyncException($msg, $e->getCode(), $e);
            } catch (ReviewSyncException $e) {
                throw $e;
            } catch (Throwable $e) {
                $msg = "Unexpected error fetching Facebook reviews for {$label}: " . $e->getMessage();
                $this->logger->error("[FacebookReviewSync] {$msg}");
                throw new ReviewSyncException($msg, 0, $e);
            }
        } while ($nextUrl !== null && $nextUrl !== '');

        $this->logger->info(sprintf(
            '[FacebookReviewSync] Fetched %d review(s) across %d page(s) for %s.',
            count($reviews),
            $pageCounter,
            $label
        ));

        return $reviews;
    }

    /**
     * Parses a single Facebook Graph API rating/recommendation item into standard domain structure.
     *
     * @param array<string, mixed> $item
     * @return array<string, mixed>|null
     */
    private function parseFacebookRating(array $item, string $pageId): ?array
    {
        $story = (array) ($item['open_graph_story'] ?? []);
        $storyId = (string) ($story['id'] ?? '');

        if ($storyId === '') {
            return null;
        }

        $data = (array) ($story['data'] ?? []);
        $reviewer = (array) ($item['reviewer'] ?? []);

        $customerName = (string) ($reviewer['name'] ?? 'Facebook User');
        $customerId = (string) ($reviewer['id'] ?? ('FB_' . substr(md5($storyId), 0, 12)));

        $recType = strtolower((string) ($data['recommendation_type'] ?? 'positive'));
        $rating = ($recType === 'positive') ? 5.0 : 1.0;
        $recommend = ($recType === 'positive') ? 'yes' : 'no';

        $reviewText = (string) ($data['review_text'] ?? ($story['message'] ?? '-'));
        if (trim($reviewText) === '') {
            $reviewText = '-';
        }

        $createTime = !empty($story['start_time'])
            ? date('Y-m-d H:i:s', strtotime((string) $story['start_time']))
            : date('Y-m-d H:i:s');

        $seller = !empty($data['seller']) ? $data['seller'] : ['page_id' => $pageId];
        $reviewExtra = json_encode($seller, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $parsed = [
            'obj_id' => $storyId,
            'customer_id' => $customerId,
            'customer_name' => $customerName,
            'customer_email' => null,
            'customer_phone' => null,
            'customer_state' => null,
            'customer_region' => null,
            'review_rate' => $rating,
            'review_text' => $reviewText,
            'review_recommend' => $recommend,
            'review_service' => 'Facebook',
            'review_date' => $createTime,
            'review_extra' => $reviewExtra,
            'type' => 'REVIEW',
            'is_google' => false,
            'comments' => [],
        ];

        // Parse comments/replies on the recommendation post
        if (!empty($story['comments']['data']) && is_array($story['comments']['data'])) {
            foreach ($story['comments']['data'] as $comment) {
                if (!is_array($comment) || empty($comment['id'])) {
                    continue;
                }

                $commentId = (string) $comment['id'];
                $commentText = (string) ($comment['message'] ?? '');
                $from = (array) ($comment['from'] ?? []);
                $fromId = isset($from['id']) ? (string) $from['id'] : null;
                $fromName = isset($from['name']) ? (string) $from['name'] : null;
                $commentDate = !empty($comment['created_time'])
                    ? date('Y-m-d H:i:s', strtotime((string) $comment['created_time']))
                    : $createTime;

                $parsed['comments'][] = [
                    'obj_id' => $commentId,
                    'customer_id' => $fromId,
                    'customer_name' => $fromName,
                    'reply_text' => $commentText !== '' ? $commentText : '[attachment]',
                    'reply_date' => $commentDate,
                    'reply_extra' => json_encode($comment, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        return $parsed;
    }
}

