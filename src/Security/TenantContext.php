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
        $accessible = [];
        if ($primaryAccountId !== null && $primaryAccountId > 0) {
            $accessible = [$primaryAccountId];
        }

        return new self(
            userId: $userId,
            role: $role,
            primaryAccountId: $primaryAccountId,
            scopedAccountId: null,
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

    public function isGuest(): bool
    {
        return $this->userId === null;
    }

    public function isAdmin(): bool
    {
        return $this->role === User::ROLE_ADMIN || $this->role === User::ROLE_SUPER;
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
            return true;
        }

        return in_array($accountId, $this->accessibleAccountIds, true);
    }

    public function applyTenantScope(Query $query, string $accountColumn = 'account_id'): Query
    {
        if ($this->isGuest()) {
            // Strict denial for guest access
            return $query->andWhere('0 = 1');
        }

        if ($this->isAdmin() && $this->scopedAccountId === null) {
            // Global admin has access to all records
            return $query;
        }

        $targetAccountId = $this->scopedAccountId ?? $this->primaryAccountId;
        if ($targetAccountId === null || $targetAccountId <= 0) {
            // User has no valid tenant assignment
            return $query->andWhere('0 = 1');
        }

        return $query->andWhere([$accountColumn => $targetAccountId]);
    }
}
