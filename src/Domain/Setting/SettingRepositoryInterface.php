<?php

declare(strict_types=1);

namespace App\Domain\Setting;

/**
 * Interface for System Settings Repository.
 * Strict read-only configuration retrieval.
 */
interface SettingRepositoryInterface
{
    /**
     * Retrieve a raw string setting value, or return $default.
     */
    public function get(string $key, ?string $default = null): ?string;

    /**
     * Retrieve an integer setting value, or return $default.
     */
    public function getInt(string $key, int $default = 0): int;

    /**
     * Retrieve a boolean setting value, or return $default.
     */
    public function getBool(string $key, bool $default = false): bool;

    /**
     * Retrieve all system settings as a key => value dictionary.
     *
     * @return array<string, ?string>
     */
    public function getAll(): array;

    /**
     * Check if a setting key exists in the database.
     */
    public function has(string $key): bool;
}
