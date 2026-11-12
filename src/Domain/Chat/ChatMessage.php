<?php

declare(strict_types=1);

namespace App\Domain\Chat;

/**
 * ChatMessage Domain Entity.
 * Represents a single inbound or outbound message within a Chat thread.
 * Mapped to database table 'chat_message'.
 */
final class ChatMessage
{
    public const TYPE_RECEIVED = 'received';
    public const TYPE_SENT = 'sent';

    /**
     * @param int $id
     * @param int $accountId
     * @param int $serviceConfigId
     * @param int $chatId
     * @param string $type
     * @param string|null $objId
     * @param string|null $customerId
     * @param string|null $customerName
     * @param string $chatText
     * @param string|null $chatExtra
     * @param string $chatDate
     * @param int|null $userId
     * @param int|null $createdAt
     * @param int|null $updatedAt
     */
    public function __construct(
        private int $id,
        private int $accountId,
        private int $serviceConfigId,
        private int $chatId,
        private string $type,
        private ?string $objId,
        private ?string $customerId,
        private ?string $customerName,
        private string $chatText,
        private ?string $chatExtra,
        private string $chatDate,
        private ?int $userId = null,
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
            chatId: (int) ($row['chat_id'] ?? 0),
            type: (string) ($row['type'] ?? self::TYPE_RECEIVED),
            objId: isset($row['obj_id']) ? (string) $row['obj_id'] : null,
            customerId: isset($row['customer_id']) ? (string) $row['customer_id'] : null,
            customerName: isset($row['customer_name']) ? (string) $row['customer_name'] : null,
            chatText: (string) ($row['chat_text'] ?? ''),
            chatExtra: isset($row['chat_extra']) ? (string) $row['chat_extra'] : null,
            chatDate: (string) ($row['chat_date'] ?? date('Y-m-d H:i:s')),
            userId: isset($row['user_id']) ? (int) $row['user_id'] : null,
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

    public function getChatId(): int
    {
        return $this->chatId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isReceived(): bool
    {
        return $this->type === self::TYPE_RECEIVED;
    }

    public function isSent(): bool
    {
        return $this->type === self::TYPE_SENT;
    }

    public function getObjId(): ?string
    {
        return $this->objId;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function getCustomerName(): ?string
    {
        return $this->customerName;
    }

    public function getChatText(): string
    {
        return $this->chatText;
    }

    public function getChatExtra(): ?string
    {
        return $this->chatExtra;
    }

    public function getChatDate(): string
    {
        return $this->chatDate;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
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

