<?php

declare(strict_types=1);

namespace App\Domain\Email\Service;

use App\Domain\Email\EmailRepositoryInterface;
use App\Domain\Email\Provider\MailgunClientInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Background Email Queue Worker Service.
 * Manages atomic claiming, delivery dispatch, and lifecycle transitions for outbound emails.
 */
final class EmailQueueWorkerService
{
    public function __construct(
        private EmailRepositoryInterface $emailRepo,
        private MailgunClientInterface $mailgunClient,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Processes a batch of pending emails atomically.
     *
     * @param int $batchSize Number of pending messages to claim per iteration
     * @return int Number of successfully dispatched messages
     */
    public function processBatch(int $batchSize = 100): int
    {
        // 1. Recover stale processing jobs older than 15 minutes
        $recovered = $this->emailRepo->recoverStaleQueueJobs(15);
        if ($recovered > 0) {
            $this->logger?->warning(sprintf('[EmailQueueWorker] Recovered %d stale processing email jobs.', $recovered));
        }

        // 2. Fetch pending queue items
        $pendingItems = $this->emailRepo->findPendingQueue($batchSize);
        if (empty($pendingItems)) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $successCount = 0;

        foreach ($pendingItems as $item) {
            if (!$item->isTimeToSend()) {
                continue;
            }

            // 3. Atomic conditional claim: transition status 0 -> 4 with lock time
            if (!$this->emailRepo->claimQueueJob($item->getId(), $now)) {
                continue; // Job was concurrently claimed by another worker
            }

            try {
                $bccList = [];
                if ($item->getBcc() !== null && $item->getBcc() !== '') {
                    $decoded = json_decode($item->getBcc(), true);
                    if (is_array($decoded)) {
                        $bccList = array_filter(array_map('strval', $decoded));
                    }
                }

                $externalId = $this->mailgunClient->send(
                    to: $item->getEmail(),
                    from: '',
                    subject: $item->getSubject(),
                    htmlBody: $item->getBody(),
                    bcc: $bccList,
                );

                $this->emailRepo->markQueueSent($item->getId(), $externalId);
                $this->logger?->info(sprintf('[EmailQueueWorker] Sent email #%d to %s', $item->getId(), $item->getEmail()));
                $successCount++;
            } catch (Throwable $e) {
                $this->emailRepo->markQueueFailed($item->getId());
                $this->logger?->error(sprintf(
                    '[EmailQueueWorker] Failed sending email #%d to %s: %s',
                    $item->getId(),
                    $item->getEmail(),
                    $e->getMessage()
                ));
            }
        }

        return $successCount;
    }
}
