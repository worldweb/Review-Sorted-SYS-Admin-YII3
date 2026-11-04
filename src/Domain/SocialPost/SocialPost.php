<?php

declare(strict_types=1);

namespace App\Domain\SocialPost;

/**
 * SocialPost Domain Entity.
 * Represents an outbound social post composer draft, scheduled, or published message.
 * Mapped to database table 'social_post'.
 */
final class SocialPost
{
    public const MEDIA_NONE = 'none';
    public const MEDIA_IMAGE = 'image';
    public const MEDIA_VIDEO = 'video';

    public const TYPE_IMMEDIATE = 'immediate';
    public const TYPE_SCHEDULED = 'scheduled';

    /**
     * @param int $id
     * @param int $accountId
     * @param int $userId
     * @param string $content
     * @param string $mediaType
     * @param string|null $mediaUrl
     * @param string $postType
     * @param string|null $scheduledAt
     * @param int $createdAt
     * @param int $updatedAt
     * @param array<int, SocialPostDestination> $destinations
     * @param string|null $authorEmail
     * @param string|null $accountBusinessName
     */
    public function __construct(
        private int $id,
        private int $accountId,
        private int $userId,
        private string $content,
        private string $mediaType = self::MEDIA_NONE,
        private ?string $mediaUrl = null,
        private string $postType = self::TYPE_IMMEDIATE,
        private ?string $scheduledAt = null,
        private int $createdAt = 0,
        private int $updatedAt = 0,
        private array $destinations = [],
        private ?string $authorEmail = null,
        private ?string $accountBusinessName = null,
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
            userId: (int) ($row['user_id'] ?? 0),
            content: (string) ($row['content'] ?? ''),
            mediaType: (string) ($row['media_type'] ?? self::MEDIA_NONE),
            mediaUrl: isset($row['media_url']) ? (string) $row['media_url'] : null,
            postType: (string) ($row['post_type'] ?? self::TYPE_IMMEDIATE),
            scheduledAt: isset($row['scheduled_at']) ? (string) $row['scheduled_at'] : null,
            createdAt: (int) ($row['created_at'] ?? 0),
            updatedAt: (int) ($row['updated_at'] ?? 0),
            authorEmail: isset($row['author_email']) ? (string) $row['author_email'] : null,
            accountBusinessName: isset($row['account_business_name']) ? (string) $row['account_business_name'] : null,
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

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    public function hasMedia(): bool
    {
        return $this->mediaType !== self::MEDIA_NONE && !empty($this->mediaUrl);
    }

    public function getMediaUrl(): ?string
    {
        return $this->mediaUrl;
    }

    public function getPostType(): string
    {
        return $this->postType;
    }

    public function isScheduled(): bool
    {
        return $this->postType === self::TYPE_SCHEDULED;
    }

    public function getScheduledAt(): ?string
    {
        return $this->scheduledAt;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): int
    {
        return $this->updatedAt;
    }

    /**
     * @return array<int, SocialPostDestination>
     */
    public function getDestinations(): array
    {
        return $this->destinations;
    }

    /**
     * @param array<int, SocialPostDestination> $destinations
     */
    public function withDestinations(array $destinations): self
    {
        $clone = clone $this;
        $clone->destinations = $destinations;
        return $clone;
    }

    public function getAuthorEmail(): ?string
    {
        return $this->authorEmail;
    }

    public function getAccountBusinessName(): ?string
    {
        return $this->accountBusinessName;
    }

    public function isEditable(): bool
    {
        if ($this->postType !== self::TYPE_SCHEDULED) {
            return false;
        }

        if (empty($this->scheduledAt) || strtotime($this->scheduledAt) <= time()) {
            return false;
        }

        if (empty($this->destinations)) {
            return false;
        }

        foreach ($this->destinations as $dest) {
            if ($dest->getStatus() !== SocialPostDestination::STATUS_QUEUED) {
                return false;
            }
        }

        return true;
    }

    public function isCancelled(): bool
    {
        if (empty($this->destinations)) {
            return false;
        }

        foreach ($this->destinations as $dest) {
            if ($dest->getStatus() !== SocialPostDestination::STATUS_FAILED || $dest->getErrorMessage() !== 'Cancelled by user') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{total: int, published: int, processing: int, queued: int, failed: int, cancelled: int}
     */
    public function getPublishingProgress(): array
    {
        $total = count($this->destinations);
        $published = 0;
        $processing = 0;
        $queued = 0;
        $failed = 0;
        $cancelled = 0;

        foreach ($this->destinations as $dest) {
            if ($dest->getErrorMessage() === 'Cancelled by user') {
                $cancelled++;
            } elseif ($dest->getStatus() === SocialPostDestination::STATUS_PUBLISHED) {
                $published++;
            } elseif ($dest->getStatus() === SocialPostDestination::STATUS_PROCESSING) {
                $processing++;
            } elseif ($dest->getStatus() === SocialPostDestination::STATUS_FAILED) {
                $failed++;
            } else {
                $queued++;
            }
        }

        return [
            'total' => $total,
            'published' => $published,
            'processing' => $processing,
            'queued' => $queued,
            'failed' => $failed,
            'cancelled' => $cancelled,
        ];
    }

    public function getDerivedStatus(): string
    {
        if (empty($this->destinations)) {
            return 'no_destinations';
        }

        if ($this->isCancelled()) {
            return 'cancelled';
        }

        $statuses = array_map(static fn($d) => $d->getStatus(), $this->destinations);
        $unique = array_unique($statuses);

        if (count($unique) === 1 && reset($unique) === SocialPostDestination::STATUS_PUBLISHED) {
            return SocialPostDestination::STATUS_PUBLISHED;
        }

        if (in_array(SocialPostDestination::STATUS_PUBLISHED, $statuses, true) && (in_array(SocialPostDestination::STATUS_FAILED, $statuses, true) || in_array(SocialPostDestination::STATUS_QUEUED, $statuses, true))) {
            return 'partially_published';
        }

        if (count($unique) === 1 && reset($unique) === SocialPostDestination::STATUS_FAILED) {
            return SocialPostDestination::STATUS_FAILED;
        }

        if (in_array(SocialPostDestination::STATUS_PROCESSING, $statuses, true)) {
            return SocialPostDestination::STATUS_PROCESSING;
        }

        if ($this->postType === self::TYPE_SCHEDULED && !empty($this->scheduledAt) && strtotime($this->scheduledAt) > time()) {
            return self::TYPE_SCHEDULED;
        }

        return SocialPostDestination::STATUS_QUEUED;
    }

    public function getOverallStatus(): string
    {
        return $this->getDerivedStatus();
    }
}
