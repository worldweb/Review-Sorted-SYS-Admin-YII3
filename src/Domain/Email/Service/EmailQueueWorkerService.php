<?php

declare(strict_types=1);

namespace App\Domain\Email\Service;

use App\Domain\Email\EmailRepositoryInterface;
use App\Domain\Email\Provider\MailgunClientInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
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
        $stats = $this->processBatchDetailed($batchSize);
        return $stats['dispatched'];
    }

    /**
     * Processes a batch of pending emails atomically with detailed outcome statistics.
     *
     * @param int $batchSize Number of pending messages to claim per iteration
     * @return array{claimed: int, dispatched: int, failed: int, recovered_stale: int}
     */
    public function processBatchDetailed(int $batchSize = 100): array
    {
        $stats = [
            'claimed' => 0,
            'dispatched' => 0,
            'failed' => 0,
            'recovered_stale' => 0,
        ];

        // Fail-fast guard in production: refuse to process/claim queue items if live provider is not configured
        if (\App\Environment::isProd() && !$this->mailgunClient->isConfigured()) {
            throw new RuntimeException(
                '[EmailQueueWorker] Cannot process email queue: Mailgun is not configured for live sending in production. Ensure MAILGUN_API_KEY is set in .env.'
            );
        }

        // 1. Recover stale processing jobs older than 15 minutes
        $recovered = $this->emailRepo->recoverStaleQueueJobs(15);
        $stats['recovered_stale'] = $recovered;
        if ($recovered > 0) {
            $this->logger?->warning(sprintf('[EmailQueueWorker] Recovered %d stale processing email jobs.', $recovered));
        }

        // 2. Fetch pending queue items
        $pendingItems = $this->emailRepo->findPendingQueue($batchSize);
        if (empty($pendingItems)) {
            return $stats;
        }

        $now = date('Y-m-d H:i:s');

        foreach ($pendingItems as $item) {
            if (!$item->isTimeToSend()) {
                continue;
            }

            // 3. Atomic conditional claim: transition status 0 -> 4 with lock time
            if (!$this->emailRepo->claimQueueJob($item->getId(), $now)) {
                continue; // Job was concurrently claimed by another worker
            }

            $stats['claimed']++;

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
                $this->logger?->info(sprintf('[EmailQueueWorker] Sent email #%d', $item->getId()));
                $stats['dispatched']++;
            } catch (Throwable $e) {
                $this->emailRepo->markQueueFailed($item->getId());
                $this->logger?->error(sprintf(
                    '[EmailQueueWorker] Failed sending email #%d (%s): %s',
                    $item->getId(),
                    $e::class,
                    $this->sanitizeErrorMessage($e->getMessage(), $item->getEmail(), $item->getBody())
                ));
                $stats['failed']++;
            }
        }

        return $stats;
    }

    /**
     * Redacts recipient PII, message content, and credentials from provider exception messages.
     */
    private function sanitizeErrorMessage(string $message, string $recipientEmail, string $body): string
    {
        if ($recipientEmail !== '') {
            $message = str_ireplace($recipientEmail, '[redacted-email]', $message);
        }
        if ($body !== '' && strlen($body) > 4) {
            $message = str_replace($body, '[redacted-content]', $message);
        }

        $message = (string) preg_replace('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', '[redacted-email]', $message);
        $message = (string) preg_replace('/\b(Bearer\s+|Basic\s+|api[_-]?key[=:\s]+|token[=:\s]+|secret[=:\s]+|key-[a-zA-Z0-9]+)[^\s,;"\']*/i', '$1[redacted]', $message);

        if (strlen($message) > 250) {
            $message = substr($message, 0, 250) . '... [truncated]';
        }

        return $message;
    }
}
