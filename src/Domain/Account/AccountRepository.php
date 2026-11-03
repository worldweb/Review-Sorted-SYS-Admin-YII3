<?php

declare(strict_types=1);

namespace App\Domain\Account;

use App\Security\TenantContextInterface;
use App\Security\TenantContextProviderInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * Account Repository Implementation.
 * Provides safe, parameterized, read-only queries for Account domain entities
 * with automatic tenant boundary protection.
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
        'qr_code',
        'created_at',
        'updated_at',
    ];

    public function __construct(
        private ConnectionInterface $db,
        private ?TenantContextProviderInterface $tenantContextProvider = null,
    ) {
    }

    public function findById(int $id, ?TenantContextInterface $context = null): ?Account
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        // Enforce tenant boundary: if context is present and user cannot access account, fail safe
        if ($ctx !== null && !$ctx->canAccessAccount($id)) {
            return null;
        }

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
        $cleanKey = trim($key);
        if ($cleanKey === '') {
            return null;
        }

        $row = (new Query($this->db))
            ->select(self::SAFE_COLUMNS)
            ->from('{{%account}}')
            ->where(['activation_key' => $cleanKey])
            ->one();

        if ($row === null || $row === false) {
            return null;
        }

        return Account::fromDatabaseRow($row);
    }

    public function findAllActive(int $limit = 50, int $offset = 0, ?TenantContextInterface $context = null): array
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        // Unauthenticated guests cannot list active accounts
        if ($ctx !== null && $ctx->isGuest()) {
            return [];
        }

        $query = (new Query($this->db))
            ->select(self::SAFE_COLUMNS)
            ->from('{{%account}}')
            ->where(['status' => Account::STATUS_ACTIVE])
            ->orderBy(['id' => SORT_ASC])
            ->limit($limit)
            ->offset($offset);

        // Apply tenant restrictions: non-admins only see their own account
        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $scopedId = $ctx->getScopedAccountId() ?? $ctx->getPrimaryAccountId();
            if ($scopedId === null || $scopedId <= 0) {
                return [];
            }
            $query->andWhere(['id' => $scopedId]);
        }

        $rows = $query->all();

        return array_map(static fn (array $row) => Account::fromDatabaseRow($row), $rows);
    }

    public function findAccessibleByAccountIds(array $accountIds, ?TenantContextInterface $context = null): array
    {
        $cleanIds = array_values(array_filter(array_map('intval', $accountIds), static fn (int $id) => $id > 0));

        if (empty($cleanIds)) {
            return [];
        }

        $ctx = $context ?? $this->tenantContextProvider?->get();

        // Filter requested IDs against tenant permissions
        if ($ctx !== null) {
            if ($ctx->isGuest()) {
                return [];
            }
            $cleanIds = array_values(array_filter($cleanIds, static fn (int $id) => $ctx->canAccessAccount($id)));
            if (empty($cleanIds)) {
                return [];
            }
        }

        $rows = (new Query($this->db))
            ->select(self::SAFE_COLUMNS)
            ->from('{{%account}}')
            ->where(['id' => $cleanIds])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        return array_map(static fn (array $row) => Account::fromDatabaseRow($row), $rows);
    }

    public function count(array $filter = [], ?TenantContextInterface $context = null): int
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        if ($ctx !== null && $ctx->isGuest()) {
            return 0;
        }

        $query = (new Query($this->db))->from('{{%account}}');

        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $scopedId = $ctx->getScopedAccountId() ?? $ctx->getPrimaryAccountId();
            if ($scopedId === null || $scopedId <= 0) {
                return 0;
            }
            $query->andWhere(['id' => $scopedId]);
        }

        if (isset($filter['status'])) {
            $query->andWhere(['status' => (int) $filter['status']]);
        }

        if (isset($filter['state']) && is_string($filter['state'])) {
            $query->andWhere(['state' => $filter['state']]);
        }

        return (int) $query->count();
    }

    public function search(AccountSearchFilter $filter, ?TenantContextInterface $context = null): array
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        if ($ctx !== null && $ctx->isGuest()) {
            return ['accounts' => [], 'total' => 0];
        }

        $query = (new Query($this->db))
            ->select(self::SAFE_COLUMNS)
            ->from('{{%account}}');

        // Tenant restriction: non-admins only see their own account
        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $scopedId = $ctx->getScopedAccountId() ?? $ctx->getPrimaryAccountId();
            if ($scopedId === null || $scopedId <= 0) {
                return ['accounts' => [], 'total' => 0];
            }
            $query->andWhere(['id' => $scopedId]);
        }

        // Search keyword filter
        if ($filter->search !== '') {
            $query->andWhere([
                'or',
                ['like', 'business_name', $filter->search],
                ['like', 'first_name', $filter->search],
                ['like', 'email', $filter->search],
            ]);
        }

        if ($filter->firstName !== '') {
            $query->andWhere(['like', 'first_name', $filter->firstName]);
        }

        // Status filter
        if ($filter->status !== null) {
            $query->andWhere(['status' => $filter->status]);
        }

        $total = (int) (clone $query)->count();

        // Apply sorting
        $sortOrder = strtoupper($filter->sortOrder) === 'DESC' ? SORT_DESC : SORT_ASC;
        $sortColumn = match ($filter->sortField) {
            'business_name' => 'business_name',
            'email' => 'email',
            'status' => 'status',
            'created_at' => 'created_at',
            'max_users' => 'max_users',
            default => 'id',
        };

        $rows = $query
            ->orderBy([$sortColumn => $sortOrder])
            ->limit($filter->pageSize)
            ->offset($filter->getOffset())
            ->all();

        $accounts = array_map(static fn (array $row) => Account::fromDatabaseRow($row), $rows);

        return ['accounts' => $accounts, 'total' => $total];
    }

    public function getBillingSummary(int $accountId, int $userId): array
    {
        // 1. Query latest payment record
        $paymentRow = (new Query($this->db))
            ->select(['id', 'plan_id', 'stripe_status', 'plan_period_end', 'default_source'])
            ->from('{{%subscriptions}}')
            ->where(['user_id' => $userId])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        $planName = 'No Active Plan';
        if ($paymentRow !== null && !empty($paymentRow['plan_id'])) {
            $planRow = (new Query($this->db))
                ->select(['plan_name'])
                ->from('{{%plans}}')
                ->where(['id' => $paymentRow['plan_id']])
                ->one();
            if ($planRow !== null && !empty($planRow['plan_name'])) {
                $planName = (string) $planRow['plan_name'];
            }
        }

        // 2. Count active users in account
        $activeUsersCount = (int) (new Query($this->db))
            ->from('{{%user}}')
            ->where(['account_id' => $accountId])
            ->count();

        // 3. Get allowed seats from account
        $account = $this->findById($accountId);
        $allowedSeats = $account?->getMaxUsers() ?? 0;

        $hasSubscription = $paymentRow !== null && !empty($paymentRow['id']);
        $status = $hasSubscription && !empty($paymentRow['stripe_status']) ? (string) $paymentRow['stripe_status'] : 'inactive';
        $renewalDate = $paymentRow !== null && !empty($paymentRow['plan_period_end'])
            ? date('M j, Y', strtotime((string) $paymentRow['plan_period_end']))
            : null;
        $defaultSource = $paymentRow['default_source'] ?? null;
        $paymentLast4 = is_string($defaultSource) && strlen($defaultSource) >= 4 ? substr($defaultSource, -4) : null;

        return [
            'hasSubscription' => $hasSubscription,
            'planName' => $planName,
            'status' => $status,
            'renewalDate' => $renewalDate,
            'usedSeats' => $activeUsersCount,
            'allowedSeats' => $allowedSeats,
            'paymentBrand' => 'Card',
            'paymentLast4' => $paymentLast4,
        ];
    }

    public function createAccount(AccountSaveData $data): int
    {
        throw new \RuntimeException(
            'Database write operation (createAccount) blocked: Database connection is strictly in read-only mode during Phase 3.'
        );
    }

    public function updateAccount(int $id, AccountSaveData $data): bool
    {
        throw new \RuntimeException(
            'Database write operation (updateAccount) blocked: Database connection is strictly in read-only mode during Phase 3.'
        );
    }

    public function deleteAccount(int $id): bool
    {
        throw new \RuntimeException(
            'Database write operation (deleteAccount) blocked: Database connection is strictly in read-only mode during Phase 3.'
        );
    }
}
