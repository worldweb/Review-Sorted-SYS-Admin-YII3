<?php

declare(strict_types=1);

namespace App\Domain\WhatsApp;

/**
 * WhatsApp Queue Domain Entity.
 * Represents an outbound message queued for transmission via Meta WhatsApp Cloud API.
 * Mapped to database table 'whatsapp_queue'.
 */
final class WhatsAppQueue
{
    public const STATUS_FAILED = -1;
    public const STATUS_QUEUED = 0;
    public const STATUS_SENT = 1;
    public const STATUS_PROCESSING = 2;

    public const STATUS_LABELS = [
        self::STATUS_FAILED => 'Failed',
        self::STATUS_QUEUED => 'Queued',
        self::STATUS_SENT => 'Sent',
        self::STATUS_PROCESSING => 'Processing',
    ];

    public function __construct(
        private int $id,
        private ?int $accountId,
        private string $phone,
        private string $message,
        private int $status,
        private ?string $sendAt = null,
        private ?string $sentAt = null,
        private ?string $lockedAt = null,
        private ?string $providerMessageId = null,
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
            phone: (string) ($row['phone'] ?? ''),
            message: (string) ($row['message'] ?? ''),
            status: (int) ($row['status'] ?? self::STATUS_QUEUED),
            sendAt: isset($row['send_at']) && $row['send_at'] !== '' ? (string) $row['send_at'] : null,
            sentAt: isset($row['sent_at']) && $row['sent_at'] !== '' ? (string) $row['sent_at'] : null,
            lockedAt: isset($row['locked_at']) && $row['locked_at'] !== '' ? (string) $row['locked_at'] : null,
            providerMessageId: isset($row['provider_message_id']) && $row['provider_message_id'] !== ''
                ? (string) $row['provider_message_id']
                : null,
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

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getStatusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? 'Unknown';
    }

    public function getSendAt(): ?string
    {
        return $this->sendAt;
    }

    public function getSentAt(): ?string
    {
        return $this->sentAt;
    }

    public function getLockedAt(): ?string
    {
        return $this->lockedAt;
    }

    public function getProviderMessageId(): ?string
    {
        return $this->providerMessageId;
    }

    public function getCreatedAt(): ?int
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?int
    {
        return $this->updatedAt;
    }

    public function isQueued(): bool
    {
        return $this->status === self::STATUS_QUEUED;
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isTimeToSend(): bool
    {
        if (empty($this->sendAt)) {
            return true;
        }

        return strtotime($this->sendAt) <= time();
    }

    public static function createQueued(
        int $accountId,
        string $phone,
        string $message,
        ?string $sendAt = null,
    ): self {
        $now = time();
        return new self(
            id: 0,
            accountId: $accountId,
            phone: $phone,
            message: $message,
            status: self::STATUS_QUEUED,
            sendAt: $sendAt,
            sentAt: null,
            lockedAt: null,
            providerMessageId: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }
}
