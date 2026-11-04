<?php

declare(strict_types=1);

namespace App\Domain\Setting;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * System Settings Repository Implementation.
 * Provides safe, parameterized configuration lookups and persistence with request-level memory caching.
 */
final class SettingRepository implements SettingRepositoryInterface
{
    /**
     * In-memory cache for the current request lifecycle.
     * @var array<string, ?string>|null
     */
    private ?array $allSettingsCache = null;

    /**
     * @var array<string, ?string>
     */
    private array $itemCache = [];

    public function __construct(
        private ConnectionInterface $db,
    ) {
    }

    public function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, $this->itemCache)) {
            return $this->itemCache[$key] ?? $default;
        }

        if ($this->allSettingsCache !== null) {
            return array_key_exists($key, $this->allSettingsCache)
                ? $this->allSettingsCache[$key]
                : $default;
        }

        $row = (new Query($this->db))
            ->select(['value'])
            ->from('{{%system_setting}}')
            ->where(['key' => $key])
            ->one();

        if ($row === null || $row === false) {
            $this->itemCache[$key] = null;
            return $default;
        }

        $val = $row['value'] !== null ? (string) $row['value'] : null;
        $this->itemCache[$key] = $val;

        return $val ?? $default;
    }

    public function getInt(string $key, int $default = 0): int
    {
        $val = $this->get($key, null);
        if ($val === null || !is_numeric($val)) {
            return $default;
        }

        return (int) $val;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $val = $this->get($key, null);
        if ($val === null) {
            return $default;
        }

        $lower = strtolower(trim($val));
        if (in_array($lower, ['1', 'true', 'on', 'yes'], true)) {
            return true;
        }
        if (in_array($lower, ['0', 'false', 'off', 'no', ''], true)) {
            return false;
        }

        return (bool) $val;
    }

    public function getAll(): array
    {
        if ($this->allSettingsCache !== null) {
            return $this->allSettingsCache;
        }

        $rows = (new Query($this->db))
            ->select(['key', 'value'])
            ->from('{{%system_setting}}')
            ->all();

        $map = [];
        foreach ($rows as $row) {
            $k = (string) $row['key'];
            $map[$k] = $row['value'] !== null ? (string) $row['value'] : null;
        }

        $this->allSettingsCache = $map;

        return $map;
    }

    public function has(string $key): bool
    {
        if ($this->allSettingsCache !== null) {
            return array_key_exists($key, $this->allSettingsCache);
        }

        if (array_key_exists($key, $this->itemCache) && $this->itemCache[$key] !== null) {
            return true;
        }

        return (new Query($this->db))
            ->from('{{%system_setting}}')
            ->where(['key' => $key])
            ->exists();
    }

    public function set(string $key, ?string $value): bool
    {
        try {
            $existing = (new Query($this->db))
                ->from('{{%system_setting}}')
                ->where(['key' => $key])
                ->exists();

            if ($existing) {
                $this->db->createCommand()->update(
                    '{{%system_setting}}',
                    ['value' => $value, 'updated_at' => time()],
                    ['key' => $key]
                )->execute();
            } else {
                $this->db->createCommand()->insert(
                    '{{%system_setting}}',
                    ['key' => $key, 'value' => $value, 'updated_at' => time()]
                )->execute();
            }

            $this->clearCache();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function clearCache(): void
    {
        $this->allSettingsCache = null;
        $this->itemCache = [];
    }
}
