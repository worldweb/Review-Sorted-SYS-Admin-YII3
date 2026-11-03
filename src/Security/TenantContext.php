<?php

declare(strict_types=1);

namespace App\Security;

use App\Domain\User\User;
use Yiisoft\Db\Query\Query;

/**
 * Tenant Context.
 * Manages tenant scoping, identity boundaries, and query isolation.
 */
final class TenantContext implements TenantContextInterface
{
    /**
     * @param int[] $accessibleAccountIds
     */
    public function __construct(
        private ?int $userId = null,
        private ?string $role = null,
        private ?int $primaryAccountId = null,
        private ?int $scopedAccountId = null,
        private array $accessibleAccountIds = [],
    ) {
    }

    /**
     * Create a TenantContext for an authenticated User entity.
     */
    public static function fromUser(?User $user, ?int $scopedAccountId = null): self
    {
        if ($user === null || !$user->isActive()) {
            return self::guest();
        }

        $userId = $user->getId();
        $role = $user->getRole();
        $primaryAccountId = $user->getAccountId();

        if ($user->isAdmin()) {
            // Admin users have global visibility unless explicit account scope is set
            $accessible = $scopedAccountId !== null ? [$scopedAccountId] : [];
            return new self(
                userId: $userId,
                role: $role,
                primaryAccountId: $scopedAccountId ?? $primaryAccountId,
                scopedAccountId: $scopedAccountId,
                accessibleAccountIds: $accessible,
            );
        }

        // Standard tenant user (account_admin or worker)
        $resolvedAccountId = $scopedAccountId ?? ($primaryAccountId ?? $user->getOwnerId());
        $accessible = [];
        if ($resolvedAccountId !== null && $resolvedAccountId > 0) {
            $accessible[] = $resolvedAccountId;
        }
        if ($primaryAccountId !== null && $primaryAccountId > 0) {
            $accessible[] = $primaryAccountId;
        }
        $accessible = array_values(array_unique($accessible));

        return new self(
            userId: $userId,
            role: $role,
            primaryAccountId: $resolvedAccountId,
            scopedAccountId: $scopedAccountId,
            accessibleAccountIds: $accessible,
        );
    }

    /**
     * Create an unauthenticated Guest context.
     */
    public static function guest(): self
    {
        return new self();
    }

    /**
     * Create an unrestricted system context for internal authentication and bootstrap lookups.
     */
    public static function unrestricted(?int $userId = null): self
    {
        return new self(
            userId: $userId ?? 0,
            role: User::ROLE_SUPER,
        );
    }

    public function isGuest(): bool
    {
        return $this->userId === null;
    }

    public function isAuthenticated(): bool
    {
        return $this->userId !== null;
    }

    public function isAdmin(): bool
    {
        return $this->role === User::ROLE_ADMIN || $this->role === User::ROLE_SUPER;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === User::ROLE_SUPER;
    }

    public function isAccountAdmin(): bool
    {
        return $this->role === User::ROLE_ACCOUNT_ADMIN;
    }

    public function isWorker(): bool
    {
        return $this->role === User::ROLE_WORKER;
    }

    public function getAccountId(): ?int
    {
        return $this->getPrimaryAccountId();
    }

    public function isUnrestricted(): bool
    {
        return $this->isAdmin() && $this->scopedAccountId === null;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function getPrimaryAccountId(): ?int
    {
        return $this->scopedAccountId ?? $this->primaryAccountId;
    }

    public function getScopedAccountId(): ?int
    {
        return $this->scopedAccountId;
    }

    public function getAccessibleAccountIds(): array
    {
        return $this->accessibleAccountIds;
    }

    public function canAccessAccount(int $accountId): bool
    {
        if ($this->isGuest() || $accountId <= 0) {
            return false;
        }

        if ($this->isAdmin()) {
            // When an administrator is explicitly scoped into an account,
            // they are restricted to that account for the scoped session
            if ($this->scopedAccountId !== null) {
                return $accountId === $this->scopedAccountId;
            }
            // Unrestricted global administrator can access all accounts
            return true;
        }

        return in_array($accountId, $this->accessibleAccountIds, true);
    }

    public function applyTenantScope(Query $query, string $column = 'account_id', ?string $tableAlias = null): Query
    {
        if ($this->isGuest()) {
            // Strict denial for unauthenticated guest access
            return $query->andWhere('0 = 1');
        }

        if ($this->isUnrestricted()) {
            // Unrestricted global administrator has access to all records
            return $query;
        }

        $targetAccountId = $this->scopedAccountId ?? $this->primaryAccountId;
        if ($targetAccountId === null || $targetAccountId <= 0) {
            // User has no valid tenant assignment
            return $query->andWhere('0 = 1');
        }

        // Prevent column ambiguity in joined queries
        $qualifiedColumn = $column;
        if ($tableAlias !== null && !str_contains($column, '.')) {
            $qualifiedColumn = $tableAlias . '.' . $column;
        }

        return $query->andWhere([$qualifiedColumn => $targetAccountId]);
    }
}
