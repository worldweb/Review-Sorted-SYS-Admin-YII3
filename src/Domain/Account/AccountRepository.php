<?php

declare(strict_types=1);

namespace App\Domain\Account;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * Account Repository Implementation.
 * Provides safe, parameterized, read-only queries for Account domain entities.
 */
final class AccountRepository implements AccountRepositoryInterface
{
    private const SAFE_COLUMNS = [
        'id',
        'business_name',
        'status',
        'max_users',
        'activation_key',
        'state',
        'first_name',
        'last_name',
        'email',
        'contact_number',
        'website',
        'created_at',
        'updated_at',
    ];

    public function __construct(
        private ConnectionInterface $db,
    ) {
    }

    public function findById(int $id): ?Account
    {
        $row = (new Query($this->db))
            ->select(self::SAFE_COLUMNS)
            ->from('{{%account}}')
            ->where(['id' => $id])
            ->one();

        if ($row === null || $row === false) {
            return null;
        }

        return Account::fromDatabaseRow($row);
    }

    public function findByActivationKey(string $key): ?Account
    {
        if (trim($key) === '') {
            return null;
        }

        $row = (new Query($this->db))
            ->select(self::SAFE_COLUMNS)
            ->from('{{%account}}')
            ->where(['activation_key' => $key])
            ->one();

        if ($row === null || $row === false) {
            return null;
        }

        return Account::fromDatabaseRow($row);
    }

    public function findAllActive(int $limit = 50, int $offset = 0): array
    {
        $rows = (new Query($this->db))
            ->select(self::SAFE_COLUMNS)
            ->from('{{%account}}')
            ->where(['status' => Account::STATUS_ACTIVE])
            ->orderBy(['id' => SORT_ASC])
            ->limit($limit)
            ->offset($offset)
            ->all();

        return array_map(static fn (array $row) => Account::fromDatabaseRow($row), $rows);
    }

    public function findAccessibleByAccountIds(array $accountIds): array
    {
        $cleanIds = array_values(array_filter(array_map('intval', $accountIds), static fn (int $id) => $id > 0));

        if (empty($cleanIds)) {
            return [];
        }

        $rows = (new Query($this->db))
            ->select(self::SAFE_COLUMNS)
            ->from('{{%account}}')
            ->where(['id' => $cleanIds])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        return array_map(static fn (array $row) => Account::fromDatabaseRow($row), $rows);
    }

    public function count(array $filter = []): int
    {
        $query = (new Query($this->db))->from('{{%account}}');

        if (isset($filter['status'])) {
            $query->andWhere(['status' => (int) $filter['status']]);
        }

        if (isset($filter['state']) && is_string($filter['state'])) {
            $query->andWhere(['state' => $filter['state']]);
        }

        return (int) $query->count();
    }
}
