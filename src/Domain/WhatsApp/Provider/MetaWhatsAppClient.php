<?php

declare(strict_types=1);

namespace App\Domain\WhatsApp\Provider;

use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Meta WhatsApp Cloud API Client Implementation.
 * Encapsulates communication with Meta Graph WhatsApp API.
 * In read-only / testing mode, real HTTP calls are safely bypassed.
 */
final class MetaWhatsAppClient implements MetaWhatsAppClientInterface
{
    public function __construct(
        private string $token = '',
        private string $phoneNumberId = '',
        private string $apiVersion = 'v18.0',
        private bool $enabled = false,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function sendTextMessage(string $phone, string $message): array
    {
        if (!$this->enabled || $this->token === '' || $this->phoneNumberId === '') {
            if (\App\Environment::isProd()) {
                if (!$this->enabled) {
                    throw new RuntimeException(
                        '[MetaWhatsAppClient] Live WhatsApp messaging is disabled in production (WHATSAPP_ENABLED is false). Simulated sending is prohibited in production.'
                    );
                }
                throw new RuntimeException(
                    '[MetaWhatsAppClient] Cannot send WhatsApp message in production: WHATSAPP_TOKEN or WHATSAPP_PHONE_NUMBER_ID is missing.'
                );
            }

            $this->logger?->info(sprintf(
                '[MetaWhatsAppClient: Simulated Send] Phone: %s, Message length: %d chars (Network disabled)',
                $phone,
                mb_strlen($message)
            ));

            return [
                'messaging_product' => 'whatsapp',
                'contacts' => [
                    ['input' => $phone, 'wa_id' => ltrim($phone, '+')],
                ],
                'messages' => [
                    ['id' => 'wamid.simulated.' . bin2hex(random_bytes(16))],
                ],
            ];
        }

        $url = sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            $this->apiVersion,
            $this->phoneNumberId
        );

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'text',
            'text' => [
                'body' => $message,
            ],
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);

        if ($response === false || $curlErr !== '') {
            throw new RuntimeException('WhatsApp API connection failed: ' . ($curlErr ?: 'Unknown error'));
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $errDetail = sprintf('WhatsApp API HTTP %d', $httpCode);
            $errData = json_decode((string) $response, true);
            if (!empty($errData['error']['message'])) {
                $errDetail .= ': ' . $errData['error']['message'];
            }
            throw new RuntimeException($errDetail);
        }

        $data = json_decode((string) $response, true);
        if (!is_array($data)) {
            throw new RuntimeException('Invalid JSON response from WhatsApp API.');
        }

        return $data;
    }

    public function isConfigured(): bool
    {
        return $this->enabled && $this->token !== '' && $this->phoneNumberId !== '';
    }
}
