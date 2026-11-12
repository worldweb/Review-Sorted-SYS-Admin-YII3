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

    public function fetchReviews(ServiceConfig|array $config): array
    {
        $configId = $config instanceof ServiceConfig ? $config->getId() : (int) ($config['id'] ?? 0);
        $pageToken = $config instanceof ServiceConfig ? $config->getPageToken() : (string) ($config['page_token'] ?? '');
        $pageId = $config instanceof ServiceConfig ? $config->getPageId() : (string) ($config['page_id'] ?? '');
        $label = $config instanceof ServiceConfig ? $config->getLabel() : (string) ($config['label'] ?? "Page #{$pageId}");

        if (trim($pageToken) === '') {
            $this->logger->warning("[FacebookReviewSync] ServiceConfig #{$configId} ({$label}) has no page_token.");
            return [];
        }

        if (trim($pageId) === '') {
            $this->logger->warning("[FacebookReviewSync] ServiceConfig #{$configId} ({$label}) has no page_id.");
            return [];
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
                    $this->logger->warning("[FacebookReviewSync] Facebook Graph API returned status {$statusCode} for {$label} on page {$pageCounter}.");
                    break;
                }

                $body = json_decode((string) $response->getBody(), true);
                if (!is_array($body)) {
                    break;
                }

                $rawItems = (array) ($body['data'] ?? []);
                foreach ($rawItems as $item) {
                    $parsed = $this->parseFacebookRating($item, $pageId);
                    if ($parsed !== null) {
                        $reviews[] = $parsed;
                    }
                }

                $nextUrl = !empty($body['paging']['next']) ? (string) $body['paging']['next'] : null;

                // Safety guard against infinite pagination loops
                if ($pageCounter >= 50) {
                    $this->logger->warning("[FacebookReviewSync] Reached maximum pagination limit (50 pages) for {$label}.");
                    break;
                }
            } catch (RequestException $e) {
                $errMsg = $e->getMessage();
                if ($e->hasResponse()) {
                    $res = $e->getResponse();
                    $errJson = json_decode((string) ($res ? $res->getBody() : ''), true);
                    if (is_array($errJson) && isset($errJson['error']['message'])) {
                        $errMsg = (string) $errJson['error']['message'];
                    }
                }
                $this->logger->error("[FacebookReviewSync] Request error fetching reviews for {$label}: {$errMsg}");
                break;
            } catch (Throwable $e) {
                $this->logger->error("[FacebookReviewSync] Unexpected error fetching reviews for {$label}: " . $e->getMessage());
                break;
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

