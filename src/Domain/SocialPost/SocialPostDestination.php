<?php

declare(strict_types=1);

namespace App\Domain\SocialPost;

/**
 * SocialPostDestination Domain Entity.
 * Represents an individual delivery target (e.g. Facebook Page, Instagram Account, Google Location)
 * for a SocialPost.
 * Mapped to database table 'social_post_destination'.
 */
final class SocialPostDestination
{
    public const PLATFORM_FACEBOOK = 'facebook';
    public const PLATFORM_INSTAGRAM = 'instagram';
    public const PLATFORM_GOOGLE = 'google';

    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_FAILED = 'failed';

    /**
     * @param int $id
     * @param int $socialPostId
     * @param int $accountId
     * @param string $platform
     * @param int|null $serviceConfigId
     * @param int|null $subServiceId
     * @param string $status
     * @param string $scheduledAt
     * @param string|null $publishedAt
     * @param string|null $remotePostId
     * @param string|null $errorMessage
     * @param int $retryCount
     * @param string|null $lockedAt
     * @param int $createdAt
     * @param int $updatedAt
     * @param string|null $destinationName
     */
    public function __construct(
        private int $id,
        private int $socialPostId,
        private int $accountId,
        private string $platform,
        private ?int $serviceConfigId,
        private ?int $subServiceId,
        private string $status,
        private string $scheduledAt,
        private ?string $publishedAt = null,
        private ?string $remotePostId = null,
        private ?string $errorMessage = null,
        private int $retryCount = 0,
        private ?string $lockedAt = null,
        private int $createdAt = 0,
        private int $updatedAt = 0,
        private ?string $destinationName = null,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            socialPostId: (int) ($row['social_post_id'] ?? 0),
            accountId: (int) ($row['account_id'] ?? 0),
            platform: (string) ($row['platform'] ?? self::PLATFORM_FACEBOOK),
            serviceConfigId: isset($row['service_config_id']) ? (int) $row['service_config_id'] : null,
            subServiceId: isset($row['sub_service_id']) ? (int) $row['sub_service_id'] : null,
            status: (string) ($row['status'] ?? self::STATUS_QUEUED),
            scheduledAt: (string) ($row['scheduled_at'] ?? ''),
            publishedAt: isset($row['published_at']) ? (string) $row['published_at'] : null,
            remotePostId: isset($row['remote_post_id']) ? (string) $row['remote_post_id'] : null,
            errorMessage: isset($row['error_message']) ? (string) $row['error_message'] : null,
            retryCount: (int) ($row['retry_count'] ?? 0),
            lockedAt: isset($row['locked_at']) ? (string) $row['locked_at'] : null,
            createdAt: (int) ($row['created_at'] ?? 0),
            updatedAt: (int) ($row['updated_at'] ?? 0),
            destinationName: isset($row['destination_name']) ? (string) $row['destination_name'] : null,
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSocialPostId(): int
    {
        return $this->socialPostId;
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function getServiceConfigId(): ?int
    {
        return $this->serviceConfigId;
    }

    public function getSubServiceId(): ?int
    {
        return $this->subServiceId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isQueued(): bool
    {
        return $this->status === self::STATUS_QUEUED;
    }

    public function getScheduledAt(): string
    {
        return $this->scheduledAt;
    }

    public function getPublishedAt(): ?string
    {
        return $this->publishedAt;
    }

    public function getRemotePostId(): ?string
    {
        return $this->remotePostId;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    public function getLockedAt(): ?string
    {
        return $this->lockedAt;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): int
    {
        return $this->updatedAt;
    }

    public function getDestinationName(): ?string
    {
        return $this->destinationName;
    }
}
