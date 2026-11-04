<?php

declare(strict_types=1);

namespace App\Domain\SocialPost\Service;

use App\Domain\SocialPost\DTO\DispatchOutcome;
use App\Domain\SocialPost\DTO\PlatformDispatchResult;
use App\Domain\SocialPost\SocialPostDestination;
use App\Domain\SocialPost\SocialPostRepositoryInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Social Posting Service implementation.
 *
 * Coordinates platform resolver execution, outcome classification, exponential backoff,
 * ambiguous delivery quarantine, and fenced atomic row updates.
 */
final class SocialPostingService implements SocialPostingServiceInterface
{
    public const MAX_RETRIES = 2; // Total 3 attempts: initial attempt (retry_count=0) + 2 retries (retry_count=1,2)

    public function __construct(
        private SocialPostRepositoryInterface $postRepo,
        private SocialPlatformResolverFactoryInterface $resolverFactory,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function processDestination(SocialPostDestination $destination, string $lockTime): PlatformDispatchResult
    {
        $platform = $destination->getPlatform();
        $destId = $destination->getId();

        // 1. Resolve platform publisher
        $resolver = $this->resolverFactory->getResolver($platform);
        if ($resolver === null) {
            // Defensive protection: if no resolver is registered for this platform,
            // safely release the claimed lock and return the destination to 'queued'.
            $this->postRepo->releaseClaimedDestination($destId, $lockTime);
            $this->logger?->info(sprintf(
                '[SocialPostingService] No resolver registered for platform "%s". Destination #%d released back to queued.',
                $platform,
                $destId
            ));

            return PlatformDispatchResult::temporaryFailure(
                sprintf('No platform resolver registered for platform "%s". Destination returned to queue.', $platform)
            );
        }

        // 2. Fetch parent post
        $post = $this->postRepo->findById($destination->getSocialPostId());
        if ($post === null) {
            $errorMsg = sprintf('Parent social post #%d not found.', $destination->getSocialPostId());
            $this->postRepo->markDestinationFailed($destId, $errorMsg, $lockTime, isAmbiguous: false);
            $this->logger?->error(sprintf('[SocialPostingService] Destination #%d failed: %s', $destId, $errorMsg));

            return PlatformDispatchResult::permanentFailure($errorMsg);
        }

        // 3. Dispatch to remote platform resolver
        try {
            $result = $resolver->dispatch($post, $destination);
        } catch (Throwable $e) {
            // Unhandled network timeout or transport exception during dispatch:
            // Must be quarantined as AMBIGUOUS to protect against duplicate publishing.
            $ambiguousMsg = 'Ambiguous delivery: ' . $e->getMessage();
            $this->postRepo->markDestinationFailed($destId, $ambiguousMsg, $lockTime, isAmbiguous: true);
            $this->logger?->warning(sprintf(
                '[SocialPostingService] Destination #%d threw unhandled exception during dispatch. Quarantined as ambiguous: %s',
                $destId,
                $e->getMessage()
            ));

            return PlatformDispatchResult::ambiguousFailure($ambiguousMsg);
        }

        // 4. Handle classified outcome
        return match ($result->getOutcome()) {
            DispatchOutcome::SUCCESS => $this->handleSuccess($destination, $result, $lockTime),
            DispatchOutcome::TEMPORARY_FAILURE => $this->handleTemporaryFailure($destination, $result, $lockTime),
            DispatchOutcome::PERMANENT_FAILURE => $this->handlePermanentFailure($destination, $result, $lockTime),
            DispatchOutcome::AMBIGUOUS_FAILURE => $this->handleAmbiguousFailure($destination, $result, $lockTime),
        };
    }

    private function handleSuccess(
        SocialPostDestination $destination,
        PlatformDispatchResult $result,
        string $lockTime,
    ): PlatformDispatchResult {
        $destId = $destination->getId();
        $remotePostId = $result->getRemotePostId();

        if ($remotePostId === null || trim($remotePostId) === '') {
            $ambiguousMsg = 'Ambiguous delivery: Platform resolver reported success but omitted the remote post ID.';
            $this->postRepo->markDestinationFailed($destId, $ambiguousMsg, $lockTime, isAmbiguous: true);
            $this->logger?->warning(sprintf('[SocialPostingService] Destination #%d: %s', $destId, $ambiguousMsg));

            return PlatformDispatchResult::ambiguousFailure($ambiguousMsg);
        }

        $fenced = $this->postRepo->markDestinationPublished($destId, $remotePostId, $lockTime);
        if (!$fenced) {
            $this->logger?->warning(sprintf(
                '[SocialPostingService] Destination #%d: Published remotely (ID: %s) but local DB lock expired or was superseded.',
                $destId,
                $remotePostId
            ));
        } else {
            $this->logger?->info(sprintf(
                '[SocialPostingService] Destination #%d successfully published (Remote ID: %s).',
                $destId,
                $remotePostId
            ));
        }

        return $result;
    }

    private function handleTemporaryFailure(
        SocialPostDestination $destination,
        PlatformDispatchResult $result,
        string $lockTime,
    ): PlatformDispatchResult {
        $destId = $destination->getId();
        $currentRetries = $destination->getRetryCount();

        if ($currentRetries >= self::MAX_RETRIES) {
            // Retries exhausted (initial attempt + 2 retries completed). Mark as failed.
            $exhaustedMsg = $result->getErrorMessage() ?? 'Maximum delivery retries exhausted.';
            $this->postRepo->markDestinationFailed($destId, $exhaustedMsg, $lockTime, isAmbiguous: false);
            $this->logger?->warning(sprintf(
                '[SocialPostingService] Destination #%d failed after %d retries: %s',
                $destId,
                $currentRetries,
                $exhaustedMsg
            ));

            return PlatformDispatchResult::permanentFailure($exhaustedMsg, $result->getHttpStatusCode());
        }

        // Schedule next retry with backoff:
        // Attempt 1 retry: 5 minutes (300s) default
        // Attempt 2 retry: 10 minutes (600s) default
        $nextRetryCount = $currentRetries + 1;
        $baseDelay = ($nextRetryCount === 1) ? 300 : 600;
        $delaySeconds = max($baseDelay, $result->getRetryAfterSeconds() ?? 0);

        $this->postRepo->rescheduleDestination($destId, $nextRetryCount, $delaySeconds, $lockTime);
        $this->logger?->info(sprintf(
            '[SocialPostingService] Destination #%d scheduled for retry #%d in %d seconds. Reason: %s',
            $destId,
            $nextRetryCount,
            $delaySeconds,
            $result->getErrorMessage() ?? 'Temporary error'
        ));

        return $result;
    }

    private function handlePermanentFailure(
        SocialPostDestination $destination,
        PlatformDispatchResult $result,
        string $lockTime,
    ): PlatformDispatchResult {
        $destId = $destination->getId();
        $errorMsg = $result->getErrorMessage() ?? 'Permanent delivery failure.';

        $this->postRepo->markDestinationFailed($destId, $errorMsg, $lockTime, isAmbiguous: false);
        $this->logger?->error(sprintf('[SocialPostingService] Destination #%d permanent failure: %s', $destId, $errorMsg));

        return $result;
    }

    private function handleAmbiguousFailure(
        SocialPostDestination $destination,
        PlatformDispatchResult $result,
        string $lockTime,
    ): PlatformDispatchResult {
        $destId = $destination->getId();
        $errorMsg = $result->getErrorMessage() ?? 'Worker encountered unconfirmed network response.';
        if (!str_starts_with($errorMsg, 'Ambiguous delivery:')) {
            $errorMsg = 'Ambiguous delivery: ' . $errorMsg;
        }

        $this->postRepo->markDestinationFailed($destId, $errorMsg, $lockTime, isAmbiguous: true);
        $this->logger?->warning(sprintf(
            '[SocialPostingService] Destination #%d quarantined as ambiguous failure: %s',
            $destId,
            $errorMsg
        ));

        return $result;
    }
}

