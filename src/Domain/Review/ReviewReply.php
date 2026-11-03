<?php

declare(strict_types=1);

namespace App\Domain\Review;

/**
 * ReviewReply Domain Entity.
 * Represents a reply or response in a review conversation thread.
 */
final class ReviewReply
{
    public const TYPE_RECEIVED = 'received';
    public const TYPE_SENT = 'sent';

    public function __construct(
        private int $id,
        private ?int $accountId,
        private ?int $userId,
        private int $serviceConfigId,
        private int $reviewId,
        private string $objId,
        private ?string $customerId,
        private ?string $customerName,
        private string $replyText,
        private string $replyDate,
        private ?string $replyExtra = null,
        private ?int $createdAt = null,
        private ?int $updatedAt = null,
        // Joined metadata for UI presentation
        private ?string $userName = null,
        private ?string $serviceConfigLabel = null,
    ) {
    }

    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            accountId: isset($row['account_id']) && $row['account_id'] !== null ? (int) $row['account_id'] : null,
            userId: isset($row['user_id']) && $row['user_id'] !== null ? (int) $row['user_id'] : null,
            serviceConfigId: (int) ($row['service_config_id'] ?? 0),
            reviewId: (int) ($row['review_id'] ?? 0),
            objId: (string) ($row['obj_id'] ?? ''),
            customerId: isset($row['customer_id']) ? (string) $row['customer_id'] : null,
            customerName: isset($row['customer_name']) ? (string) $row['customer_name'] : null,
            replyText: (string) ($row['reply_text'] ?? ''),
            replyDate: (string) ($row['reply_date'] ?? ''),
            replyExtra: isset($row['reply_extra']) ? (string) $row['reply_extra'] : null,
            createdAt: isset($row['created_at']) && $row['created_at'] !== null ? (int) $row['created_at'] : null,
            updatedAt: isset($row['updated_at']) && $row['updated_at'] !== null ? (int) $row['updated_at'] : null,
            userName: isset($row['user_name']) ? (string) $row['user_name'] : null,
            serviceConfigLabel: isset($row['service_config_label']) ? (string) $row['service_config_label'] : null,
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

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getServiceConfigId(): int
    {
        return $this->serviceConfigId;
    }

    public function getReviewId(): int
    {
        return $this->reviewId;
    }

    public function getObjId(): string
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

    public function getReplyText(): string
    {
        return $this->replyText;
    }

    public function getReplyDate(): string
    {
        return $this->replyDate;
    }

    public function getReplyExtra(): ?string
    {
        return $this->replyExtra;
    }

    public function getCreatedAt(): ?int
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?int
    {
        return $this->updatedAt;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function getServiceConfigLabel(): ?string
    {
        return $this->serviceConfigLabel;
    }

    /**
     * Returns reply type: 'sent' if sent by an internal team member, 'received' if from the customer.
     */
    public function getType(): string
    {
        return ($this->userId !== null) ? self::TYPE_SENT : self::TYPE_RECEIVED;
    }

    /**
     * Resolves the display author name matching Yii 2 logic:
     * If internal user replied, returns the business page label or user name.
     * Otherwise returns the customer name.
     */
    public function getAuthorLabel(): string
    {
        if ($this->userId !== null) {
            return $this->serviceConfigLabel ?? $this->userName ?? 'Business Representative';
        }

        return !empty(trim((string) $this->customerName)) ? $this->customerName : 'Customer';
    }
}
