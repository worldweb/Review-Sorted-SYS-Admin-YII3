<?php

declare(strict_types=1);

namespace App\Domain\LocationGroup;

/**
 * Location Group Service Config Junction Domain Entity.
 * Represents the membership of a specific connected social destination within a Location Group.
 * Mapped to database table 'location_group_service_config'.
 */
final class LocationGroupServiceConfig
{
    public function __construct(
        private int $id,
        private int $groupId,
        private int $serviceConfigId,
        private ?int $subServiceId,
        private int $createdAt,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            groupId: (int) $row['group_id'],
            serviceConfigId: (int) $row['service_config_id'],
            subServiceId: isset($row['sub_service_id']) && (int) $row['sub_service_id'] > 0
                ? (int) $row['sub_service_id']
                : null,
            createdAt: (int) ($row['created_at'] ?? 0),
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function getServiceConfigId(): int
    {
        return $this->serviceConfigId;
    }

    public function getSubServiceId(): ?int
    {
        return $this->subServiceId;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    /**
     * Get the composite destination identity tuple: "{service_config_id}:{sub_service_id}".
     */
    public function getCompositeKey(): string
    {
        $sub = $this->subServiceId ?? 0;
        return "{$this->serviceConfigId}:{$sub}";
    }
}
