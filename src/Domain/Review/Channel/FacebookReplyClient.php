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
 * FacebookReplyClient.
 * Executes live comment and review replies via the Facebook Graph API v25.0.
 * Replicates the exact endpoints, payload, and response mapping of FacebookResolver and FacebookReviewReplyData.
 */
final class FacebookReplyClient implements FacebookReplyClientInterface
{
    private const GRAPH_API_VERSION = 'v25.0';
    private const GRAPH_API_BASE = 'https://graph.facebook.com/';

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
                'Facebook channel is missing a valid page access token. Please re-authenticate the page in channel settings.',
                'MISSING_PAGE_TOKEN',
                'Facebook'
            );
        }

        $objectId = trim($review->getObjId());
        if ($objectId === '') {
            return ReplyDispatchResult::failure(
                'Review is missing a valid Facebook object ID (obj_id).',
                'MISSING_OBJECT_ID',
                'Facebook'
            );
        }

        $endpointUrl = self::GRAPH_API_BASE . self::GRAPH_API_VERSION . '/' . rawurlencode($objectId) . '/comments';

        try {
            $response = $this->httpClient->request('POST', $endpointUrl, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'form_params' => [
                    'message' => $message,
                    'access_token' => $pageToken,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            if ($statusCode >= 200 && $statusCode < 300 && is_array($data) && isset($data['id'])) {
                // Confirmed success from Facebook Graph API
                $replyObjId = (string) $data['id'];
                $now = date('Y-m-d H:i:s');
                $replyExtra = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                $this->logger->info("Successfully published reply for review {$review->getId()} to Facebook Graph API (Reply ID: {$replyObjId}).");

                return ReplyDispatchResult::success(
                    objId: $replyObjId,
                    replyDate: $now,
                    replyExtra: $replyExtra,
                    platform: 'Facebook'
                );
            }

            $rawError = is_array($data) && isset($data['error']['message']) ? $data['error']['message'] : 'Missing ID in response payload';
            return ReplyDispatchResult::failure(
                "Facebook Graph API returned unexpected response: {$rawError}",
                (string) $statusCode,
                'Facebook'
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
                    $rawMsg = (string) ($apiError['message'] ?? $errorMessage);
                    $errorCode = (string) ($apiError['code'] ?? $statusCode);
                    $errorSubcode = isset($apiError['error_subcode']) ? (int) $apiError['error_subcode'] : 0;

                    // Provide human-friendly explanations for known Facebook OAuth errors
                    if ($errorCode === '190') {
                        if ($errorSubcode === 463 || $errorSubcode === 467) {
                            $errorMessage = 'Facebook page access token has expired. Please re-authenticate your Facebook page in channel settings.';
                        } else {
                            $errorMessage = 'Facebook authorization failed: ' . $rawMsg;
                        }
                    } elseif ($errorCode === '200' || $errorCode === '10') {
                        $errorMessage = 'Permission denied by Facebook: The connected account lacks pages_manage_engagement or comment publishing permissions.';
                    } elseif ($errorCode === '100') {
                        $errorMessage = 'Invalid target object on Facebook: The post or comment may have been deleted or is inaccessible.';
                    } else {
                        $errorMessage = $rawMsg;
                    }
                } else {
                    $errorCode = (string) $statusCode;
                }
            }

            // Sanitize log to ensure tokens are never logged
            $this->logger->error("Facebook API dispatch error for review {$review->getId()} [{$errorCode}]: {$errorMessage}");

            return ReplyDispatchResult::failure(
                "Facebook API error (Code {$errorCode}): {$errorMessage}",
                $errorCode,
                'Facebook'
            );
        } catch (\Throwable $e) {
            $this->logger->error("Unexpected exception during Facebook reply dispatch for review {$review->getId()}: " . $e->getMessage());

            return ReplyDispatchResult::failure(
                'Facebook reply dispatch failed: ' . $e->getMessage(),
                'UNEXPECTED_ERROR',
                'Facebook'
            );
        }
    }
}
