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
