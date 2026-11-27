<?php

declare(strict_types=1);

namespace App\Domain\Review\Sync;

use App\Domain\Review\Review;
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
            throw new ReviewSyncException("Facebook Page {$label} has no access token configured. Please connect Facebook in Settings.");
        }

        if (trim($pageId) === '') {
            throw new ReviewSyncException("Facebook Page {$label} has no page_id configured. Please reconnect Facebook in Settings.");
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
                    throw new ReviewSyncException("Facebook Graph API returned HTTP status {$statusCode} for {$label} on page {$pageCounter}.");
                }

                $body = json_decode((string) $response->getBody(), true);
                if (!is_array($body)) {
                    throw new ReviewSyncException("Facebook Graph API returned non-JSON response for {$label} on page {$pageCounter}.");
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
                        $this->logger->debug("[FacebookReviewSync] Reached pagination limit ({$maxPages} page(s)) for {$label}. Additional historical reviews remain available.");
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
                throw new ReviewSyncException($msg, $e->getCode(), $e);
            } catch (ReviewSyncException $e) {
                throw $e;
            } catch (Throwable $e) {
                throw new ReviewSyncException("Unexpected error fetching Facebook reviews for {$label}: " . $e->getMessage(), 0, $e);
            }
        } while ($nextUrl !== null && $nextUrl !== '');

        $this->logger->debug(sprintf(
            '[FacebookReviewSync] Fetched %d review(s) across %d page(s) for %s.',
            count($reviews),
            $pageCounter,
            $label
        ));

        return $reviews;
    }

    public function fetchPageMentions(ServiceConfig|array $config, ?string $lastObjId = null, int $maxPages = 5): array
    {
        [$pageToken, $pageId, $label] = $this->extractPageCredentials($config);
        $endpoint = self::GRAPH_API_BASE . self::GRAPH_API_VERSION . '/' . rawurlencode($pageId) . '/tagged';
        $items = $this->fetchPaginatedGraphItems(
            endpoint: $endpoint,
            query: [
                'fields' => 'created_time,id,message,permalink_url,parent_id,from,target',
                'limit' => 100,
                'access_token' => $pageToken,
            ],
            label: $label,
            lastObjId: $lastObjId,
            maxPages: $maxPages,
        );

        $mentions = [];
        foreach ($items as $item) {
            $id = trim((string) ($item['id'] ?? ''));
            if ($id === '') {
                continue;
            }
            $from = is_array($item['from'] ?? null) ? $item['from'] : [];
            $ts = !empty($item['created_time']) ? strtotime((string) $item['created_time']) : false;

            $mentions[] = [
                'obj_id' => $id,
                'customer_id' => (string) ($from['id'] ?? '-'),
                'customer_name' => (string) ($from['name'] ?? '-'),
                'review_rate' => null,
                'review_text' => trim((string) ($item['message'] ?? '-')) ?: '-',
                'review_date' => date('Y-m-d H:i:s', $ts !== false ? $ts : time()),
                'review_extra' => (string) json_encode([
                    'url' => (string) ($item['permalink_url'] ?? ''),
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'review_service' => 'Facebook',
                'type' => Review::TYPE_MENTION,
            ];
        }

        return $mentions;
    }

    public function fetchInstagramMentions(
        string $instaObjId,
        string $pageToken,
        string $label,
        ?string $lastObjId = null,
        int $maxPages = 5,
    ): array {
        if (trim($pageToken) === '' || trim($instaObjId) === '') {
            throw new ReviewSyncException("Instagram channel {$label} is missing token or object ID.");
        }

        $endpoint = self::GRAPH_API_BASE . self::GRAPH_API_VERSION . '/' . rawurlencode($instaObjId) . '/tags';
        $items = $this->fetchPaginatedGraphItems(
            endpoint: $endpoint,
            query: [
                'fields' => 'id,caption,permalink,username,timestamp,media_url',
                'limit' => 100,
                'access_token' => $pageToken,
            ],
            label: $label,
            lastObjId: $lastObjId,
            maxPages: $maxPages,
        );

        $mentions = [];
        foreach ($items as $item) {
            $id = trim((string) ($item['id'] ?? ''));
            if ($id === '') {
                continue;
            }
            $ts = !empty($item['timestamp']) ? strtotime((string) $item['timestamp']) : false;

            $mentions[] = [
                'obj_id' => $id,
                'customer_id' => '-',
                'customer_name' => (string) ($item['username'] ?? '-'),
                'review_rate' => null,
                'review_text' => trim((string) ($item['caption'] ?? '-')) ?: '-',
                'review_date' => date('Y-m-d H:i:s', $ts !== false ? $ts : time()),
                'review_extra' => (string) json_encode([
                    'url' => (string) ($item['permalink'] ?? ''),
                    'media' => (string) ($item['media_url'] ?? ''),
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'review_service' => 'Instagram',
                'type' => Review::TYPE_MENTION,
            ];
        }

        return $mentions;
    }

    public function fetchPostReplies(ServiceConfig|array $config, int $maxPages = 2): array
    {
        [$pageToken, $pageId, $label] = $this->extractPageCredentials($config);
        $feedEndpoint = self::GRAPH_API_BASE . self::GRAPH_API_VERSION . '/' . rawurlencode($pageId) . '/feed';
        $posts = $this->fetchPaginatedGraphItems(
            endpoint: $feedEndpoint,
            query: [
                'fields' => 'id,message,permalink_url,status_type,created_time,story',
                'limit' => 25,
                'access_token' => $pageToken,
            ],
            label: $label,
            lastObjId: null,
            maxPages: $maxPages,
        );

        $replies = [];
        foreach ($posts as $postData) {
            $postId = trim((string) ($postData['id'] ?? ''));
            if ($postId === '') {
                continue;
            }

            $commentsEndpoint = self::GRAPH_API_BASE . self::GRAPH_API_VERSION . '/' . rawurlencode($postId) . '/comments';
            $commentsBody = $this->requestGraphJson(
                url: $commentsEndpoint,
                query: [
                    'fields' => 'id,message,from,created_time,attachment,like_count',
                    'limit' => 100,
                    'access_token' => $pageToken,
                ],
                label: $label,
                pageCounter: 1,
            );

            foreach ((array) ($commentsBody['data'] ?? []) as $replyData) {
                if (!is_array($replyData)) {
                    continue;
                }
                $replyId = trim((string) ($replyData['id'] ?? ''));
                if ($replyId === '') {
                    continue;
                }
                $from = is_array($replyData['from'] ?? null) ? $replyData['from'] : [];
                $ts = !empty($replyData['created_time']) ? strtotime((string) $replyData['created_time']) : false;

                $replies[] = [
                    'obj_id' => $replyId,
                    'customer_id' => (string) ($from['id'] ?? '-'),
                    'customer_name' => (string) ($from['name'] ?? 'Facebook User'),
                    'review_rate' => null,
                    'review_text' => trim((string) ($replyData['message'] ?? '-')) ?: '-',
                    'review_date' => date('Y-m-d H:i:s', $ts !== false ? $ts : time()),
                    'review_extra' => (string) json_encode([
                        'post' => $postData,
                        'url' => (string) ($postData['permalink_url'] ?? ''),
                        'likes' => (int) ($replyData['like_count'] ?? 0),
                        'attachment' => $replyData['attachment'] ?? null,
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'review_service' => 'Facebook',
                    'type' => Review::TYPE_POST_REPLY,
                ];
            }
        }

        return $replies;
    }

    /**
     * @param ServiceConfig|array<string, mixed> $config
     * @return array{0: string, 1: string, 2: string}
     */
    private function extractPageCredentials(ServiceConfig|array $config): array
    {
        $configId = $config instanceof ServiceConfig ? $config->getId() : (int) ($config['id'] ?? 0);
        $pageToken = $config instanceof ServiceConfig ? $config->getPageToken() : (string) ($config['page_token'] ?? '');
        $pageId = $config instanceof ServiceConfig ? $config->getPageId() : (string) ($config['page_id'] ?? '');
        $label = $config instanceof ServiceConfig ? $config->getLabel() : (string) ($config['label'] ?? "Page #{$pageId}");

        if (trim($pageToken) === '') {
            throw new ReviewSyncException("Facebook Page {$label} has no access token configured. Please connect Facebook in Settings.");
        }

        if (trim($pageId) === '') {
            throw new ReviewSyncException("Facebook Page {$label} has no page_id configured. Please reconnect Facebook in Settings.");
        }

        return [$pageToken, $pageId, $label];
    }

    /**
     * @param array<string, mixed> $query
     * @return array<int, array<string, mixed>>
     */
    private function fetchPaginatedGraphItems(
        string $endpoint,
        array $query,
        string $label,
        ?string $lastObjId,
        int $maxPages,
    ): array {
        $items = [];
        $nextUrl = null;
        $pageCounter = 0;

        do {
            $pageCounter++;
            $body = $this->requestGraphJson(
                url: ($nextUrl !== null && $nextUrl !== '') ? $nextUrl : $endpoint,
                query: ($nextUrl !== null && $nextUrl !== '') ? [] : $query,
                label: $label,
                pageCounter: $pageCounter,
            );

            $rawItems = (array) ($body['data'] ?? []);
            if (empty($rawItems)) {
                break;
            }

            $reachedLastKnown = false;
            foreach ($rawItems as $raw) {
                if (!is_array($raw)) {
                    continue;
                }
                if ($lastObjId !== null && $lastObjId !== '' && (($raw['id'] ?? '') === $lastObjId)) {
                    $reachedLastKnown = true;
                    break;
                }
                $items[] = $raw;
            }

            if ($reachedLastKnown) {
                break;
            }

            $nextUrl = !empty($body['paging']['next']) ? (string) $body['paging']['next'] : null;
            if ($pageCounter >= $maxPages) {
                break;
            }
        } while ($nextUrl !== null && $nextUrl !== '');

        return $items;
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    private function requestGraphJson(string $url, array $query, string $label, int $pageCounter): array
    {
        try {
            $options = ['headers' => ['Accept' => 'application/json']];
            if (!empty($query)) {
                $options['query'] = $query;
            }
            $response = $this->httpClient->request('GET', $url, $options);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                throw new ReviewSyncException("Facebook Graph API returned HTTP status {$statusCode} for {$label} on page {$pageCounter}.");
            }

            $body = json_decode((string) $response->getBody(), true);
            if (!is_array($body)) {
                throw new ReviewSyncException("Facebook Graph API returned non-JSON response for {$label} on page {$pageCounter}.");
            }

            return $body;
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
            throw new ReviewSyncException($msg, $e->getCode(), $e);
        } catch (ReviewSyncException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ReviewSyncException("Unexpected error calling Facebook Graph API for {$label}: " . $e->getMessage(), 0, $e);
        }
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

