<?php

declare(strict_types=1);

namespace App\Domain\SocialPost\Resolver;

use App\Domain\SocialConnection\SocialConnectionRepositoryInterface;
use App\Domain\SocialPost\Client\MetaGraphApiClientInterface;
use App\Domain\SocialPost\DTO\PlatformDispatchResult;
use App\Domain\SocialPost\Exception\MetaGraphApiException;
use App\Domain\SocialPost\Exception\MetaNetworkException;
use App\Domain\SocialPost\Service\SocialMediaUrlResolverInterface;
use App\Domain\SocialPost\Service\SocialPlatformResolverInterface;
use App\Domain\SocialPost\SocialPost;
use App\Domain\SocialPost\SocialPostDestination;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Platform resolver for publishing to Facebook Pages via Meta Graph API v25.0.
 *
 * Supports Text posts (/feed), Photo posts (/photos), and Video posts (/videos).
 */
final class FacebookPlatformResolver implements SocialPlatformResolverInterface
{
    private const PLATFORM = 'facebook';
    private const MAX_CONTENT_LENGTH = 63206;

    public function __construct(
        private SocialConnectionRepositoryInterface $connectionRepo,
        private MetaGraphApiClientInterface $metaClient,
        private SocialMediaUrlResolverInterface $mediaUrlResolver,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function getPlatform(): string
    {
        return self::PLATFORM;
    }

    public function dispatch(SocialPost $post, SocialPostDestination $destination): PlatformDispatchResult
    {
        $destId = $destination->getId();

        // 1. Validate platform target
        if ($destination->getPlatform() !== self::PLATFORM) {
            return PlatformDispatchResult::permanentFailure(
                sprintf('Invalid platform "%s" targeted by FacebookPlatformResolver.', $destination->getPlatform())
            );
        }

        // 2. Validate ServiceConfig ID
        $configId = $destination->getServiceConfigId();
        if ($configId === null || $configId <= 0) {
            return PlatformDispatchResult::permanentFailure(
                'Destination is missing a valid Facebook service configuration ID.'
            );
        }

        // 3. Retrieve Facebook connection
        $serviceConfig = $this->connectionRepo->findById($configId);
        if ($serviceConfig === null) {
            return PlatformDispatchResult::permanentFailure(
                sprintf('Facebook service configuration #%d not found.', $configId)
            );
        }

        // 4. Strict tenant isolation check
        if (
            $serviceConfig->getAccountId() !== $destination->getAccountId()
            || $serviceConfig->getAccountId() !== $post->getAccountId()
        ) {
            $this->logger?->error(sprintf(
                '[FacebookPlatformResolver] Tenant ownership violation for destination #%d: DestAccount=%d, PostAccount=%d, ConfigAccount=%d',
                $destId,
                $destination->getAccountId(),
                $post->getAccountId(),
                (int) $serviceConfig->getAccountId()
            ));

            return PlatformDispatchResult::permanentFailure(
                'Tenant ownership mismatch: destination account does not match connected Facebook Page account.'
            );
        }

        // 5. Validate connection status and credentials
        if (!$serviceConfig->isLinked()) {
            return PlatformDispatchResult::permanentFailure(
                'Facebook Page connection is deactivated or unlinked. Please reconnect the Page in channel settings.'
            );
        }

        if (!$serviceConfig->isFacebook()) {
            return PlatformDispatchResult::permanentFailure(
                'Target service configuration is not a Facebook connection.'
            );
        }

        $pageId = trim((string) $serviceConfig->getPageId());
        if ($pageId === '') {
            return PlatformDispatchResult::permanentFailure(
                'Facebook Page configuration is missing a Page ID.'
            );
        }

        $pageToken = trim((string) $serviceConfig->getPageToken());
        if ($pageToken === '') {
            return PlatformDispatchResult::permanentFailure(
                'Facebook Page configuration is missing a Page Access Token. Please re-authenticate the Facebook Page.'
            );
        }

        // 6. Content length validation
        $content = $post->getContent();
        if (mb_strlen($content) > self::MAX_CONTENT_LENGTH) {
            return PlatformDispatchResult::permanentFailure(
                sprintf('Facebook post content exceeds the maximum limit of %s characters.', number_format(self::MAX_CONTENT_LENGTH))
            );
        }

        // 7. Media validation & endpoint resolution
        $mediaType = $post->getMediaType();
        $mediaUrl = $post->getMediaUrl();
        $publicMediaUrl = null;

        if ($mediaType === SocialPost::MEDIA_IMAGE || $mediaType === SocialPost::MEDIA_VIDEO) {
            if (empty($mediaUrl)) {
                return PlatformDispatchResult::permanentFailure(
                    sprintf('Media type is "%s" but media attachment path is empty.', $mediaType)
                );
            }

            try {
                $publicMediaUrl = $this->mediaUrlResolver->resolvePublicUrl($mediaUrl);
                if ($publicMediaUrl === null) {
                    return PlatformDispatchResult::permanentFailure('Media URL could not be resolved into a public URL.');
                }
            } catch (InvalidArgumentException $e) {
                return PlatformDispatchResult::permanentFailure(
                    'Media URL validation failed: ' . $e->getMessage()
                );
            }
        } elseif (trim($content) === '') {
            return PlatformDispatchResult::permanentFailure(
                'Facebook text-only post cannot be empty.'
            );
        }

        // 8. Execute remote publishing request
        try {
            if ($mediaType === SocialPost::MEDIA_IMAGE) {
                $endpoint = sprintf('/%s/photos', rawurlencode($pageId));
                $params = [
                    'caption' => $content,
                    'url' => $publicMediaUrl,
                ];
                $response = $this->metaClient->post($endpoint, $params, $pageToken);

                // Meta photo publishing returns ['id' => 'photo_id', 'post_id' => 'page_post_id']
                // Prefer post_id to represent the feed post
                $remoteId = (string) ($response['post_id'] ?? ($response['id'] ?? ''));
            } elseif ($mediaType === SocialPost::MEDIA_VIDEO) {
                $endpoint = sprintf('/%s/videos', rawurlencode($pageId));
                $params = [
                    'description' => $content,
                    'file_url' => $publicMediaUrl,
                ];
                $response = $this->metaClient->post($endpoint, $params, $pageToken);
                $remoteId = (string) ($response['id'] ?? '');
            } else {
                $endpoint = sprintf('/%s/feed', rawurlencode($pageId));
                $params = [
                    'message' => $content,
                ];
                $response = $this->metaClient->post($endpoint, $params, $pageToken);
                $remoteId = (string) ($response['id'] ?? '');
            }

            if (trim($remoteId) === '') {
                return PlatformDispatchResult::ambiguousFailure(
                    'Ambiguous delivery: Facebook API responded with HTTP success but omitted a valid remote post ID.',
                    200
                );
            }

            $this->logger?->info(sprintf(
                '[FacebookPlatformResolver] Successfully published destination #%d to Facebook Page %s (Remote ID: %s)',
                $destId,
                $pageId,
                $remoteId
            ));

            return PlatformDispatchResult::success($remoteId, 200, $response);

        } catch (MetaGraphApiException $e) {
            return $this->classifyApiException($e, $destId);
        } catch (MetaNetworkException $e) {
            // Socket timeout, DNS drop, or connection reset during POST publishing
            $ambiguousMsg = sprintf(
                'Ambiguous delivery: Network connection dropped or timed out during Facebook publishing request: %s',
                $e->getMessage()
            );
            $this->logger?->warning(sprintf('[FacebookPlatformResolver] Destination #%d: %s', $destId, $ambiguousMsg));

            return PlatformDispatchResult::ambiguousFailure($ambiguousMsg);
        } catch (Throwable $e) {
            $ambiguousMsg = sprintf(
                'Ambiguous delivery: Unexpected exception during Facebook publishing: %s',
                $e->getMessage()
            );
            $this->logger?->error(sprintf('[FacebookPlatformResolver] Destination #%d: %s', $destId, $ambiguousMsg));

            return PlatformDispatchResult::ambiguousFailure($ambiguousMsg);
        }
    }

    private function classifyApiException(MetaGraphApiException $e, int $destId): PlatformDispatchResult
    {
        $statusCode = $e->getHttpStatusCode();
        $errorCode = $e->getErrorCode();
        $message = $e->getMessage();

        // 1. Expired, invalid, or revoked Page Access Token (OAuth 190)
        if ($e->isAuthError()) {
            $err = sprintf(
                'Facebook authentication failed (OAuth %d): Page access token is expired or revoked. Please reconnect your Facebook Page.',
                $errorCode
            );
            $this->logger?->error(sprintf('[FacebookPlatformResolver] Destination #%d: %s', $destId, $err));
            return PlatformDispatchResult::permanentFailure($err, $statusCode, $e->getRawError());
        }

        // 2. Permission errors (Code 200, 10, 298)
        if ($e->isPermissionError()) {
            $err = sprintf(
                'Permission denied by Facebook (Error %d): Connected account lacks required publishing permissions (pages_manage_posts).',
                $errorCode
            );
            $this->logger?->error(sprintf('[FacebookPlatformResolver] Destination #%d: %s', $destId, $err));
            return PlatformDispatchResult::permanentFailure($err, $statusCode, $e->getRawError());
        }

        // 3. Permanent parameter or validation errors (Code 100)
        if ($e->isPermanentParameterError()) {
            $err = sprintf('Facebook validation error (Code 100): %s', $message);
            $this->logger?->error(sprintf('[FacebookPlatformResolver] Destination #%d: %s', $destId, $err));
            return PlatformDispatchResult::permanentFailure($err, $statusCode, $e->getRawError());
        }

        // 4. Rate limits (Code 4, 17, 32, 341, 613, HTTP 429) rejected before post execution
        if ($e->isRateLimit()) {
            $err = sprintf('Facebook rate limit exceeded (Code %d): %s', $errorCode, $message);
            $this->logger?->warning(sprintf('[FacebookPlatformResolver] Destination #%d: %s', $destId, $err));
            return PlatformDispatchResult::temporaryFailure($err, $statusCode, $e->getRetryAfterSeconds(), $e->getRawError());
        }

        // 5. Server crash or internal Meta error during POST publishing (Code 1, 2, HTTP 5xx)
        // Since request reached Meta, a server crash might have occurred after the post was created
        if ($e->isServerCrash()) {
            $err = sprintf(
                'Ambiguous delivery: Meta server error (Code %d, HTTP %d) occurred during publishing: %s',
                $errorCode,
                $statusCode,
                $message
            );
            $this->logger?->warning(sprintf('[FacebookPlatformResolver] Destination #%d: %s', $destId, $err));
            return PlatformDispatchResult::ambiguousFailure($err, $statusCode);
        }

        // 6. Generic permanent failure for other HTTP 4xx errors
        $err = sprintf('Facebook publishing failed (Code %d, HTTP %d): %s', $errorCode, $statusCode, $message);
        $this->logger?->error(sprintf('[FacebookPlatformResolver] Destination #%d: %s', $destId, $err));

        return PlatformDispatchResult::permanentFailure($err, $statusCode, $e->getRawError());
    }
}
