<?php

declare(strict_types=1);

namespace App\Domain\Setting;

/**
 * System Setting Domain Entity / DTO.
 */
final class SystemSetting
{
    public function __construct(
        private string $key,
        private ?string $value,
        private int $updatedAt = 0,
    ) {
    }

    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            key: (string) ($row['key'] ?? ''),
            value: isset($row['value']) ? (string) $row['value'] : null,
            updatedAt: (int) ($row['updated_at'] ?? 0),
        );
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function getUpdatedAt(): int
    {
        return $this->updatedAt;
    }
}
