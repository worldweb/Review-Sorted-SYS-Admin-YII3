<?php

declare(strict_types=1);

namespace App\Domain\WhatsApp;

/**
 * WhatsApp Address Contact Domain Entity.
 * Represents a saved phone contact in the account WhatsApp directory.
 * Mapped to database table 'whatsapp_addresses'.
 */
final class WhatsAppAddress
{
    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;

    public function __construct(
        private int $id,
        private ?int $accountId,
        private string $name,
        private string $phone,
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
            name: (string) ($row['name'] ?? ''),
            phone: (string) ($row['phone'] ?? ''),
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getPhone(): string
    {
        return $this->phone;
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

    /**
     * Return normalized E.164 phone representation.
     */
    public function getNormalizedPhone(): string
    {
        $cleaned = preg_replace('/[^0-9+]/', '', $this->phone) ?? '';
        if (str_starts_with($cleaned, '04') && strlen($cleaned) === 10) {
            // Australian mobile shorthand (04xx xxx xxx -> +614xx xxx xxx)
            return '+61' . substr($cleaned, 1);
        }
        if (!str_starts_with($cleaned, '+') && strlen($cleaned) >= 10) {
            return '+' . $cleaned;
        }
        return $cleaned;
    }
}
