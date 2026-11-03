<?php

declare(strict_types=1);

namespace App\Domain\Email;

/**
 * Email Address Contact Domain Entity.
 * Represents a saved recipient in the account contact book.
 * Mapped to database table 'email_addresses'.
 */
final class EmailAddress
{
    public const TYPE_TO = 'to';
    public const TYPE_CC = 'cc';
    public const TYPE_BCC = 'bcc';

    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;

    public function __construct(
        private int $id,
        private ?int $accountId,
        private string $name,
        private string $email,
        private string $type,
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
            email: (string) ($row['email'] ?? ''),
            type: in_array((string) ($row['type'] ?? ''), [self::TYPE_TO, self::TYPE_CC, self::TYPE_BCC], true)
                ? (string) $row['type']
                : self::TYPE_TO,
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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getType(): string
    {
        return $this->type;
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
}
