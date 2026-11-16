<?php

declare(strict_types=1);

namespace App\Domain\Notification;

/**
 * Domain entity representing an in-app user notification.
 */
final class Notification
{
    public function __construct(
        private int $id,
        private int $userId,
        private int $accountId,
        private string $type,
        private string $title,
        private ?string $message = null,
        private ?string $actionUrl = null,
        private bool $isRead = false,
        private ?int $readAt = null,
        private int $createdAt = 0,
        private ?string $accountName = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getActionUrl(): ?string
    {
        return $this->actionUrl;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function getReadAt(): ?int
    {
        return $this->readAt;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function getAccountName(): ?string
    {
        return $this->accountName;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'account_id' => $this->accountId,
            'account_name' => $this->accountName,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'is_read' => $this->isRead,
            'read_at' => $this->readAt,
            'created_at' => $this->createdAt,
        ];
    }
}

