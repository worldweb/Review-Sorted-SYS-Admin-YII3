<?php

declare(strict_types=1);

namespace App\Domain\User;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * User Repository Implementation.
 * Provides safe, parameterized, read-only queries for User domain entities.
 */
final class UserRepository implements UserRepositoryInterface
{
    private const SAFE_COLUMNS = [
        'id' => 'u.id',
        'name' => 'u.name',
        'email' => 'u.email',
        'status' => 'u.status',
        'account_id' => 'u.account_id',
        'owner_id' => 'u.owner_id',
        'created_at' => 'u.created_at',
        'updated_at' => 'u.updated_at',
        'role' => 'aa.item_name',
    ];

    public function __construct(
        private ConnectionInterface $db,
    ) {
    }

    public function findById(int $id): ?User
    {
        $row = (new Query($this->db))
            ->select(self::SAFE_COLUMNS)
            ->from(['u' => '{{%user}}'])
            ->leftJoin(['aa' => '{{%auth_assignment}}'], 'CAST(aa.user_id AS UNSIGNED) = u.id')
            ->where(['u.id' => $id])
            ->one();

        if ($row === null || $row === false) {
            return null;
        }

        return User::fromDatabaseRow($row, $row['role'] ?? null);
    }

    public function findByEmail(string $email): ?User
    {
        $cleanEmail = trim($email);
        if ($cleanEmail === '') {
            return null;
        }

        $row = (new Query($this->db))
            ->select(self::SAFE_COLUMNS)
            ->from(['u' => '{{%user}}'])
            ->leftJoin(['aa' => '{{%auth_assignment}}'], 'CAST(aa.user_id AS UNSIGNED) = u.id')
            ->where(['u.email' => $cleanEmail])
            ->one();

        if ($row === null || $row === false) {
            return null;
        }

        return User::fromDatabaseRow($row, $row['role'] ?? null);
    }

    public function findByAccountId(int $accountId, int $limit = 50, int $offset = 0): array
    {
        $rows = (new Query($this->db))
            ->select(self::SAFE_COLUMNS)
            ->from(['u' => '{{%user}}'])
            ->leftJoin(['aa' => '{{%auth_assignment}}'], 'CAST(aa.user_id AS UNSIGNED) = u.id')
            ->where(['u.account_id' => $accountId])
            ->orderBy(['u.id' => SORT_ASC])
            ->limit($limit)
            ->offset($offset)
            ->all();

        return array_map(
            static fn (array $row) => User::fromDatabaseRow($row, $row['role'] ?? null),
            $rows
        );
    }

    public function findSubUsers(int $ownerId): array
    {
        $rows = (new Query($this->db))
            ->select(self::SAFE_COLUMNS)
            ->from(['u' => '{{%user}}'])
            ->leftJoin(['aa' => '{{%auth_assignment}}'], 'CAST(aa.user_id AS UNSIGNED) = u.id')
            ->where(['u.owner_id' => $ownerId])
            ->orderBy(['u.id' => SORT_ASC])
            ->all();

        return array_map(
            static fn (array $row) => User::fromDatabaseRow($row, $row['role'] ?? null),
            $rows
        );
    }

    public function getUserRole(int $userId): ?string
    {
        $role = (new Query($this->db))
            ->select('item_name')
            ->from('{{%auth_assignment}}')
            ->where(['user_id' => (string) $userId])
            ->scalar();

        return is_string($role) && $role !== '' ? $role : null;
    }

    public function countByAccountId(int $accountId): int
    {
        return (int) (new Query($this->db))
            ->from('{{%user}}')
            ->where(['account_id' => $accountId])
            ->count();
    }
}
