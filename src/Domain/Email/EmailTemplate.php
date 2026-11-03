<?php

declare(strict_types=1);

namespace App\Domain\Email;

/**
 * Email Template Domain Entity.
 * Represents a saved or system-protected email layout and message template.
 * Mapped to database table 'email_template'.
 */
final class EmailTemplate
{
    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;

    public const PROTECTED_SYSTEM = 1;
    public const PROTECTED_USER = 0;

    public function __construct(
        private int $id,
        private ?int $accountId,
        private string $slug,
        private string $name,
        private string $text,
        private int $isProtected,
        private int $status,
        private ?int $createdAt = null,
        private ?int $updatedAt = null,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            accountId: isset($row['account_id']) && (int) $row['account_id'] > 0 ? (int) $row['account_id'] : null,
            slug: (string) ($row['slug'] ?? ''),
            name: (string) ($row['name'] ?? ''),
            text: (string) ($row['text'] ?? ''),
            isProtected: (int) ($row['protected'] ?? self::PROTECTED_USER),
            status: (int) ($row['status'] ?? self::STATUS_ACTIVE),
            createdAt: isset($row['created_at']) && (int) $row['created_at'] > 0 ? (int) $row['created_at'] : null,
            updatedAt: isset($row['updated_at']) && (int) $row['updated_at'] > 0 ? (int) $row['updated_at'] : null,
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAccountId(): ?int
    {
        return $this->accountId;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function isProtected(): bool
    {
        return $this->isProtected === self::PROTECTED_SYSTEM;
    }

    public function getProtected(): int
    {
        return $this->isProtected;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getCreatedAt(): ?int
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?int
    {
        return $this->updatedAt;
    }

    public function withUpdated(string $name, string $text, int $status): self
    {
        return new self(
            id: $this->id,
            accountId: $this->accountId,
            slug: $this->slug,
            name: $name,
            text: $text,
            isProtected: $this->isProtected,
            status: $status,
            createdAt: $this->createdAt,
            updatedAt: time(),
        );
    }

    public static function createCustom(string $name, string $text, ?int $accountId, int $status = self::STATUS_ACTIVE): self
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name) ?? '', '-'));
        if ($slug === '') {
            $slug = 'custom-template-' . time();
        }
        $now = time();
        return new self(
            id: 0,
            accountId: $accountId,
            slug: $slug,
            name: $name,
            text: $text,
            isProtected: self::PROTECTED_USER,
            status: $status,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    /**
     * Render template body with token replacements.
     * Tokens are case-insensitive uppercase double-hash placeholders (e.g. ##NAME##).
     *
     * @param array<string, string|int|float|null> $tokens
     */
    public function renderBody(array $tokens = [], ?string $customMessage = null): string
    {
        $body = !empty($customMessage) ? $customMessage : $this->text;

        foreach ($tokens as $key => $value) {
            $placeholder = '##' . strtoupper($key) . '##';
            $body = str_replace($placeholder, (string) ($value ?? ''), $body);
        }

        return $body;
    }
}
