<?php

declare(strict_types=1);

namespace App\Domain\Account;

use App\Security\TenantContextInterface;

/**
 * Interface for Account Repository.
 * Strict read-only operations for domain account retrieval with tenant boundary enforcement.
 */
interface AccountRepositoryInterface
{
    /**
     * Retrieve an account by its primary key ID.
     * Enforces tenant boundary if context is provided or active in the provider.
     */
    public function findById(int $id, ?TenantContextInterface $context = null): ?Account;

    /**
     * Retrieve an account by its unique activation key.
     * Used by external integrations and webhook verification.
     */
    public function findByActivationKey(string $key): ?Account;

    /**
     * Retrieve active accounts with pagination.
     * Enforces tenant scoping: non-admins only see their own account.
     *
     * @return Account[]
     */
    public function findAllActive(int $limit = 50, int $offset = 0, ?TenantContextInterface $context = null): array;

    /**
     * Retrieve accounts accessible by a list of tenant account IDs.
     * Enforces tenant scoping: filters out account IDs the caller is not authorized to access.
     *
     * @param int[] $accountIds
     * @return Account[]
     */
    public function findAccessibleByAccountIds(array $accountIds, ?TenantContextInterface $context = null): array;

    /**
     * Count total accounts matching filter criteria and tenant boundaries.
     */
    public function count(array $filter = [], ?TenantContextInterface $context = null): int;

    /**
     * Search and paginate accounts respecting tenant restrictions and filter criteria.
     *
     * @return array{accounts: Account[], total: int}
     */
    public function search(AccountSearchFilter $filter, ?TenantContextInterface $context = null): array;

    /**
     * Get authoritative billing and seat usage data for workspace overview.
     */
    public function getBillingSummary(int $accountId, int $userId): array;

    /**
     * Create a new tenant account record.
     * (Prepared for write environment; guarded in read-only environment).
     */
    public function createAccount(AccountSaveData $data): int;

    /**
     * Update an existing tenant account record.
     * (Prepared for write environment; guarded in read-only environment).
     */
    public function updateAccount(int $id, AccountSaveData $data): bool;

    /**
     * Delete an existing tenant account record.
     * (Prepared for write environment; guarded in read-only environment).
     */
    public function deleteAccount(int $id): bool;
}
