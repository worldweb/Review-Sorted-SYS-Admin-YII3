<?php

declare(strict_types=1);

namespace App\Shared;

/**
 * Shared Template Placeholder Rendering Service.
 * Safely renders merge tags across Email and WhatsApp templates.
 * Strictly replicates legacy double-hash syntax: ##NAME##, ##EMAIL##, ##PHONE##, etc.
 */
final class TemplateRendererService
{
    /**
     * Available merge tags and their documentation metadata.
     *
     * @var array<string, array{label: string, scope: string, example: string}>
     */
    public const SUPPORTED_TAGS = [
        '##NAME##' => [
            'label' => 'Customer / Recipient Name',
            'scope' => 'Campaigns & Reviews',
            'example' => 'John Doe',
        ],
        '##EMAIL##' => [
            'label' => 'Customer / Recipient Email',
            'scope' => 'Email Campaigns',
            'example' => 'john@example.com',
        ],
        '##PHONE##' => [
            'label' => 'Customer / Recipient Phone',
            'scope' => 'WhatsApp Campaigns',
            'example' => '+61400111222',
        ],
        '##RATE##' => [
            'label' => 'Rating (1–5 Stars)',
            'scope' => 'Review Follow-ups',
            'example' => '5',
        ],
        '##REVIEW##' => [
            'label' => 'Review Comment Text',
            'scope' => 'Review Follow-ups',
            'example' => 'Great customer experience!',
        ],
        '##LOCATION##' => [
            'label' => 'Location / Service Name',
            'scope' => 'Review Follow-ups',
            'example' => 'Downtown Branch',
        ],
    ];

    /**
     * Renders HTML email content by replacing placeholders with context data.
     *
     * @param string $templateContent Base HTML or custom message body
     * @param array<string, mixed> $contextData Key-value array of recipient/context values
     */
    public function renderEmail(string $templateContent, array $contextData = []): string
    {
        $rendered = $templateContent;

        foreach ($contextData as $key => $value) {
            if (is_array($value) || is_object($value)) {
                continue;
            }

            $placeholder = '##' . strtoupper((string) $key) . '##';
            // Sanitize replaced user input to prevent HTML injection inside emails
            $safeValue = htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $rendered = str_ireplace($placeholder, $safeValue, $rendered);
        }

        return $rendered;
    }

    /**
     * Renders WhatsApp message content as sanitized plain text with placeholder replacements.
     *
     * @param string $templateContent Base text or custom message
     * @param array<string, mixed> $contextData Key-value array of recipient/context values
     */
    public function renderWhatsApp(string $templateContent, array $contextData = []): string
    {
        $rendered = $templateContent;

        foreach ($contextData as $key => $value) {
            if (is_array($value) || is_object($value)) {
                continue;
            }

            $placeholder = '##' . strtoupper((string) $key) . '##';
            $plainValue = (string) ($value ?? '');
            $rendered = str_ireplace($placeholder, $plainValue, $rendered);
        }

        // WhatsApp messages must strictly remain plain text
        $plainText = strip_tags($rendered);
        // Normalize multiple blank lines to clean formatting
        $cleaned = preg_replace("/[\r\n]{3,}/", "\n\n", $plainText);

        return trim((string) $cleaned);
    }

    /**
     * Returns dictionary of supported tags for view rendering.
     *
     * @return array<string, array{label: string, scope: string, example: string}>
     */
    public function getSupportedTags(): array
    {
        return self::SUPPORTED_TAGS;
    }
}
