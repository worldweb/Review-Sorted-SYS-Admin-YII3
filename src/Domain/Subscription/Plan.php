<?php

declare(strict_types=1);

namespace App\Domain\Subscription;

/**
 * Plan Domain Entity.
 * Represents a pricing plan / tier.
 * Mapped to database table 'plans'.
 */
final class Plan
{
    public const STATUS_ACTIVE = '1';
    public const STATUS_INACTIVE = '0';

    /**
     * @param int $id
     * @param string $planName
     * @param string $planPrice
     * @param string $planLimit
     * @param string $planFeatures
     * @param string $status
     * @param int|null $createdAt
     * @param int|null $updatedAt
     */
    public function __construct(
        private int $id,
        private string $planName,
        private string $planPrice,
        private string $planLimit,
        private string $planFeatures,
        private string $status = self::STATUS_ACTIVE,
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
            planName: (string) ($row['plan_name'] ?? ''),
            planPrice: (string) ($row['plan_price'] ?? '0.00'),
            planLimit: (string) ($row['plan_limit'] ?? ''),
            planFeatures: (string) ($row['plan_features'] ?? ''),
            status: (string) ($row['status'] ?? self::STATUS_ACTIVE),
            createdAt: isset($row['created_at']) ? (int) $row['created_at'] : null,
            updatedAt: isset($row['updated_at']) ? (int) $row['updated_at'] : null,
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPlanName(): string
    {
        return $this->planName;
    }

    public function getPlanPrice(): string
    {
        return $this->planPrice;
    }

    public function getFormattedPrice(): string
    {
        return '$' . number_format((float) $this->planPrice, 2);
    }

    public function getPlanLimit(): string
    {
        return $this->planLimit;
    }

    public function getPlanFeatures(): string
    {
        return $this->planFeatures;
    }

    /**
     * @return array<int, string>
     */
    public function getFeaturesList(): array
    {
        $raw = explode("\n", str_replace(["\r\n", "\r", ','], "\n", $this->planFeatures));
        return array_values(array_filter(array_map('trim', $raw)));
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE || $this->status === 'active';
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
