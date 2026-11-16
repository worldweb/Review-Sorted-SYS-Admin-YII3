<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Schema;

/**
 * ExpectedSchemaManifest.
 *
 * Definitive, version-controlled schema manifest for the Reviews Sorted Yii3 platform.
 * Contains the expected database structure (tables, columns, indexes, foreign keys, nullability, defaults)
 * required by the application's domain repositories and services.
 *
 * This manifest travels with the codebase to provide deterministic schema validation
 * when deploying to new environments, fresh databases, staging, or production.
 */
final class ExpectedSchemaManifest
{
    public const CATEGORY_CORE = 'core';
    public const CATEGORY_REVIEWS = 'reviews';
    public const CATEGORY_QRCODE = 'qrcode';
    public const CATEGORY_BILLING = 'billing';
    public const CATEGORY_NOTIFICATIONS = 'notifications';
    public const CATEGORY_LOCATION_GROUP = 'location_group';
    public const CATEGORY_SOCIAL_CONNECTION = 'social_connection';
    public const CATEGORY_SOCIAL_POST = 'social_post';
    public const CATEGORY_CHAT = 'chat';
    public const CATEGORY_EMAIL = 'email';
    public const CATEGORY_WHATSAPP = 'whatsapp';

    private static ?array $tables = null;

    /**
     * Returns the full expected database schema map.
     *
     * @return array<string, array{
     *     category: string,
     *     label: string,
     *     required: bool,
     *     primaryKey: list<string>,
     *     columns: array<string, array{
     *         type: string,
     *         dbType: string,
     *         phpType: string,
     *         size: ?int,
     *         scale: ?int,
     *         allowNull: bool,
     *         defaultValue: mixed,
     *         autoIncrement: bool,
     *         unsigned: bool
     *     }>,
     *     indexes: array<string, array{
     *         columns: list<string>,
     *         isPrimary: bool,
     *         isUnique: bool
     *     }>,
     *     foreignKeys: array<string, array<string, string>>
     * }>
     */
    public static function getTables(): array
    {
        if (self::$tables === null) {
            self::$tables = require __DIR__ . '/schema_manifest.php';
        }

        return self::$tables;
    }

    /**
     * @return list<string>
     */
    public static function getTableNames(): array
    {
        return array_keys(self::getTables());
    }

    /**
     * @return list<string>
     */
    public static function getRequiredTableNames(): array
    {
        $required = [];
        foreach (self::getTables() as $name => $def) {
            if (!empty($def['required'])) {
                $required[] = $name;
            }
        }
        return $required;
    }

    /**
     * @return ?array
     */
    public static function getTableDefinition(string $name): ?array
    {
        return self::getTables()[$name] ?? null;
    }

    /**
     * Returns category metadata labels and display order.
     *
     * @return array<string, array{label: string, icon: string, required: bool}>
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_CORE => ['label' => 'Core Platform & Security', 'icon' => '🔒', 'required' => true],
            self::CATEGORY_REVIEWS => ['label' => 'Reviews & Feedback', 'icon' => '⭐', 'required' => true],
            self::CATEGORY_QRCODE => ['label' => 'QR Code Generation & Scans', 'icon' => '📱', 'required' => true],
            self::CATEGORY_BILLING => ['label' => 'Billing & Subscriptions', 'icon' => '💳', 'required' => true],
            self::CATEGORY_NOTIFICATIONS => ['label' => 'User Notifications', 'icon' => '🔔', 'required' => true],
            self::CATEGORY_LOCATION_GROUP => ['label' => 'Location Groups', 'icon' => '📍', 'required' => true],
            self::CATEGORY_SOCIAL_CONNECTION => ['label' => 'Social & API Connections', 'icon' => '🔗', 'required' => true],
            self::CATEGORY_SOCIAL_POST => ['label' => 'Social Post Studio', 'icon' => '📢', 'required' => true],
            self::CATEGORY_CHAT => ['label' => 'Chat & Messenger Feeds', 'icon' => '💬', 'required' => true],
            self::CATEGORY_EMAIL => ['label' => 'Email Campaigns & Queue', 'icon' => '✉️', 'required' => false],
            self::CATEGORY_WHATSAPP => ['label' => 'WhatsApp Campaigns & Queue', 'icon' => '📱', 'required' => false],
        ];
    }
}