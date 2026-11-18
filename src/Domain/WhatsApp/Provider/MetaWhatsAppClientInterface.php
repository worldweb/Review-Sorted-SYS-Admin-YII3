<?php

declare(strict_types=1);

namespace App\Domain\WhatsApp\Provider;

/**
 * Interface for Meta WhatsApp Cloud API Provider Client.
 */
interface MetaWhatsAppClientInterface
{
    /**
     * Sends an outbound plain-text WhatsApp message.
     *
     * @param string $phone E.164 normalized recipient phone number (e.g. +61400111222)
     * @param string $message Plain text message content
     * @return array<string, mixed> Provider response payload including message ID
     */
    public function sendTextMessage(string $phone, string $message): array;

    /**
     * Sends an outbound template-based WhatsApp message (required outside the 24-hour service window).
     *
     * @param string $phone E.164 normalized recipient phone number (e.g. +61400111222)
     * @param string $templateName Name of approved Meta WhatsApp message template
     * @param string $languageCode Language code (e.g. en_US)
     * @param array<int, array<string, mixed>> $components Template components/parameters
     * @return array<string, mixed> Provider response payload including message ID
     */
    public function sendTemplateMessage(
        string $phone,
        string $templateName,
        string $languageCode = 'en_US',
        array $components = [],
    ): array;

    /**
     * Checks if provider credentials and network dispatch are active.
     */
    public function isConfigured(): bool;
}
