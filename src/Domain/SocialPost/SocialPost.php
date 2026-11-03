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

    public function getOverallStatus(): string
    {
        if (empty($this->destinations)) {
            return $this->isScheduled() ? 'scheduled' : 'draft';
        }

        $allPublished = true;
        $anyFailed = false;
        foreach ($this->destinations as $d) {
            if ($d->isFailed()) {
                $anyFailed = true;
            }
            if (!$d->isPublished()) {
                $allPublished = false;
            }
        }

        if ($allPublished) {
            return 'published';
        }
        if ($anyFailed) {
            return 'partial_failure';
        }
        return 'queued';
    }
}
