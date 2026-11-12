<?php

declare(strict_types=1);

namespace App\Domain\Review\Sync;

use App\Domain\SocialConnection\ServiceConfig;
use App\Domain\SocialConnection\SocialConnectionRepositoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * GoogleReviewSyncClient.
 * Fetches reviews from Google Business Profile MyBusiness v4 REST API.
 * Handles automatic access token refresh, resource naming, pagination, and error isolation.
 */
final class GoogleReviewSyncClient implements GoogleReviewSyncClientInterface
{
    private const GOOGLE_API_BASE = 'https://mybusiness.googleapis.com/v4/';
    private const GOOGLE_TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';

    private const STAR_RATINGS = [
        'ONE' => 1.0,
        'TWO' => 2.0,
        'THREE' => 3.0,
        'FOUR' => 4.0,
        'FIVE' => 5.0,
        'STAR_RATING_UNSPECIFIED' => 0.0,
    ];

    private ClientInterface $httpClient;
    private LoggerInterface $logger;

    public function __construct(
        private ?SocialConnectionRepositoryInterface $connectionRepo = null,
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
        $pageName = $config instanceof ServiceConfig ? $config->getPageName() : (string) ($config['page_name'] ?? '');
        $token = $config instanceof ServiceConfig ? $config->getToken() : (string) ($config['token'] ?? '');
        $label = $config instanceof ServiceConfig ? $config->getLabel() : (string) ($config['label'] ?? "Location #{$configId}");

        if (trim($pageToken) === '') {
            $this->logger->warning("[GoogleReviewSync] ServiceConfig #{$configId} ({$label}) has no page_token OAuth bundle.");
            return [];
        }

        $tokenData = json_decode($pageToken, true);
        if (!is_array($tokenData) || empty($tokenData['access_token'])) {
            $this->logger->warning("[GoogleReviewSync] ServiceConfig #{$configId} ({$label}) has malformed page_token JSON.");
            return [];
        }

        $accessToken = (string) $tokenData['access_token'];

        // Token Expiration Buffer Check (60s buffer)
        $isExpired = false;
        if (isset($tokenData['created'], $tokenData['expires_in'])) {
            $isExpired = ((int) $tokenData['created'] + (int) $tokenData['expires_in'] - 60) < time();
        }

        if ($isExpired && !empty($tokenData['refresh_token'])) {
            $refreshed = $this->refreshAccessToken((string) $tokenData['refresh_token'], $config);
            if ($refreshed !== null) {
                $accessToken = $refreshed;
                $tokenData['access_token'] = $refreshed;
                $tokenData['created'] = time();
                if ($this->connectionRepo !== null && $configId > 0) {
                    $this->connectionRepo->updatePageToken($configId, (string) json_encode($tokenData));
                }
            } else {
                $this->logger->error("[GoogleReviewSync] Access token refresh failed for ServiceConfig #{$configId} ({$label}).");
                return [];
            }
        }

        // Determine GBP resource path: accounts/{acc}/locations/{loc}
        $resourcePath = (str_contains($pageName, 'accounts/')) ? $pageName : rtrim($token, '/') . '/' . ltrim($pageName, '/');
        $endpoint = self::GOOGLE_API_BASE . ltrim($resourcePath, '/') . '/reviews';

        $reviews = [];
        $nextPageToken = null;
        $pageCounter = 0;
        $retried401 = false;

        do {
            $pageCounter++;
            $queryParams = [
                'pageSize' => 100,
            ];
            if ($nextPageToken !== null && $nextPageToken !== '') {
                $queryParams['pageToken'] = $nextPageToken;
            }

            try {
                $response = $this->httpClient->request('GET', $endpoint, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Accept' => 'application/json',
                    ],
                    'query' => $queryParams,
                ]);

                $statusCode = $response->getStatusCode();
                if ($statusCode !== 200) {
                    $this->logger->warning("[GoogleReviewSync] Google API returned status {$statusCode} for {$label} on page {$pageCounter}.");
                    break;
                }

                $body = json_decode((string) $response->getBody(), true);
                if (!is_array($body)) {
                    break;
                }

                $rawReviews = (array) ($body['reviews'] ?? []);
                foreach ($rawReviews as $rdata) {
                    $parsed = $this->parseGoogleReview($rdata);
                    if ($parsed !== null) {
                        $reviews[] = $parsed;
                    }
                }

                $nextPageToken = !empty($body['nextPageToken']) ? (string) $body['nextPageToken'] : null;

                // Safety guard against infinite pagination loops
                if ($pageCounter >= 50) {
                    $this->logger->warning("[GoogleReviewSync] Reached maximum pagination limit (50 pages) for {$label}.");
                    break;
                }
            } catch (RequestException $e) {
                // If 401 Unauthorized, attempt exactly one in-flight token refresh and retry
                if ($e->hasResponse() && $e->getResponse()->getStatusCode() === 401 && !$retried401 && !empty($tokenData['refresh_token'])) {
                    $retried401 = true;
                    $this->logger->warning("[GoogleReviewSync] Received HTTP 401 for {$label}; attempting in-flight token refresh and retry...");
                    $refreshed = $this->refreshAccessToken((string) $tokenData['refresh_token'], $config);
                    if ($refreshed !== null) {
                        $accessToken = $refreshed;
                        $tokenData['access_token'] = $refreshed;
                        $tokenData['created'] = time();
                        if ($this->connectionRepo !== null && $configId > 0) {
                            $this->connectionRepo->updatePageToken($configId, (string) json_encode($tokenData));
                        }

                        // Retry request once with the refreshed token
                        try {
                            $retryResponse = $this->httpClient->request('GET', $endpoint, [
                                'headers' => [
                                    'Authorization' => 'Bearer ' . $accessToken,
                                    'Accept' => 'application/json',
                                ],
                                'query' => $queryParams,
                            ]);

                            if ($retryResponse->getStatusCode() === 200) {
                                $retryBody = json_decode((string) $retryResponse->getBody(), true);
                                if (is_array($retryBody)) {
                                    $rawReviews = (array) ($retryBody['reviews'] ?? []);
                                    foreach ($rawReviews as $rdata) {
                                        $parsed = $this->parseGoogleReview($rdata);
                                        if ($parsed !== null) {
                                            $reviews[] = $parsed;
                                        }
                                    }
                                    $nextPageToken = !empty($retryBody['nextPageToken']) ? (string) $retryBody['nextPageToken'] : null;
                                    continue;
                                }
                            }
                        } catch (Throwable $retryE) {
                            $this->logger->error("[GoogleReviewSync] Retry after 401 failed for {$label}: " . $retryE->getMessage());
                        }
                    }
                }

                $errMsg = $e->getMessage();
                if ($e->hasResponse()) {
                    $res = $e->getResponse();
                    $errJson = json_decode((string) ($res ? $res->getBody() : ''), true);
                    if (is_array($errJson) && isset($errJson['error']['message'])) {
                        $errMsg = (string) $errJson['error']['message'];
                    }
                }
                $this->logger->error("[GoogleReviewSync] Request error fetching reviews for {$label}: {$errMsg}");
                break;
            } catch (Throwable $e) {
                $this->logger->error("[GoogleReviewSync] Unexpected error fetching reviews for {$label}: " . $e->getMessage());
                break;
            }
        } while ($nextPageToken !== null && $nextPageToken !== '');

        $this->logger->info(sprintf(
            '[GoogleReviewSync] Fetched %d review(s) across %d page(s) for %s.',
            count($reviews),
            $pageCounter,
            $label
        ));

        return $reviews;
    }

    /**
     * Parses a single Google API review object into standard domain structure.
     *
     * @param array<string, mixed> $review
     * @return array<string, mixed>|null
     */
    private function parseGoogleReview(array $review): ?array
    {
        $reviewId = (string) ($review['reviewId'] ?? '');
        if ($reviewId === '') {
            return null;
        }

        $reviewer = (array) ($review['reviewer'] ?? []);
        $customerName = (string) ($reviewer['displayName'] ?? 'A Google User');
        if (trim($customerName) === '') {
            $customerName = 'A Google User';
        }

        $starRating = (string) ($review['starRating'] ?? 'STAR_RATING_UNSPECIFIED');
        $ratingVal = self::STAR_RATINGS[$starRating] ?? 0.0;

        $comment = (string) ($review['comment'] ?? '-');
        if (trim($comment) === '') {
            $comment = '-';
        }

        $createTime = !empty($review['createTime'])
            ? date('Y-m-d H:i:s', strtotime((string) $review['createTime']))
            : date('Y-m-d H:i:s');

        $resourceName = (string) ($review['name'] ?? '');

        $parsed = [
            'obj_id' => $reviewId,
            'customer_id' => $customerName,
            'customer_name' => $customerName,
            'customer_email' => null,
            'customer_phone' => null,
            'customer_state' => null,
            'customer_region' => null,
            'review_rate' => $ratingVal,
            'review_text' => $comment,
            'review_recommend' => $ratingVal >= 4.0 ? 'yes' : ($ratingVal > 0 ? 'no' : null),
            'review_service' => 'Google',
            'review_date' => $createTime,
            'review_extra' => $resourceName,
            'type' => 'REVIEW',
            'is_google' => true,
            'comments' => [],
        ];

        // Parse merchant review reply if present
        if (!empty($review['reviewReply']) && is_array($review['reviewReply'])) {
            $reply = $review['reviewReply'];
            $replyComment = (string) ($reply['comment'] ?? '');
            if (trim($replyComment) !== '') {
                $replyTime = !empty($reply['updateTime'])
                    ? date('Y-m-d H:i:s', strtotime((string) $reply['updateTime']))
                    : $createTime;

                $parsed['comments'][] = [
                    'obj_id' => $reviewId . '-reply',
                    'customer_id' => null,
                    'customer_name' => null,
                    'reply_text' => $replyComment,
                    'reply_date' => $replyTime,
                    'reply_extra' => json_encode($reply, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        return $parsed;
    }

    /**
     * Refreshes an expired Google access token using the OAuth refresh token.
     */
    private function refreshAccessToken(string $refreshToken, ServiceConfig|array $config): ?string
    {
        $clientId = '';
        $clientSecret = '';

        if ($this->connectionRepo !== null) {
            $apis = $this->connectionRepo->findApisByService(\App\Domain\SocialConnection\Api::SERVICE_GOOGLE);
            foreach ($apis as $api) {
                if (!empty($api->getClientJson())) {
                    $json = json_decode($api->getClientJson(), true);
                    if (is_array($json)) {
                        $info = $json['web'] ?? $json['installed'] ?? $json;
                        if (!empty($info['client_id']) && !empty($info['client_secret'])) {
                            $clientId = (string) $info['client_id'];
                            $clientSecret = (string) $info['client_secret'];
                            break;
                        }
                    }
                }
            }
        }

        if ($clientId === '' || $clientSecret === '') {
            $clientId = \App\Environment::googleClientId();
            $clientSecret = \App\Environment::googleClientSecret();
        }

        if ($clientId === '' || $clientSecret === '') {
            $this->logger->warning('[GoogleReviewSync] Missing Google client_id or client_secret for token refresh.');
            return null;
        }

        try {
            $response = $this->httpClient->request('POST', self::GOOGLE_TOKEN_ENDPOINT, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ],
                'form_params' => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'refresh_token' => $refreshToken,
                    'grant_type' => 'refresh_token',
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode((string) $response->getBody(), true);
                if (is_array($data) && !empty($data['access_token'])) {
                    return (string) $data['access_token'];
                }
            }
        } catch (Throwable $e) {
            $this->logger->warning('[GoogleReviewSync] OAuth token refresh request failed: ' . $e->getMessage());
        }

        return null;
    }
}

