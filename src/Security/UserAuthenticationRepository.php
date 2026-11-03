<?php

declare(strict_types=1);

namespace App\Security;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * User Authentication Repository Implementation.
 * Isolated repository designed strictly for secure identity authentication lookups.
 */
final class UserAuthenticationRepository implements UserAuthenticationRepositoryInterface
{
    private const AUTH_COLUMNS = [
        'id' => 'u.id',
        'name' => 'u.name',
        'email' => 'u.email',
        'password_hash' => 'u.password_hash',
        'auth_key' => 'u.auth_key',
        'status' => 'u.status',
        'account_id' => 'u.account_id',
        'role' => 'aa.item_name',
    ];

    public function __construct(
        private ConnectionInterface $db,
    ) {
    }

    public function findAuthIdentityByEmail(string $email): ?UserAuthIdentity
    {
        $cleanEmail = trim($email);
        if ($cleanEmail === '') {
            return null;
        }

        $row = (new Query($this->db))
            ->select(self::AUTH_COLUMNS)
            ->from(['u' => '{{%user}}'])
            ->leftJoin(['aa' => '{{%auth_assignment}}'], 'CAST(aa.user_id AS UNSIGNED) = u.id')
            ->where(['u.email' => $cleanEmail])
            ->groupBy(['u.id', 'aa.item_name'])
            ->one();

        if ($row === null || $row === false) {
            return null;
        }

        return UserAuthIdentity::fromDatabaseRow($row, $row['role'] ?? null);
    }

    public function findAuthIdentityById(int $id): ?UserAuthIdentity
    {
        if ($id <= 0) {
            return null;
        }

        $row = (new Query($this->db))
            ->select(self::AUTH_COLUMNS)
            ->from(['u' => '{{%user}}'])
            ->leftJoin(['aa' => '{{%auth_assignment}}'], 'CAST(aa.user_id AS UNSIGNED) = u.id')
            ->where(['u.id' => $id])
            ->groupBy(['u.id', 'aa.item_name'])
            ->one();

        if ($row === null || $row === false) {
            return null;
        }

        return UserAuthIdentity::fromDatabaseRow($row, $row['role'] ?? null);
    }

    public function findAuthIdentityByAuthKey(string $authKey): ?UserAuthIdentity
    {
        $cleanKey = trim($authKey);
        if ($cleanKey === '') {
            return null;
        }

        $row = (new Query($this->db))
            ->select(self::AUTH_COLUMNS)
            ->from(['u' => '{{%user}}'])
            ->leftJoin(['aa' => '{{%auth_assignment}}'], 'CAST(aa.user_id AS UNSIGNED) = u.id')
            ->where(['u.auth_key' => $cleanKey])
            ->groupBy(['u.id', 'aa.item_name'])
            ->one();

        if ($row === null || $row === false) {
            return null;
        }

        return UserAuthIdentity::fromDatabaseRow($row, $row['role'] ?? null);
    }
}
