<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Security\TenantContextInterface;
use App\Security\TenantContextProviderInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * User Repository Implementation.
 * Provides safe, parameterized, read-only queries for User domain entities
 * with automatic tenant boundary protection.
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
        private ?TenantContextProviderInterface $tenantContextProvider = null,
    ) {
    }

    public function findById(int $id, ?TenantContextInterface $context = null): ?User
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        // Unauthenticated guests cannot lookup users
        if ($ctx !== null && $ctx->isGuest()) {
            return null;
        }

        $row = (new Query($this->db))
            ->select(self::SAFE_COLUMNS)
            ->from(['u' => '{{%user}}'])
            ->leftJoin(['aa' => '{{%auth_assignment}}'], 'CAST(aa.user_id AS UNSIGNED) = u.id')
            ->where(['u.id' => $id])
            ->groupBy(['u.id', 'aa.item_name'])
            ->one();

        if ($row === null || $row === false) {
            return null;
        }

        $user = User::fromDatabaseRow($row, $row['role'] ?? null);

        // Enforce tenant boundary: non-admins cannot view users from other accounts
        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $userAccountId = $user->getAccountId();
            if ($userAccountId !== null && !$ctx->canAccessAccount($userAccountId)) {
                return null;
            }
            // If the target user has no account (e.g. system admin) and current user is not unrestricted admin or self
            if ($userAccountId === null && $ctx->getUserId() !== $user->getId()) {
                return null;
            }
        }

        return $user;
    }

    public function findByEmail(string $email, ?TenantContextInterface $context = null): ?User
    {
        $cleanEmail = trim($email);
        if ($cleanEmail === '') {
            return null;
        }

        $ctx = $context ?? $this->tenantContextProvider?->get();

        // Unauthenticated guests cannot lookup users by email via domain repository
        if ($ctx !== null && $ctx->isGuest()) {
            return null;
        }

        $row = (new Query($this->db))
            ->select(self::SAFE_COLUMNS)
            ->from(['u' => '{{%user}}'])
            ->leftJoin(['aa' => '{{%auth_assignment}}'], 'CAST(aa.user_id AS UNSIGNED) = u.id')
            ->where(['u.email' => $cleanEmail])
            ->groupBy(['u.id', 'aa.item_name'])
            ->one();

        if ($row === null || $row === false) {
            return null;
        }

        $user = User::fromDatabaseRow($row, $row['role'] ?? null);

        // Enforce tenant boundary
        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $userAccountId = $user->getAccountId();
            if ($userAccountId !== null && !$ctx->canAccessAccount($userAccountId)) {
                return null;
            }
        }

        return $user;
    }

    public function findByAccountId(int $accountId, int $limit = 50, int $offset = 0, ?TenantContextInterface $context = null): array
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        // Enforce tenant boundary: caller must have permission to access requested account
        if ($ctx !== null && !$ctx->canAccessAccount($accountId)) {
            return [];
        }

        $rows = (new Query($this->db))
            ->select(self::SAFE_COLUMNS)
            ->from(['u' => '{{%user}}'])
            ->leftJoin(['aa' => '{{%auth_assignment}}'], 'CAST(aa.user_id AS UNSIGNED) = u.id')
            ->where(['u.account_id' => $accountId])
            ->groupBy(['u.id', 'aa.item_name'])
            ->orderBy(['u.id' => SORT_ASC])
            ->limit($limit)
            ->offset($offset)
            ->all();

        return array_map(
            static fn (array $row) => User::fromDatabaseRow($row, $row['role'] ?? null),
            $rows
        );
    }

    public function findSubUsers(int $ownerId, ?TenantContextInterface $context = null): array
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        // If context is present, ensure caller can access the owner's account
        if ($ctx !== null && !$ctx->isUnrestricted()) {
            if ($ctx->getUserId() !== $ownerId) {
                // If not self, verify owner's account is within scope
                $ownerRow = (new Query($this->db))
                    ->select(['account_id'])
                    ->from('{{%user}}')
                    ->where(['id' => $ownerId])
                    ->one();

                if ($ownerRow === null || $ownerRow === false) {
                    return [];
                }

                $ownerAccId = (int) ($ownerRow['account_id'] ?? 0);
                if (!$ctx->canAccessAccount($ownerAccId)) {
                    return [];
                }
            }
        }

        $rows = (new Query($this->db))
            ->select(self::SAFE_COLUMNS)
            ->from(['u' => '{{%user}}'])
            ->leftJoin(['aa' => '{{%auth_assignment}}'], 'CAST(aa.user_id AS UNSIGNED) = u.id')
            ->where(['u.owner_id' => $ownerId])
            ->groupBy(['u.id', 'aa.item_name'])
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

    public function countByAccountId(int $accountId, ?TenantContextInterface $context = null): int
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        if ($ctx !== null && !$ctx->canAccessAccount($accountId)) {
            return 0;
        }

        return (int) (new Query($this->db))
            ->from('{{%user}}')
            ->where(['account_id' => $accountId])
            ->count();
    }

    public function search(UserSearchFilter $filter, ?TenantContextInterface $context = null): array
    {
        $ctx = $context ?? $this->tenantContextProvider?->get();

        // Guest access blocked
        if ($ctx !== null && $ctx->isGuest()) {
            return ['users' => [], 'total' => 0];
        }

        $query = (new Query($this->db))
            ->select(self::SAFE_COLUMNS)
            ->from(['u' => '{{%user}}'])
            ->leftJoin(['aa' => '{{%auth_assignment}}'], 'CAST(aa.user_id AS UNSIGNED) = u.id')
            ->where(['u.owner_id' => null]); // Reproducing Yii 2 UserSearch: exclude sub-users

        // Tenant boundary enforcement
        if ($ctx !== null && !$ctx->isUnrestricted()) {
            $contextAccountId = $ctx->getAccountId();
            if ($contextAccountId !== null && $contextAccountId > 0) {
                $query->andWhere(['u.account_id' => $contextAccountId]);
            } else {
                // If user has no account and is not unrestricted admin, restrict to self
                $query->andWhere(['u.id' => $ctx->getUserId()]);
            }
        } elseif ($filter->accountId !== null && $filter->accountId > 0) {
            $query->andWhere(['u.account_id' => $filter->accountId]);
        }

        // Search keyword filter
        if ($filter->search !== '') {
            $query->andWhere([
                'or',
                ['like', 'u.name', $filter->search],
                ['like', 'u.email', $filter->search],
            ]);
        }

        // Status filter
        if ($filter->status !== null) {
            $query->andWhere(['u.status' => $filter->status]);
        }

        // Role filter
        if ($filter->role !== null && $filter->role !== '') {
            $query->andWhere(['aa.item_name' => $filter->role]);
        }

        $query->groupBy(['u.id', 'aa.item_name']);

        // Count total matching records
        $total = (int) (clone $query)->count();

        // Apply sorting
        $sortOrder = strtoupper($filter->sortOrder) === 'DESC' ? SORT_DESC : SORT_ASC;
        $sortColumn = match ($filter->sortField) {
            'name' => 'u.name',
            'email' => 'u.email',
            'status' => 'u.status',
            'created_at' => 'u.created_at',
            default => 'u.id',
        };

        $rows = $query
            ->orderBy([$sortColumn => $sortOrder])
            ->limit($filter->pageSize)
            ->offset($filter->getOffset())
            ->all();

        $users = array_map(
            static fn (array $row) => User::fromDatabaseRow($row, $row['role'] ?? null),
            $rows
        );

        return ['users' => $users, 'total' => $total];
    }

    public function findSwitchableAccounts(int $userId): array
    {
        // Find all accounts where this user is the primary user or owner of a sub-user
        $rows = (new Query($this->db))
            ->select(['a.id', 'a.business_name'])
            ->from(['u' => '{{%user}}'])
            ->innerJoin(['a' => '{{%account}}'], 'a.id = u.account_id')
            ->where(['or', ['u.id' => $userId], ['u.owner_id' => $userId]])
            ->andWhere(['a.status' => 1])
            ->groupBy(['a.id', 'a.business_name'])
            ->all();

        $accounts = [];
        foreach ($rows as $row) {
            $accounts[(int) $row['id']] = (string) $row['business_name'];
        }

        return $accounts;
    }

    public function findSubUserByAccount(int $currentUserId, int $targetAccountId): ?User
    {
        // Reproducing Yii 2 UserController::actionSwitch lines 268-275
        $currentUserRow = (new Query($this->db))
            ->select(['id', 'owner_id', 'account_id'])
            ->from('{{%user}}')
            ->where(['id' => $currentUserId])
            ->one();

        if ($currentUserRow === null || $currentUserRow === false) {
            return null;
        }

        $ownerId = !empty($currentUserRow['owner_id']) ? (int) $currentUserRow['owner_id'] : $currentUserId;

        // Find user where account_id = targetAccountId AND (id = ownerId OR owner_id = ownerId)
        $row = (new Query($this->db))
            ->select(self::SAFE_COLUMNS)
            ->from(['u' => '{{%user}}'])
            ->leftJoin(['aa' => '{{%auth_assignment}}'], 'CAST(aa.user_id AS UNSIGNED) = u.id')
            ->where(['u.account_id' => $targetAccountId])
            ->andWhere(['or', ['u.id' => $ownerId], ['u.owner_id' => $ownerId]])
            ->groupBy(['u.id', 'aa.item_name'])
            ->one();

        if ($row === null || $row === false) {
            return null;
        }

        return User::fromDatabaseRow($row, $row['role'] ?? null);
    }

    public function createUser(UserSaveData $data): int
    {
        throw new \RuntimeException(
            'Database write operation (createUser) blocked: Database connection is strictly in read-only mode during Phase 3.'
        );
    }

    public function updateUser(int $id, UserSaveData $data): bool
    {
        throw new \RuntimeException(
            'Database write operation (updateUser) blocked: Database connection is strictly in read-only mode during Phase 3.'
        );
    }

    public function deleteUser(int $id): bool
    {
        throw new \RuntimeException(
            'Database write operation (deleteUser) blocked: Database connection is strictly in read-only mode during Phase 3.'
        );
    }
}
