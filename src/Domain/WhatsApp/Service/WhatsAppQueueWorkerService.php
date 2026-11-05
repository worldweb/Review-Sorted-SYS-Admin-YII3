<?php

declare(strict_types=1);

namespace App\Domain\WhatsApp\Service;

use App\Domain\WhatsApp\Provider\MetaWhatsAppClientInterface;
use App\Domain\WhatsApp\WhatsAppRepositoryInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Background WhatsApp Queue Worker Service.
 * Manages atomic claiming, message dispatch, and lifecycle transitions for outbound WhatsApp messages.
 */
final class WhatsAppQueueWorkerService
{
    public function __construct(
        private WhatsAppRepositoryInterface $whatsappRepo,
        private MetaWhatsAppClientInterface $metaWhatsAppClient,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Processes a batch of pending WhatsApp messages atomically.
     *
     * @param int $batchSize Number of pending messages to claim per iteration
     * @return int Number of successfully dispatched messages
     */
    public function processBatch(int $batchSize = 100): int
    {
        // Fail-fast guard in production: refuse to process/claim queue items if live provider is not configured
        if (\App\Environment::isProd() && !$this->metaWhatsAppClient->isConfigured()) {
            throw new RuntimeException(
                '[WhatsAppQueueWorker] Cannot process WhatsApp queue: Meta WhatsApp client is not configured for live messaging in production. Ensure WHATSAPP_ENABLED=true and credentials are set in .env.'
            );
        }

        // 1. Recover stale processing jobs older than 15 minutes
        $recovered = $this->whatsappRepo->recoverStaleQueueJobs(15);
        if ($recovered > 0) {
            $this->logger?->warning(sprintf('[WhatsAppQueueWorker] Recovered %d stale processing jobs.', $recovered));
        }

        // 2. Fetch pending queue items
        $pendingItems = $this->whatsappRepo->findPendingQueue($batchSize);
        if (empty($pendingItems)) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $successCount = 0;

        foreach ($pendingItems as $item) {
            if (!$item->isTimeToSend()) {
                continue;
            }

            // 3. Atomic conditional claim: transition status 0 -> 2 with lock time
            if (!$this->whatsappRepo->claimQueueJob($item->getId(), $now)) {
                continue; // Job was concurrently claimed
            }

            try {
                $response = $this->metaWhatsAppClient->sendTextMessage(
                    phone: $item->getPhone(),
                    message: $item->getMessage(),
                );

                $providerMessageId = null;
                if (!empty($response['messages'][0]['id'])) {
                    $providerMessageId = (string) $response['messages'][0]['id'];
                }

                $this->whatsappRepo->markQueueSent($item->getId(), $providerMessageId);
                $this->logger?->info(sprintf('[WhatsAppQueueWorker] Sent message #%d to %s', $item->getId(), $item->getPhone()));
                $successCount++;
            } catch (Throwable $e) {
                $this->whatsappRepo->markQueueFailed($item->getId());
                $this->logger?->error(sprintf(
                    '[WhatsAppQueueWorker] Failed sending WhatsApp message #%d to %s: %s',
                    $item->getId(),
                    $item->getPhone(),
                    $e->getMessage()
                ));
            }
        }

        return $successCount;
    }
}
