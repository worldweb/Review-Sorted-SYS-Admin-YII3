<?php

declare(strict_types=1);

namespace App\Domain\Email\Provider;

use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Mailgun API Client Implementation.
 * Encapsulates communication with Mailgun REST API.
 * In read-only / testing mode, real HTTP calls are bypassed safely.
 */
final class MailgunClient implements MailgunClientInterface
{
    public function __construct(
        private string $apiKey = '',
        private string $domain = 'reviewssorted.com',
        private string $endpoint = 'https://api.eu.mailgun.net',
        private string $defaultFrom = 'noreply@reviewssorted.com',
        private bool $enabled = false,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function send(
        string $to,
        string $from,
        string $subject,
        string $htmlBody,
        array $bcc = [],
    ): ?string {
        $sender = $from !== '' ? $from : $this->defaultFrom;

        if (!$this->enabled || $this->apiKey === '' || $this->apiKey === 'disabled') {
            if (\App\Environment::isProd()) {
                if (!$this->enabled) {
                    throw new RuntimeException(
                        '[MailgunClient] Live email sending is disabled in production (MAILGUN_ENABLED is false). Simulated sending is prohibited in production.'
                    );
                }
                throw new RuntimeException(
                    '[MailgunClient] Cannot send email in production: MAILGUN_API_KEY is missing or invalid.'
                );
            }

            $this->logger?->info(sprintf(
                '[MailgunClient: Simulated Send] To: %s, From: %s, Subject: "%s" (Network disabled)',
                $to,
                $sender,
                $subject
            ));
            // Return simulated provider ID
            return sprintf('<simulated-%s@%s>', bin2hex(random_bytes(12)), $this->domain);
        }

        $url = rtrim($this->endpoint, '/') . '/v3/' . rawurlencode($this->domain) . '/messages';

        $postFields = [
            'from' => $sender,
            'to' => $to,
            'subject' => $subject,
            'html' => $htmlBody,
        ];

        foreach ($bcc as $b) {
            $postFields['bcc'] = $b;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postFields),
            CURLOPT_USERPWD => 'api:' . $this->apiKey,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);

        if ($response === false || $curlErr !== '') {
            throw new RuntimeException('Mailgun connection failed: ' . ($curlErr ?: 'Unknown error'));
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException(sprintf('Mailgun API returned HTTP %d: %s', $httpCode, (string) $response));
        }

        $data = json_decode((string) $response, true);
        return isset($data['id']) ? (string) $data['id'] : null;
    }

    public function isConfigured(): bool
    {
        return $this->enabled && $this->apiKey !== '' && $this->apiKey !== 'disabled';
    }
}
