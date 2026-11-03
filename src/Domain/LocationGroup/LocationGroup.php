<?php

declare(strict_types=1);

namespace App\Domain\LocationGroup;

/**
 * Location Group Domain Entity.
 * Represents an administrative cluster of connected social destinations (Google, Facebook, Instagram)
 * owned by a specific tenant account.
 * Mapped to database table 'location_group'.
 */
final class LocationGroup
{
    public const STATUS_INACTIVE = 0;
    public const STATUS_ACTIVE = 1;

    /**
     * @param int $id
     * @param int $accountId
     * @param string $name
     * @param string|null $description
     * @param int $status
     * @param int $createdAt
     * @param int $updatedAt
     * @param string|null $accountBusinessName Joined account business name
     * @param int|null $destinationCount Calculated count of assigned destinations
     * @param array<int, ConnectedDestination> $destinations Loaded destination list
     */
    public function __construct(
        private int $id,
        private int $accountId,
        private string $name,
        private ?string $description,
        private int $status,
        private int $createdAt,
        private int $updatedAt,
        private ?string $accountBusinessName = null,
        private ?int $destinationCount = null,
        private array $destinations = [],
    ) {
    }

    /**
     * Reconstitute a LocationGroup domain entity from a database row.
     *
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            accountId: (int) $row['account_id'],
            name: (string) ($row['name'] ?? ''),
            description: isset($row['description']) && $row['description'] !== '' ? (string) $row['description'] : null,
            status: isset($row['status']) ? (int) $row['status'] : self::STATUS_ACTIVE,
            createdAt: (int) ($row['created_at'] ?? 0),
            updatedAt: (int) ($row['updated_at'] ?? 0),
            accountBusinessName: isset($row['account_business_name']) && $row['account_business_name'] !== ''
                ? (string) $row['account_business_name']
                : null,
            destinationCount: isset($row['destination_count']) ? (int) $row['destination_count'] : null,
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getStatusLabel(): string
    {
        return $this->status === self::STATUS_ACTIVE ? 'Active' : 'Inactive';
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): int
    {
        return $this->updatedAt;
    }

    public function getAccountBusinessName(): ?string
    {
        return $this->accountBusinessName;
    }

    public function getDestinationCount(): int
    {
        return $this->destinationCount ?? count($this->destinations);
    }

    /**
     * @return array<int, ConnectedDestination>
     */
    public function getDestinations(): array
    {
        return $this->destinations;
    }

    /**
     * @param array<int, ConnectedDestination> $destinations
     */
    public function withDestinations(array $destinations): self
    {
        $clone = clone $this;
        $clone->destinations = $destinations;
        $clone->destinationCount = count($destinations);
        return $clone;
    }
}
