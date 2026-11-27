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
        $stats = $this->processBatchDetailed($batchSize);
        return $stats['dispatched'];
    }

    /**
     * Processes a batch of pending WhatsApp messages atomically with detailed outcome statistics.
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
        if (\App\Environment::isProd() && !$this->metaWhatsAppClient->isConfigured()) {
            throw new RuntimeException(
                '[WhatsAppQueueWorker] Cannot process WhatsApp queue: Meta WhatsApp client is not configured for live messaging in production. Ensure WHATSAPP_ENABLED=true and credentials are set in .env.'
            );
        }

        // 1. Recover stale processing jobs older than 15 minutes
        $recovered = $this->whatsappRepo->recoverStaleQueueJobs(15);
        $stats['recovered_stale'] = $recovered;
        if ($recovered > 0) {
            $this->logger?->warning(sprintf('[WhatsAppQueueWorker] Recovered %d stale processing jobs.', $recovered));
        }

        // 2. Fetch pending queue items
        $pendingItems = $this->whatsappRepo->findPendingQueue($batchSize);
        if (empty($pendingItems)) {
            return $stats;
        }

        $now = date('Y-m-d H:i:s');

        foreach ($pendingItems as $item) {
            if (!$item->isTimeToSend()) {
                continue;
            }

            // 3. Atomic conditional claim: transition status 0 -> 2 with lock time
            if (!$this->whatsappRepo->claimQueueJob($item->getId(), $now)) {
                continue; // Job was concurrently claimed
            }

            $stats['claimed']++;

            try {
                $rawMessage = $item->getMessage();
                $templateData = null;

                // Check if message payload is structured JSON specifying a template
                if (str_starts_with(trim($rawMessage), '{')) {
                    $decoded = json_decode($rawMessage, true);
                    if (is_array($decoded) && !empty($decoded['template'])) {
                        $templateData = $decoded;
                    }
                }

                if ($templateData !== null) {
                    // Direct template dispatch
                    $response = $this->metaWhatsAppClient->sendTemplateMessage(
                        phone: $item->getPhone(),
                        templateName: (string) $templateData['template'],
                        languageCode: (string) ($templateData['language'] ?? 'en_US'),
                        components: (array) ($templateData['components'] ?? [])
                    );
                } else {
                    // Attempt free-text message dispatch (standard inside 24h customer-service window)
                    try {
                        $response = $this->metaWhatsAppClient->sendTextMessage(
                            phone: $item->getPhone(),
                            message: $rawMessage,
                        );
                    } catch (Throwable $sendErr) {
                        // Check if rejected by Meta because recipient is outside the 24-hour service window (Error 131047)
                        $errMsg = $sendErr->getMessage();
                        $isOutside24hWindow = str_contains($errMsg, '131047')
                            || str_contains($errMsg, 'outside the allowed window')
                            || str_contains($errMsg, '24 hours');

                        $fallbackTemplate = \App\Environment::get('WHATSAPP_CAMPAIGN_TEMPLATE_NAME');

                        if ($isOutside24hWindow && !empty($fallbackTemplate) && is_string($fallbackTemplate)) {
                            $this->logger?->info(sprintf(
                                '[WhatsAppQueueWorker] Message #%d outside 24h window; retrying with configured template: %s',
                                $item->getId(),
                                $fallbackTemplate
                            ));
                            $response = $this->metaWhatsAppClient->sendTemplateMessage(
                                phone: $item->getPhone(),
                                templateName: $fallbackTemplate,
                                languageCode: 'en_US',
                                components: [
                                    [
                                        'type' => 'body',
                                        'parameters' => [
                                            ['type' => 'text', 'text' => $rawMessage],
                                        ],
                                    ],
                                ]
                            );
                        } else {
                            throw $sendErr;
                        }
                    }
                }

                $providerMessageId = null;
                if (!empty($response['messages'][0]['id'])) {
                    $providerMessageId = (string) $response['messages'][0]['id'];
                }

                $this->whatsappRepo->markQueueSent($item->getId(), $providerMessageId);
                $this->logger?->info(sprintf('[WhatsAppQueueWorker] Sent message #%d', $item->getId()));
                $stats['dispatched']++;
            } catch (Throwable $e) {
                $this->whatsappRepo->markQueueFailed($item->getId());
                $this->logger?->error(sprintf(
                    '[WhatsAppQueueWorker] Failed sending WhatsApp message #%d (%s): %s',
                    $item->getId(),
                    $e::class,
                    $this->sanitizeErrorMessage($e->getMessage(), $item->getPhone(), $item->getMessage())
                ));
                $stats['failed']++;
            }
        }

        return $stats;
    }

    /**
     * Redacts recipient PII, message content, and credentials from provider exception messages.
     */
    private function sanitizeErrorMessage(string $message, string $recipientPhone, string $rawMessage): string
    {
        if ($recipientPhone !== '') {
            $message = str_replace($recipientPhone, '[redacted-phone]', $message);
        }
        if ($rawMessage !== '' && strlen($rawMessage) > 4) {
            $message = str_replace($rawMessage, '[redacted-content]', $message);
        }

        $message = (string) preg_replace('/\+?\d[\d\-\s\(\)]{6,}\d/', '[redacted-phone]', $message);
        $message = (string) preg_replace('/\b(Bearer\s+|EAA[a-zA-Z0-9]+|access_token[=:\s]+|api[_-]?key[=:\s]+|token[=:\s]+|secret[=:\s]+)[^\s,;"\']*/i', '$1[redacted]', $message);

        if (strlen($message) > 250) {
            $message = substr($message, 0, 250) . '... [truncated]';
        }

        return $message;
    }
}
