<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Migration;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * M261116000001AddSocialPostStudioRbac.
 *
 * Resolves RS-SEC-010: Maps Social Post Studio routes (/social-post/*)
 * to the `account_admin` RBAC role in auth_item and auth_item_child.
 *
 * NOTE: Investigation/audit rules enforce that migrations are NOT executed
 * automatically during this phase. This file is ready for execution by operations.
 */
final class M261116000001AddSocialPostStudioRbac
{
    private const PERMISSION_NAME = '/social-post/*';
    private const ROLE_ACCOUNT_ADMIN = 'account_admin';

    public function __construct(
        private ConnectionInterface $db,
    ) {
    }

    public function up(): void
    {
        $transaction = $this->db->beginTransaction();
        try {
            $now = time();

            // 1. Check or insert permission in auth_item (type 2 = PERMISSION)
            $existsItem = (new Query($this->db))
                ->from('{{%auth_item}}')
                ->where(['name' => self::PERMISSION_NAME])
                ->exists();

            if (!$existsItem) {
                $this->db->createCommand()->insert('{{%auth_item}}', [
                    'name' => self::PERMISSION_NAME,
                    'type' => 2,
                    'description' => 'Social Post Studio Access and Management',
                    'rule_name' => null,
                    'data' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->execute();
            }

            // 2. Check or insert mapping in auth_item_child (prevent duplicate entries)
            $existsChild = (new Query($this->db))
                ->from('{{%auth_item_child}}')
                ->where([
                    'parent' => self::ROLE_ACCOUNT_ADMIN,
                    'child' => self::PERMISSION_NAME,
                ])
                ->exists();

            if (!$existsChild) {
                $this->db->createCommand()->insert('{{%auth_item_child}}', [
                    'parent' => self::ROLE_ACCOUNT_ADMIN,
                    'child' => self::PERMISSION_NAME,
                ])->execute();
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public function down(): void
    {
        $transaction = $this->db->beginTransaction();
        try {
            // 1. Remove mapping for account_admin only
            $this->db->createCommand()->delete('{{%auth_item_child}}', [
                'parent' => self::ROLE_ACCOUNT_ADMIN,
                'child' => self::PERMISSION_NAME,
            ])->execute();

            // 2. Only remove permission from auth_item if no other roles (e.g. admin) still reference it
            $remainingReferences = (int) (new Query($this->db))
                ->from('{{%auth_item_child}}')
                ->where(['child' => self::PERMISSION_NAME])
                ->count();

            if ($remainingReferences === 0) {
                $this->db->createCommand()->delete('{{%auth_item}}', [
                    'name' => self::PERMISSION_NAME,
                ])->execute();
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
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
