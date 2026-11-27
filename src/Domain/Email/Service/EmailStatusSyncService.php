<?php

declare(strict_types=1);

namespace App\Domain\Email\Service;

use App\Domain\Email\EmailQueue;
use App\Domain\Email\EmailRepositoryInterface;
use App\Domain\Email\Provider\MailgunClientInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Polls Mailgun Events API for sent/accepted emails and transitions delivery status.
 */
final class EmailStatusSyncService
{
    public function __construct(
        private EmailRepositoryInterface $emailRepo,
        private MailgunClientInterface $mailgunClient,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Synchronizes delivery statuses for pending email queue items.
     *
     * @return array{checked: int, delivered: int, accepted: int, failed: int, errors: int}
     */
    public function syncPendingStatuses(int $limit = 100): array
    {
        $stats = [
            'checked' => 0,
            'delivered' => 0,
            'accepted' => 0,
            'failed' => 0,
            'errors' => 0,
        ];

        if (\App\Environment::isProd() && !$this->mailgunClient->isConfigured()) {
            throw new RuntimeException(
                '[EmailStatusSync] Cannot poll Mailgun events: Mailgun is not configured for live operation in production.'
            );
        }

        $emails = $this->emailRepo->findPendingDeliveryWithExternalId(max(1, $limit));
        if (empty($emails)) {
            return $stats;
        }

        foreach ($emails as $email) {
            $stats['checked']++;
            $externalId = (string) $email->getExternalId();

            try {
                $events = $this->mailgunClient->getEvents($externalId);
                if (empty($events)) {
                    continue;
                }

                // Evaluate terminal states first (delivered/failed) across returned events
                $resolvedStatus = null;
                foreach ($events as $event) {
                    if (!is_array($event)) {
                        continue;
                    }
                    $eventName = strtolower((string) ($event['event'] ?? ''));
                    if ($eventName === 'delivered') {
                        $resolvedStatus = 'delivered';
                        break;
                    }
                    if ($eventName === 'failed' || $eventName === 'rejected') {
                        $resolvedStatus = 'failed';
                        break;
                    }
                    if ($eventName === 'accepted' && $resolvedStatus === null) {
                        $resolvedStatus = 'accepted';
                    }
                }

                if ($resolvedStatus === 'delivered') {
                    if ($this->emailRepo->markQueueDelivered($email->getId())) {
                        $stats['delivered']++;
                    }
                } elseif ($resolvedStatus === 'failed') {
                    if ($this->emailRepo->markQueueFailed($email->getId())) {
                        $stats['failed']++;
                    }
                } elseif ($resolvedStatus === 'accepted' && $email->getStatus() !== EmailQueue::STATUS_ACCEPTED) {
                    if ($this->emailRepo->markQueueAccepted($email->getId())) {
                        $stats['accepted']++;
                    }
                }
            } catch (Throwable $e) {
                $stats['errors']++;
                $this->logger?->error(sprintf(
                    '[EmailStatusSync] Failed checking status for email #%d (%s): %s',
                    $email->getId(),
                    $e::class,
                    $e->getMessage()
                ));
            }
        }

        return $stats;
    }
}
