<?php

declare(strict_types=1);

namespace App\Security;

use Yiisoft\Db\Query\Query;

/**
 * Interface for Tenant Context.
 * Governs multi-tenant data isolation and access boundary verification.
 */
interface TenantContextInterface
{
    /**
     * Check whether the context represents a guest (unauthenticated).
     */
    public function isGuest(): bool;

    /**
     * Check whether the context has global administrator privileges.
     */
    public function isAdmin(): bool;

    /**
     * Get the primary account ID for the current context, if bound to an account.
     */
    public function getPrimaryAccountId(): ?int;

    /**
     * Get the list of all account IDs accessible under this context.
     * Empty array implies either guest or unassigned account.
     * Special: if admin and not scoped, returns empty array or null indicator meaning all.
     *
     * @return int[]
     */
    public function getAccessibleAccountIds(): array;

    /**
     * Check if a specific account ID is accessible under the current tenant context.
     */
    public function canAccessAccount(int $accountId): bool;

    /**
     * Apply tenant isolation filtering to a Yiisoft Query instance.
     */
    public function applyTenantScope(Query $query, string $accountColumn = 'account_id'): Query;
}
