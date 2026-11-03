<?php

declare(strict_types=1);

namespace App\Domain\Review;

/**
 * Review Domain Entity.
 * Represents a customer review, mention, or social feedback record.
 * Read-only domain representation with automated spam analysis.
 */
final class Review
{
    public const STATUS_READ     = 1;
    public const STATUS_NOT_READ = 0;
    public const STATUS_REPLIED  = 9;

    public const TYPE_REVIEW     = 'REVIEW';
    public const TYPE_MENTION    = 'MENTION';
    public const TYPE_POST_REPLY = 'POST_REPLY';

    public const SPAM_NORMAL     = 'normal';
    public const SPAM_SUSPICIOUS = 'suspicious';
    public const SPAM_HIGH_RISK  = 'high_risk';

    public function __construct(
        private int $id,
        private ?int $accountId,
        private int $serviceConfigId,
        private ?int $subServiceId,
        private string $objId,
        private string $customerId,
        private string $type,
        private string $customerName,
        private ?string $customerEmail,
        private ?string $customerPhone,
        private ?string $customerState,
        private ?string $customerRegion,
        private ?float $reviewRate,
        private string $reviewText,
        private ?string $reviewRecommend,
        private ?string $reviewService,
        private string $reviewDate,
        private ?string $reviewExtra,
        private int $read,
        private int $spamScore,
        private string $spamStatus,
        private ?int $createdAt = null,
        private ?int $updatedAt = null,
        // Joined metadata for UI presentation
        private ?string $serviceConfigLabel = null,
        private ?string $serviceLabel = null,
        private ?string $subServiceName = null,
        private ?string $subServiceType = null,
        private ?int $replyCount = 0,
        private ?bool $isServiceReady = true,
    ) {
    }

    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            accountId: isset($row['account_id']) && $row['account_id'] !== null ? (int) $row['account_id'] : null,
            serviceConfigId: (int) ($row['service_config_id'] ?? 0),
            subServiceId: isset($row['sub_service_id']) && $row['sub_service_id'] !== null ? (int) $row['sub_service_id'] : null,
            objId: (string) ($row['obj_id'] ?? ''),
            customerId: (string) ($row['customer_id'] ?? ''),
            type: (string) ($row['type'] ?? self::TYPE_REVIEW),
            customerName: (string) ($row['customer_name'] ?? 'Anonymous'),
            customerEmail: isset($row['customer_email']) ? (string) $row['customer_email'] : null,
            customerPhone: isset($row['customer_phone']) ? (string) $row['customer_phone'] : null,
            customerState: isset($row['customer_state']) ? (string) $row['customer_state'] : null,
            customerRegion: isset($row['customer_region']) ? (string) $row['customer_region'] : null,
            reviewRate: isset($row['review_rate']) && $row['review_rate'] !== null ? (float) $row['review_rate'] : null,
            reviewText: (string) ($row['review_text'] ?? ''),
            reviewRecommend: isset($row['review_recommend']) ? (string) $row['review_recommend'] : null,
            reviewService: isset($row['review_service']) ? (string) $row['review_service'] : null,
            reviewDate: (string) ($row['review_date'] ?? ''),
            reviewExtra: isset($row['review_extra']) ? (string) $row['review_extra'] : null,
            read: (int) ($row['read'] ?? self::STATUS_NOT_READ),
            spamScore: (int) ($row['spam_score'] ?? 0),
            spamStatus: (string) ($row['spam_status'] ?? self::SPAM_NORMAL),
            createdAt: isset($row['created_at']) && $row['created_at'] !== null ? (int) $row['created_at'] : null,
            updatedAt: isset($row['updated_at']) && $row['updated_at'] !== null ? (int) $row['updated_at'] : null,
            serviceConfigLabel: isset($row['service_config_label']) ? (string) $row['service_config_label'] : null,
            serviceLabel: isset($row['service_label']) ? (string) $row['service_label'] : null,
            subServiceName: isset($row['sub_service_name']) ? (string) $row['sub_service_name'] : null,
            subServiceType: isset($row['sub_service_type']) ? (string) $row['sub_service_type'] : null,
            replyCount: isset($row['reply_count']) ? (int) $row['reply_count'] : 0,
            isServiceReady: isset($row['is_service_ready']) ? (bool) $row['is_service_ready'] : true,
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

    public function getSubServiceId(): ?int
    {
        return $this->subServiceId;
    }

    public function getObjId(): string
    {
        return $this->objId;
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    public function getCustomerPhone(): ?string
    {
        return $this->customerPhone;
    }

    public function getCustomerState(): ?string
    {
        return $this->customerState;
    }

    public function getCustomerRegion(): ?string
    {
        return $this->customerRegion;
    }

    public function getReviewRate(): ?float
    {
        return $this->reviewRate;
    }

    public function getReviewText(): string
    {
        return $this->reviewText;
    }

    public function getReviewRecommend(): ?string
    {
        return $this->reviewRecommend;
    }

    public function getReviewService(): ?string
    {
        return $this->reviewService;
    }

    public function getReviewDate(): string
    {
        return $this->reviewDate;
    }

    public function getReviewExtra(): ?string
    {
        return $this->reviewExtra;
    }

    public function getRead(): int
    {
        return $this->read;
    }

    public function getSpamScore(): int
    {
        return $this->spamScore;
    }

    public function getSpamStatus(): string
    {
        return $this->spamStatus;
    }

    public function getCreatedAt(): ?int
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?int
    {
        return $this->updatedAt;
    }

    public function getServiceConfigLabel(): ?string
    {
        return $this->serviceConfigLabel;
    }

    public function getServiceLabel(): ?string
    {
        return $this->serviceLabel;
    }

    public function getSubServiceName(): ?string
    {
        return $this->subServiceName;
    }

    public function getSubServiceType(): ?string
    {
        return $this->subServiceType;
    }

    public function getReplyCount(): int
    {
        return $this->replyCount ?? 0;
    }

    public function isServiceReady(): bool
    {
        return $this->isServiceReady ?? true;
    }

    public function isRead(): bool
    {
        return $this->read === self::STATUS_READ;
    }

    public function isReplied(): bool
    {
        return $this->getReplyCount() > 0;
    }

    public function getStatusLabel(): string
    {
        if ($this->isReplied()) {
            return 'REPLIED';
        }
        if ($this->isRead()) {
            return 'READ';
        }
        return 'UNREAD';
    }

    public function getTypeLabel(): string
    {
        if ($this->type !== self::TYPE_POST_REPLY) {
            return ucfirst(strtolower($this->type));
        }
        return 'Post reply';
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtra(): array
    {
        if ($this->reviewExtra === null || trim($this->reviewExtra) === '') {
            return [];
        }

        $decoded = json_decode($this->reviewExtra, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Converts review fields to key-value array for bulk template token replacement.
     * Matches legacy Yii 2 Review::toArray().
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'id' => (string) $this->id,
            'account_id' => (string) ($this->accountId ?? ''),
            'service_config_id' => (string) $this->serviceConfigId,
            'sub_service_id' => (string) ($this->subServiceId ?? ''),
            'obj_id' => $this->objId,
            'customer_id' => $this->customerId,
            'type' => $this->type,
            'customer_name' => $this->customerName,
            'customer_email' => $this->customerEmail ?? '',
            'customer_phone' => $this->customerPhone ?? '',
            'customer_state' => $this->customerState ?? '',
            'customer_region' => $this->customerRegion ?? '',
            'review_rate' => $this->reviewRate !== null ? (string) $this->reviewRate : '',
            'review_text' => $this->reviewText,
            'review_recommend' => $this->reviewRecommend ?? '',
            'review_service' => $this->reviewService ?? '',
            'review_date' => $this->reviewDate,
            'service_config_label' => $this->serviceConfigLabel ?? '',
            'service_label' => $this->serviceLabel ?? '',
        ];
    }

    /**
     * Exact replication of legacy Yii 2 spam scoring algorithm from common\models\Review.
     */
    public function calculateSpamScore(): int
    {
        $score = 0;
        $normalized = trim($this->reviewText);

        // 1. Very short reviews (< 15 chars)
        if (mb_strlen($normalized) < 15) {
            $score += 1;
        }

        // 2. Contains URL
        if (preg_match('/http|www/i', $normalized) === 1) {
            $score += 2;
        }

        // 3. ALL CAPS (at least 80% uppercase among letters)
        $lettersOnly = preg_replace('/[^A-Za-z]+/u', '', $normalized);
        if ($lettersOnly !== null && $lettersOnly !== '') {
            $upper = preg_replace('/[^A-Z]+/u', '', $lettersOnly) ?? '';
            $ratio = mb_strlen($upper) / mb_strlen($lettersOnly);
            if ($ratio >= 0.8) {
                $score += 1;
            }
        }

        // 4. Low rating with strong negative language
        $lower = mb_strtolower($normalized);
        if ($this->reviewRate !== null && ((int) $this->reviewRate) === 1) {
            if (preg_match('/\b(scam|fake|fraud|worst)\b/u', $lower) === 1) {
                $score += 1;
            }
        }

        // 5. Excessive punctuation
        if (str_contains($normalized, '!!!') || str_contains($normalized, '???')) {
            $score += 1;
        }

        return $score;
    }

    /**
     * Determines spam status category from score.
     */
    public function calculateSpamStatus(): string
    {
        $score = $this->calculateSpamScore();
        if ($score >= 4) {
            return self::SPAM_HIGH_RISK;
        }
        if ($score >= 2) {
            return self::SPAM_SUSPICIOUS;
        }
        return self::SPAM_NORMAL;
    }
}
