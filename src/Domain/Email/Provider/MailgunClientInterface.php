<?php

declare(strict_types=1);

namespace App\Domain\Email\Provider;

/**
 * Interface for Outbound Mailgun Provider Client.
 */
interface MailgunClientInterface
{
    /**
     * Dispatches an outbound email via Mailgun API.
     *
     * @param string $to Recipient email address
     * @param string $from Sender email address
     * @param string $subject Email subject line
     * @param string $htmlBody HTML rendered email body
     * @param string[] $bcc Optional list of BCC recipient emails
     * @return string|null External provider message ID (e.g. <20261103...@api.eu.mailgun.net>)
     */
    public function send(
        string $to,
        string $from,
        string $subject,
        string $htmlBody,
        array $bcc = [],
    ): ?string;

    /**
     * Checks if provider credentials and network dispatch are active.
     */
    public function isConfigured(): bool;
}
