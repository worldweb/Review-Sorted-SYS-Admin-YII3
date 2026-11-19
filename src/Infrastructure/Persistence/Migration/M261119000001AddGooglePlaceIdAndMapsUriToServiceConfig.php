<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Migration;

use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * M261119000001AddGooglePlaceIdAndMapsUriToServiceConfig.
 *
 * Adds `google_place_id` and `google_maps_uri` columns to `service_config`
 * to store Google Place ID and Google Maps URI separately without overwriting
 * the existing `page_id` column.
 */
final class M261119000001AddGooglePlaceIdAndMapsUriToServiceConfig
{
    public function __construct(
        private ConnectionInterface $db,
    ) {
    }

    public function up(): void
    {
        $schema = $this->db->getSchema();
        $table = $schema->getTableSchema('{{%service_config}}');

        if ($table !== null) {
            if ($table->getColumn('google_place_id') === null) {
                $this->db->createCommand(
                    'ALTER TABLE {{%service_config}} ADD COLUMN `google_place_id` VARCHAR(255) NULL DEFAULT NULL AFTER `page_id`'
                )->execute();
            }

            if ($table->getColumn('google_maps_uri') === null) {
                $this->db->createCommand(
                    'ALTER TABLE {{%service_config}} ADD COLUMN `google_maps_uri` TEXT NULL DEFAULT NULL AFTER `google_place_id`'
                )->execute();
            }

            $indexes = $this->db->createCommand(
                "SHOW INDEX FROM {{%service_config}} WHERE Key_name = 'idx_service_config_google_place_id'"
            )->queryAll();

            if (empty($indexes)) {
                $this->db->createCommand(
                    'ALTER TABLE {{%service_config}} ADD INDEX `idx_service_config_google_place_id` (`google_place_id`)'
                )->execute();
            }
        }
    }

    public function down(): void
    {
        $schema = $this->db->getSchema();
        $table = $schema->getTableSchema('{{%service_config}}');

        if ($table !== null) {
            $indexes = $this->db->createCommand(
                "SHOW INDEX FROM {{%service_config}} WHERE Key_name = 'idx_service_config_google_place_id'"
            )->queryAll();

            if (!empty($indexes)) {
                $this->db->createCommand(
                    'ALTER TABLE {{%service_config}} DROP INDEX `idx_service_config_google_place_id`'
                )->execute();
            }

            if ($table->getColumn('google_maps_uri') !== null) {
                $this->db->createCommand(
                    'ALTER TABLE {{%service_config}} DROP COLUMN `google_maps_uri`'
                )->execute();
            }

            if ($table->getColumn('google_place_id') !== null) {
                $this->db->createCommand(
                    'ALTER TABLE {{%service_config}} DROP COLUMN `google_place_id`'
                )->execute();
            }
        }
    }

    public function safeUp(): void
    {
        $this->up();
    }

    public function safeDown(): void
    {
        $this->down();
    }
}
