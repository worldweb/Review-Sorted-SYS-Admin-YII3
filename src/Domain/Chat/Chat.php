<?php

declare(strict_types=1);

namespace App\Domain\Chat;

/**
 * Chat Domain Entity.
 * Represents a conversation thread between a customer (Facebook Messenger / Instagram Direct)
 * and a connected service configuration (Page).
 * Mapped to database table 'chat'.
 */
final class Chat
{
    public const STATUS_NOT_READ = 0;
    public const STATUS_READ = 1;
    public const STATUS_REPLIED = 9;

    /**
     * @param int $id
     * @param int $accountId
     * @param int $serviceConfigId
     * @param string $customerId
     * @param string $customerName
     * @param string $startDate
     * @param int $read
     * @param int|null $createdAt
     * @param int|null $updatedAt
     */
    public function __construct(
        private int $id,
        private int $accountId,
        private int $serviceConfigId,
        private string $customerId,
        private string $customerName,
        private string $startDate,
        private int $read = self::STATUS_NOT_READ,
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
            id: (int) ($row['id'] ?? 0),
            accountId: (int) ($row['account_id'] ?? 0),
            serviceConfigId: (int) ($row['service_config_id'] ?? 0),
            customerId: (string) ($row['customer_id'] ?? ''),
            customerName: (string) ($row['customer_name'] ?? ''),
            startDate: (string) ($row['start_date'] ?? date('Y-m-d H:i:s')),
            read: (int) ($row['read'] ?? self::STATUS_NOT_READ),
            createdAt: isset($row['created_at']) ? (int) $row['created_at'] : null,
            updatedAt: isset($row['updated_at']) ? (int) $row['updated_at'] : null,
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function getServiceConfigId(): int
    {
        return $this->serviceConfigId;
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function getStartDate(): string
    {
        return $this->startDate;
    }

    public function getRead(): int
    {
        return $this->read;
    }

    public function isRead(): bool
    {
        return $this->read === self::STATUS_READ;
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

