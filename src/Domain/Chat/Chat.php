<?php

declare(strict_types=1);

namespace App\Domain\Chat;

/**
 * Chat Domain Entity.
 * Represents a conversation thread between a customer and the business.
 * Mapped to database table 'chat'.
 */
final class Chat
{
    public const READ_NO = 0;
    public const READ_YES = 1;

    /**
     * @param int $id
     * @param int|null $accountId
     * @param int $serviceConfigId
     * @param string $customerId
     * @param string $customerName
     * @param string $startDate
     * @param int $read
     * @param int|null $createdAt
     * @param int|null $updatedAt
     * @param array<int, ChatMessage> $messages
     * @param string|null $serviceLabel
     */
    public function __construct(
        private int $id,
        private ?int $accountId,
        private int $serviceConfigId,
        private string $customerId,
        private string $customerName,
        private string $startDate,
        private int $read = self::READ_NO,
        private ?int $createdAt = null,
        private ?int $updatedAt = null,
        private array $messages = [],
        private ?string $serviceLabel = null,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            accountId: isset($row['account_id']) ? (int) $row['account_id'] : null,
            serviceConfigId: (int) ($row['service_config_id'] ?? 0),
            customerId: (string) ($row['customer_id'] ?? ''),
            customerName: (string) ($row['customer_name'] ?? 'Customer'),
            startDate: (string) ($row['start_date'] ?? ''),
            read: (int) ($row['read'] ?? self::READ_NO),
            createdAt: isset($row['created_at']) ? (int) $row['created_at'] : null,
            updatedAt: isset($row['updated_at']) ? (int) $row['updated_at'] : null,
            serviceLabel: isset($row['service_label']) ? (string) $row['service_label'] : null,
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
        return !empty($this->customerName) ? $this->customerName : 'Anonymous';
    }

    public function getStartDate(): string
    {
        return $this->startDate;
    }

    public function isRead(): bool
    {
        return $this->read === self::READ_YES;
    }

    public function getServiceLabel(): ?string
    {
        return $this->serviceLabel;
    }

    /**
     * @return array<int, ChatMessage>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * @param array<int, ChatMessage> $messages
     */
    public function withMessages(array $messages): self
    {
        $clone = clone $this;
        $clone->messages = $messages;
        return $clone;
    }
}
