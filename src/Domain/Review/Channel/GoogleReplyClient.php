<?php

declare(strict_types=1);

namespace App\Domain\Review\Channel;

use App\Domain\Review\ReplyDispatchResult;
use App\Domain\Review\Review;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * GoogleReplyClient.
 * Executes live review replies via the Google Business Profile (MyBusiness) v4 REST API.
 * Replicates the exact endpoints, payload, and response mapping of GoogleResolver and GoogleReviewReplyData.
 */
final class GoogleReplyClient implements GoogleReplyClientInterface
{
    private const GOOGLE_API_BASE = 'https://mybusiness.googleapis.com/v4/';
    private const GOOGLE_TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';

    private ClientInterface $httpClient;
    private LoggerInterface $logger;

    public function __construct(
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->httpClient = $httpClient ?? new Client(['timeout' => 15.0]);
        $this->logger = $logger ?? new NullLogger();
    }

    public function reply(Review $review, string $message, array $configRow): ReplyDispatchResult
    {
        $pageToken = (string) ($configRow['page_token'] ?? '');
        if (trim($pageToken) === '') {
            return ReplyDispatchResult::failure(
                'Google channel is missing OAuth authorization tokens. Please reconnect your Google account in channel settings.',
                'MISSING_OAUTH_TOKEN',
                'Google'
            );
        }

        // Parse token JSON
        $tokenData = json_decode($pageToken, true);
        if (!is_array($tokenData) || empty($tokenData['access_token'])) {
            return ReplyDispatchResult::failure(
                'Invalid Google OAuth token format in channel configuration. Re-authentication is required.',
                'INVALID_TOKEN_FORMAT',
                'Google'
            );
        }

        $accessToken = (string) $tokenData['access_token'];

        // Check token expiration buffer (60s)
        $isExpired = false;
        if (isset($tokenData['created'], $tokenData['expires_in'])) {
            $isExpired = ((int) $tokenData['created'] + (int) $tokenData['expires_in'] - 60) < time();
        }

        if ($isExpired && !empty($tokenData['refresh_token'])) {
            $refreshedToken = $this->refreshAccessToken($tokenData['refresh_token'], $configRow);
            if ($refreshedToken !== null) {
                $accessToken = $refreshedToken;
            } else {
                return ReplyDispatchResult::failure(
                    'Google OAuth access token is expired and automatic refresh failed. Please re-authenticate your Google channel.',
                    'TOKEN_REFRESH_FAILED',
                    'Google'
                );
            }
        }

        // Determine review resource name (accounts/.../locations/.../reviews/...)
        $resourceName = $review->getReviewExtra();
        if ($resourceName === null || trim($resourceName) === '' || !str_contains($resourceName, 'reviews/')) {
            // Check fallback in obj_id if stored as resource name
            if (str_contains($review->getObjId(), 'locations/') && str_contains($review->getObjId(), 'reviews/')) {
                $resourceName = $review->getObjId();
            } else {
                return ReplyDispatchResult::failure(
                    'Review does not have a valid Google resource identifier (review_extra is missing).',
                    'INVALID_RESOURCE_IDENTIFIER',
                    'Google'
                );
            }
        }

        $endpointUrl = self::GOOGLE_API_BASE . ltrim($resourceName, '/') . '/reply';
        $payload = [
            'comment' => $message,
        ];

        try {
            $response = $this->httpClient->request('PUT', $endpointUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            if ($statusCode >= 200 && $statusCode < 300) {
                // Successful dispatch confirmed by Google API
                $now = date('Y-m-d H:i:s');
                $updateTime = (is_array($data) && isset($data['updateTime']))
                    ? date('Y-m-d H:i:s', strtotime((string) $data['updateTime']))
                    : $now;

                // Match legacy GoogleReviewReplyData obj_id format: {$review->obj_id}-reply
                $objId = $review->getObjId() . '-reply';

                $replyExtra = is_array($data)
                    ? json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                    : json_encode(['comment' => $message, 'updateTime' => $now], JSON_UNESCAPED_SLASHES);

                $this->logger->info("Successfully published reply for review {$review->getId()} to Google MyBusiness API.");

                return ReplyDispatchResult::success(
                    objId: $objId,
                    replyDate: $updateTime,
                    replyExtra: $replyExtra,
                    platform: 'Google'
                );
            }

            return ReplyDispatchResult::failure(
                "Google API returned unexpected HTTP status {$statusCode}.",
                (string) $statusCode,
                'Google'
            );
        } catch (RequestException $e) {
            $errorMessage = $e->getMessage();
            $errorCode = 'REQUEST_EXCEPTION';

            if ($e->hasResponse()) {
                $res = $e->getResponse();
                $statusCode = $res ? $res->getStatusCode() : 0;
                $errBody = $res ? (string) $res->getBody() : '';
                $errJson = json_decode($errBody, true);

                if (is_array($errJson) && isset($errJson['error'])) {
                    $apiError = $errJson['error'];
                    $errorMessage = (string) ($apiError['message'] ?? $errorMessage);
                    $errorCode = (string) ($apiError['status'] ?? $apiError['code'] ?? $statusCode);
                } else {
                    $errorCode = (string) $statusCode;
                }
            }

            // Sanitize log: ensure token or auth header is never logged
            $this->logger->error("Google API dispatch error for review {$review->getId()} [{$errorCode}]: {$errorMessage}");

            return ReplyDispatchResult::failure(
                "Google API error ({$errorCode}): {$errorMessage}",
                $errorCode,
                'Google'
            );
        } catch (\Throwable $e) {
            $this->logger->error("Unexpected exception during Google reply dispatch for review {$review->getId()}: " . $e->getMessage());

            return ReplyDispatchResult::failure(
                'Google reply dispatch failed: ' . $e->getMessage(),
                'UNEXPECTED_ERROR',
                'Google'
            );
        }
    }

    /**
     * Attempts to refresh an expired Google access token using the OAuth refresh token.
     */
    private function refreshAccessToken(string $refreshToken, array $configRow): ?string
    {
        $clientJsonStr = (string) ($configRow['api_client_json'] ?? '');
        if (trim($clientJsonStr) === '') {
            return null;
        }

        $clientConfig = json_decode($clientJsonStr, true);
        if (!is_array($clientConfig)) {
            return null;
        }

        $clientInfo = $clientConfig['web'] ?? $clientConfig['installed'] ?? $clientConfig;
        $clientId = (string) ($clientInfo['client_id'] ?? '');
        $clientSecret = (string) ($clientInfo['client_secret'] ?? '');

        if ($clientId === '' || $clientSecret === '') {
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
                    $this->logger->info("Successfully refreshed Google OAuth access token for service config {$configRow['id']}.");
                    return (string) $data['access_token'];
                }
            }
        } catch (\Throwable $e) {
            $this->logger->warning("Google token refresh request failed for service config {$configRow['id']}: " . $e->getMessage());
        }

        return null;
    }
}
