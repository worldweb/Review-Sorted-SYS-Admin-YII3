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
     * Check whether the context represents an authenticated user.
     */
    public function isAuthenticated(): bool;

    /**
     * Check whether the context has global administrator privileges.
     */
    public function isAdmin(): bool;

    /**
     * Check whether the context has super administrator privileges.
     */
    public function isSuperAdmin(): bool;

    /**
     * Check whether the context is an unrestricted administrator (not scoped to an individual account).
     */
    public function isUnrestricted(): bool;

    /**
     * Get the primary account ID for the current context, if bound to an account.
     */
    public function getPrimaryAccountId(): ?int;

    /**
     * Get the explicitly scoped account ID for administrator sessions, if active.
     */
    public function getScopedAccountId(): ?int;

    /**
     * Get the list of all account IDs accessible under this context.
     * For unrestricted administrators, returns empty array representing global access.
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
     * Supports optional table alias to prevent SQL column ambiguity on joined queries.
     *
     * @param Query $query The query to filter.
     * @param string $column The column name (e.g. 'account_id' or 'id').
     * @param string|null $tableAlias Optional table alias (e.g. 'u' or 'review').
     */
    public function applyTenantScope(Query $query, string $column = 'account_id', ?string $tableAlias = null): Query;
}
