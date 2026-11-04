<?php

declare(strict_types=1);

namespace App\Domain\SocialPost\Service;

use App\Domain\SocialPost\SocialPostRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Background Social Post Queue Worker Service.
 * Manages atomic claiming, stale-lock recovery, platform discovery filtering, and worker batch loops.
 */
final class SocialPostQueueWorkerService
{
    public function __construct(
        private SocialPostRepositoryInterface $postRepo,
        private SocialPostingServiceInterface $postingService,
        private SocialPlatformResolverFactoryInterface $resolverFactory,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Processes a batch of pending social post destinations.
     *
     * @param int $batchSize Maximum number of destinations to process in this run
     * @return int Number of destinations successfully published in this batch
     */
    public function processBatch(int $batchSize = 50): int
    {
        $stats = $this->processBatchDetailed($batchSize);
        return $stats['published'];
    }

    /**
     * Processes a batch with detailed outcome telemetry.
     *
     * @param int $batchSize
     * @return array{recovered_stale: int, claimed: int, published: int, retried: int, failed: int, ambiguous: int, skipped: int}
     */
    public function processBatchDetailed(int $batchSize = 50): array
    {
        $stats = [
            'recovered_stale' => 0,
            'claimed' => 0,
            'published' => 0,
            'retried' => 0,
            'failed' => 0,
            'ambiguous' => 0,
            'skipped' => 0,
        ];

        // 1. Discover supported platforms
        $supportedPlatforms = $this->resolverFactory->getSupportedPlatforms();
        if (empty($supportedPlatforms)) {
            // Step 1 Foundation: No platform resolvers are registered yet.
            // Queue processing remains idle with zero database operations.
            $this->logger?->info('[SocialPostQueueWorker] No platform resolvers currently registered. Queue processing idle.');
            return $stats;
        }

        // 2. Recover stale processing destinations older than 15 minutes (lease expiry)
        $recovered = $this->postRepo->recoverStaleDestinations(15);
        $stats['recovered_stale'] = $recovered;
        if ($recovered > 0) {
            $this->logger?->warning(sprintf(
                '[SocialPostQueueWorker] Recovered %d stale processing destination(s).',
                $recovered
            ));
        }

        // 3. Retrieve pending destinations matching supported platforms only
        $pending = $this->postRepo->findPendingDestinations($batchSize, $supportedPlatforms);
        if (empty($pending)) {
            return $stats;
        }

        foreach ($pending as $dest) {
            $destId = $dest->getId();
            $lockTime = date('Y-m-d H:i:s');

            // 4. Atomic conditional claim: queued -> processing with lock timestamp
            if (!$this->postRepo->claimPendingDestination($destId, $lockTime)) {
                $stats['skipped']++;
                continue; // Concurrently claimed by another worker process
            }

            $stats['claimed']++;

            // 5. Delegate claimed destination to the posting service
            $result = $this->postingService->processDestination($dest, $lockTime);

            if ($result->isSuccess()) {
                $stats['published']++;
            } elseif ($result->isRetryable()) {
                $stats['retried']++;
            } elseif ($result->isAmbiguous()) {
                $stats['ambiguous']++;
            } else {
                $stats['failed']++;
            }
        }

        return $stats;
    }
}

