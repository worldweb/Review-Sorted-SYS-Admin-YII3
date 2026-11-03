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
     * Checks if provider credentials and network dispatch are active.
     */
    public function isConfigured(): bool;
}
