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
 * Platform resolver for publishing to Instagram Business / Creator accounts via Meta Graph API v25.0.
 *
 * Implements the 3-step Container protocol:
 * 1. Create Media Container (POST /{ig-user-id}/media)
 * 2. Bounded Status Polling (GET /{container-id}?fields=status_code,status)
 * 3. Publish Container (POST /{ig-user-id}/media_publish)
 */
final class InstagramPlatformResolver implements SocialPlatformResolverInterface
{
    private const PLATFORM = 'instagram';
    private const MAX_CONTENT_LENGTH = 2200;
    private const MAX_POLL_ATTEMPTS = 6;
    private const POLL_SLEEP_SECONDS = 5;

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
                sprintf('Invalid platform "%s" targeted by InstagramPlatformResolver.', $destination->getPlatform())
            );
        }

        // 2. Pre-flight check: Instagram API strictly requires media; text-only is unsupported
        $mediaType = $post->getMediaType();
        $mediaUrl = $post->getMediaUrl();

        if ($mediaType === SocialPost::MEDIA_NONE || empty($mediaUrl)) {
            $this->logger?->info(sprintf(
                '[InstagramPlatformResolver] Destination #%d rejected: text-only posts are unsupported on Instagram.',
                $destId
            ));

            return PlatformDispatchResult::permanentFailure(
                'Instagram Business API does not support text-only posts. An image or video media attachment is required.'
            );
        }

        if ($mediaType !== SocialPost::MEDIA_IMAGE && $mediaType !== SocialPost::MEDIA_VIDEO) {
            return PlatformDispatchResult::permanentFailure(
                sprintf('Unsupported media type "%s" for Instagram publishing. Only image and video are supported.', $mediaType)
            );
        }

        // 3. Validate SubService ID
        $subId = $destination->getSubServiceId();
        if ($subId === null || $subId <= 0) {
            return PlatformDispatchResult::permanentFailure(
                'Destination is missing a valid Instagram sub-service ID.'
            );
        }

        // 4. Retrieve Instagram sub-service
        $subService = $this->connectionRepo->findSubServiceById($subId);
        if ($subService === null) {
            return PlatformDispatchResult::permanentFailure(
                sprintf('Instagram account sub-service #%d not found.', $subId)
            );
        }

        // 5. Strict tenant isolation check on SubService
        if (
            $subService->getAccountId() !== $destination->getAccountId()
            || $subService->getAccountId() !== $post->getAccountId()
        ) {
            $this->logger?->error(sprintf(
                '[InstagramPlatformResolver] Tenant ownership violation for destination #%d: DestAccount=%d, PostAccount=%d, SubAccount=%d',
                $destId,
                $destination->getAccountId(),
                $post->getAccountId(),
                $subService->getAccountId()
            ));

            return PlatformDispatchResult::permanentFailure(
                'Tenant ownership mismatch: destination account does not match connected Instagram account.'
            );
        }

        if (!$subService->isInstagram()) {
            return PlatformDispatchResult::permanentFailure(
                'Target sub-service is not an Instagram connection.'
            );
        }

        $igUserId = trim($subService->getObjId());
        if ($igUserId === '') {
            return PlatformDispatchResult::permanentFailure(
                'Instagram account configuration is missing an Instagram User ID (obj_id).'
            );
        }

        // 6. Retrieve and validate parent Facebook Page connection
        $parentConfig = $this->connectionRepo->findById($subService->getServiceConfigId());
        if ($parentConfig === null) {
            return PlatformDispatchResult::permanentFailure(
                sprintf('Parent Facebook connection #%d for Instagram account not found.', $subService->getServiceConfigId())
            );
        }

        if ($parentConfig->getAccountId() !== $subService->getAccountId()) {
            $this->logger?->error(sprintf(
                '[InstagramPlatformResolver] Cross-tenant parent connection detected for subservice #%d: SubAccount=%d, ParentAccount=%d',
                $subId,
                $subService->getAccountId(),
                (int) $parentConfig->getAccountId()
            ));

            return PlatformDispatchResult::permanentFailure(
                'Tenant ownership mismatch: parent Facebook Page belongs to a different account.'
            );
        }

        if (!$parentConfig->isLinked()) {
            return PlatformDispatchResult::permanentFailure(
                'Parent Facebook Page connection is unlinked or deactivated. Please reconnect in channel settings.'
            );
        }

        if (!$parentConfig->isFacebook()) {
            return PlatformDispatchResult::permanentFailure(
                'Parent service configuration is not a Facebook connection.'
            );
        }

        if ($destination->getServiceConfigId() !== null && $destination->getServiceConfigId() !== $subService->getServiceConfigId()) {
            return PlatformDispatchResult::permanentFailure(
                'Destination service configuration ID does not match the linked parent Facebook connection.'
            );
        }

        $pageToken = trim((string) $parentConfig->getPageToken());
        if ($pageToken === '') {
            return PlatformDispatchResult::permanentFailure(
                'Parent Facebook Page Access Token is missing. Please re-authenticate the connected Facebook Page.'
            );
        }

        // 7. Validate caption character length
        $content = $post->getContent();
        if (mb_strlen($content) > self::MAX_CONTENT_LENGTH) {
            return PlatformDispatchResult::permanentFailure(
                sprintf('Instagram caption exceeds the maximum limit of %s characters.', number_format(self::MAX_CONTENT_LENGTH))
            );
        }

        // 8. Resolve public media URL
        try {
            $publicMediaUrl = $this->mediaUrlResolver->resolvePublicUrl($mediaUrl);
            if ($publicMediaUrl === null) {
                return PlatformDispatchResult::permanentFailure('Instagram media URL could not be resolved into a public URL.');
            }
        } catch (InvalidArgumentException $e) {
            return PlatformDispatchResult::permanentFailure(
                'Media URL validation failed: ' . $e->getMessage()
            );
        }

        // 9. Execute 3-Step Instagram Container Workflow
        return $this->executeContainerWorkflow($post, $destination, $igUserId, $pageToken, $publicMediaUrl, $mediaType);
    }

    private function executeContainerWorkflow(
        SocialPost $post,
        SocialPostDestination $destination,
        string $igUserId,
        string $pageToken,
        string $publicMediaUrl,
        string $mediaType,
    ): PlatformDispatchResult {
        $destId = $destination->getId();
        $caption = $post->getContent();

        // -------------------------------------------------------------
        // STEP 1: Create Media Container (POST /{ig-user-id}/media)
        // -------------------------------------------------------------
        $containerParams = [
            'caption' => $caption,
        ];

        if ($mediaType === SocialPost::MEDIA_VIDEO) {
            $containerParams['media_type'] = 'REELS';
            $containerParams['video_url'] = $publicMediaUrl;
        } else {
            $containerParams['image_url'] = $publicMediaUrl;
        }

        try {
            $containerRes = $this->metaClient->post(
                sprintf('/%s/media', rawurlencode($igUserId)),
                $containerParams,
                $pageToken
            );

            $containerId = (string) ($containerRes['id'] ?? '');
            if (trim($containerId) === '') {
                return PlatformDispatchResult::permanentFailure(
                    'Failed to create Instagram media container: Meta response omitted container ID.',
                    rawResponse: $containerRes
                );
            }

            $this->logger?->info(sprintf(
                '[InstagramPlatformResolver] Destination #%d created container #%s for Instagram account %s.',
                $destId,
                $containerId,
                $igUserId
            ));

        } catch (MetaGraphApiException $e) {
            // Container creation failure: no public post was created on Instagram.
            return $this->classifyPrePublishApiException($e, $destId, 'container creation');
        } catch (MetaNetworkException $e) {
            // Transport error on container creation: no public post was created on Instagram.
            $this->logger?->warning(sprintf(
                '[InstagramPlatformResolver] Destination #%d network error during container creation: %s',
                $destId,
                $e->getMessage()
            ));

            return PlatformDispatchResult::temporaryFailure(
                'Network error during Instagram container creation: ' . $e->getMessage(),
                retryAfterSeconds: 60
            );
        } catch (Throwable $e) {
            $this->logger?->error(sprintf(
                '[InstagramPlatformResolver] Destination #%d unexpected error during container creation: %s',
                $destId,
                $e->getMessage()
            ));

            return PlatformDispatchResult::permanentFailure(
                'Unexpected error during Instagram container creation: ' . $e->getMessage()
            );
        }

        // -------------------------------------------------------------
        // STEP 2: Bounded Status Polling (GET /{container-id}?fields=status_code,status)
        // -------------------------------------------------------------
        $attempts = 0;
        $isContainerReady = false;
        $lastStatusResponse = null;

        while ($attempts < self::MAX_POLL_ATTEMPTS) {
            $attempts++;

            try {
                $statusRes = $this->metaClient->get(
                    sprintf('/%s', rawurlencode($containerId)),
                    ['fields' => 'status_code,status'],
                    $pageToken
                );
                $lastStatusResponse = $statusRes;

                $statusCode = strtoupper(trim((string) ($statusRes['status_code'] ?? '')));

                if ($statusCode === 'FINISHED') {
                    $isContainerReady = true;
                    break;
                }

                if ($statusCode === 'ERROR') {
                    $errorDetail = (string) ($statusRes['status'] ?? 'Unknown processing error');
                    $this->logger?->error(sprintf(
                        '[InstagramPlatformResolver] Destination #%d container %s failed with ERROR: %s',
                        $destId,
                        $containerId,
                        $errorDetail
                    ));

                    return PlatformDispatchResult::permanentFailure(
                        'Instagram media container processing failed on Meta: ' . $errorDetail,
                        rawResponse: $statusRes
                    );
                }

                if ($statusCode === 'EXPIRED') {
                    $this->logger?->error(sprintf(
                        '[InstagramPlatformResolver] Destination #%d container %s expired before publish.',
                        $destId,
                        $containerId
                    ));

                    return PlatformDispatchResult::permanentFailure(
                        'Instagram media container expired before publishing.',
                        rawResponse: $statusRes
                    );
                }

                // Status is IN_PROGRESS - wait before next attempt unless it's the final attempt
                if ($attempts < self::MAX_POLL_ATTEMPTS) {
                    sleep(self::POLL_SLEEP_SECONDS);
                }

            } catch (MetaGraphApiException $e) {
                return $this->classifyPrePublishApiException($e, $destId, 'container polling');
            } catch (MetaNetworkException $e) {
                // Network error during GET polling: safe temporary failure because no publishing occurred
                $this->logger?->warning(sprintf(
                    '[InstagramPlatformResolver] Destination #%d network error during polling attempt %d: %s',
                    $destId,
                    $attempts,
                    $e->getMessage()
                ));

                return PlatformDispatchResult::temporaryFailure(
                    'Network error checking Instagram container status: ' . $e->getMessage(),
                    retryAfterSeconds: 60
                );
            }
        }

        if (!$isContainerReady) {
            // Container remained IN_PROGRESS across the polling window.
            // Critical safeguard: media_publish was NOT called, so NO public post exists.
            $this->logger?->info(sprintf(
                '[InstagramPlatformResolver] Destination #%d container %s still IN_PROGRESS after %d attempts. Safely queuing temporary retry.',
                $destId,
                $containerId,
                $attempts
            ));

            return PlatformDispatchResult::temporaryFailure(
                'Instagram media container is still processing on Meta. Publishing will be retried automatically.',
                retryAfterSeconds: 120,
                rawResponse: $lastStatusResponse
            );
        }

        // -------------------------------------------------------------
        // STEP 3: Publish Container (POST /{ig-user-id}/media_publish)
        // -------------------------------------------------------------
        try {
            $publishRes = $this->metaClient->post(
                sprintf('/%s/media_publish', rawurlencode($igUserId)),
                ['creation_id' => $containerId],
                $pageToken
            );

            $remoteId = (string) ($publishRes['id'] ?? '');
            if (trim($remoteId) === '') {
                return PlatformDispatchResult::ambiguousFailure(
                    'Ambiguous delivery: Instagram media_publish succeeded but response did not contain a valid media ID.',
                    200
                );
            }

            $this->logger?->info(sprintf(
                '[InstagramPlatformResolver] Destination #%d published successfully to Instagram %s (Remote ID: %s)',
                $destId,
                $igUserId,
                $remoteId
            ));

            return PlatformDispatchResult::success($remoteId, 200, $publishRes);

        } catch (MetaGraphApiException $e) {
            return $this->classifyPublishApiException($e, $destId);
        } catch (MetaNetworkException $e) {
            // Transport error during POST /media_publish:
            // Meta may have processed the publishing action, but network failed before confirmation was received.
            // MUST be quarantined as AMBIGUOUS to protect against duplicate publishing!
            $ambiguousMsg = sprintf(
                'Ambiguous delivery: Network connection dropped or timed out during Instagram media_publish request: %s',
                $e->getMessage()
            );
            $this->logger?->warning(sprintf('[InstagramPlatformResolver] Destination #%d: %s', $destId, $ambiguousMsg));

            return PlatformDispatchResult::ambiguousFailure($ambiguousMsg);
        } catch (Throwable $e) {
            $ambiguousMsg = sprintf(
                'Ambiguous delivery: Unexpected exception during Instagram media_publish: %s',
                $e->getMessage()
            );
            $this->logger?->error(sprintf('[InstagramPlatformResolver] Destination #%d: %s', $destId, $ambiguousMsg));

            return PlatformDispatchResult::ambiguousFailure($ambiguousMsg);
        }
    }

    /**
     * Classifies Meta API exceptions that occur BEFORE the media_publish call
     * (i.e. during container creation or polling), where no public post could have been created.
     */
    private function classifyPrePublishApiException(MetaGraphApiException $e, int $destId, string $stage): PlatformDispatchResult
    {
        $statusCode = $e->getHttpStatusCode();
        $errorCode = $e->getErrorCode();
        $message = $e->getMessage();

        if ($e->isAuthError()) {
            $err = sprintf('Instagram authentication failed during %s: Access token is expired or revoked.', $stage);
            $this->logger?->error(sprintf('[InstagramPlatformResolver] Destination #%d: %s', $destId, $err));
            return PlatformDispatchResult::permanentFailure($err, $statusCode, $e->getRawError());
        }

        if ($e->isPermissionError()) {
            $err = sprintf('Permission denied by Instagram during %s (Error %d): Account lacks required publishing permissions (instagram_content_publish).', $stage, $errorCode);
            $this->logger?->error(sprintf('[InstagramPlatformResolver] Destination #%d: %s', $destId, $err));
            return PlatformDispatchResult::permanentFailure($err, $statusCode, $e->getRawError());
        }

        if ($e->isRateLimit()) {
            $err = sprintf('Instagram rate limit exceeded during %s: %s', $stage, $message);
            $this->logger?->warning(sprintf('[InstagramPlatformResolver] Destination #%d: %s', $destId, $err));
            return PlatformDispatchResult::temporaryFailure($err, $statusCode, $e->getRetryAfterSeconds(), $e->getRawError());
        }

        if ($e->isPermanentParameterError()) {
            $err = sprintf('Instagram parameter error during %s: %s', $stage, $message);
            $this->logger?->error(sprintf('[InstagramPlatformResolver] Destination #%d: %s', $destId, $err));
            return PlatformDispatchResult::permanentFailure($err, $statusCode, $e->getRawError());
        }

        $err = sprintf('Instagram API error during %s (Code %d, HTTP %d): %s', $stage, $errorCode, $statusCode, $message);
        $this->logger?->error(sprintf('[InstagramPlatformResolver] Destination #%d: %s', $destId, $err));

        return PlatformDispatchResult::permanentFailure($err, $statusCode, $e->getRawError());
    }

    /**
     * Classifies Meta API exceptions that occur DURING the atomic media_publish call.
     */
    private function classifyPublishApiException(MetaGraphApiException $e, int $destId): PlatformDispatchResult
    {
        $statusCode = $e->getHttpStatusCode();
        $errorCode = $e->getErrorCode();
        $message = $e->getMessage();

        if ($e->isAuthError()) {
            $err = 'Instagram authentication failed: Access token is expired or revoked. Please reconnect the Facebook Page.';
            $this->logger?->error(sprintf('[InstagramPlatformResolver] Destination #%d: %s', $destId, $err));
            return PlatformDispatchResult::permanentFailure($err, $statusCode, $e->getRawError());
        }

        if ($e->isPermissionError()) {
            $err = sprintf('Permission denied by Instagram (Error %d): Account lacks instagram_content_publish permission.', $errorCode);
            $this->logger?->error(sprintf('[InstagramPlatformResolver] Destination #%d: %s', $destId, $err));
            return PlatformDispatchResult::permanentFailure($err, $statusCode, $e->getRawError());
        }

        if ($e->isRateLimit()) {
            $err = sprintf('Instagram rate limit during publishing: %s', $message);
            $this->logger?->warning(sprintf('[InstagramPlatformResolver] Destination #%d: %s', $destId, $err));
            return PlatformDispatchResult::temporaryFailure($err, $statusCode, $e->getRetryAfterSeconds(), $e->getRawError());
        }

        // Server crash on POST /media_publish: remote post might have been created right before server error!
        if ($e->isServerCrash()) {
            $err = sprintf('Ambiguous delivery: Meta server error (Code %d, HTTP %d) during Instagram media_publish: %s', $errorCode, $statusCode, $message);
            $this->logger?->warning(sprintf('[InstagramPlatformResolver] Destination #%d: %s', $destId, $err));
            return PlatformDispatchResult::ambiguousFailure($err, $statusCode);
        }

        $err = sprintf('Instagram publish error (Code %d, HTTP %d): %s', $errorCode, $statusCode, $message);
        $this->logger?->error(sprintf('[InstagramPlatformResolver] Destination #%d: %s', $destId, $err));

        return PlatformDispatchResult::permanentFailure($err, $statusCode, $e->getRawError());
    }
}
