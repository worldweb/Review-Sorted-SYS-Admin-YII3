<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Security\TenantContextInterface;

/**
 * Interface for User Repository.
 * Strict read-only operations for domain user retrieval with tenant boundary enforcement.
 */
interface UserRepositoryInterface
{
    /**
     * Retrieve a user by their primary key ID.
     * Enforces tenant boundary if context is provided or active in the provider.
     */
    public function findById(int $id, ?TenantContextInterface $context = null): ?User;

    /**
     * Retrieve a user by email address.
     * Used for user lookups within authorized scope.
     */
    public function findByEmail(string $email, ?TenantContextInterface $context = null): ?User;

    /**
     * Retrieve users belonging to a specific tenant account ID.
     * Enforces tenant boundary: callers cannot query account IDs they do not own.
     *
     * @return User[]
     */
    public function findByAccountId(int $accountId, int $limit = 50, int $offset = 0, ?TenantContextInterface $context = null): array;

    /**
     * Retrieve sub-users created/managed by a specific owner user ID.
     * Enforces tenant boundary.
     *
     * @return User[]
     */
    public function findSubUsers(int $ownerId, ?TenantContextInterface $context = null): array;

    /**
     * Get the RBAC role name assigned to a user ID.
     */
    public function getUserRole(int $userId): ?string;

    /**
     * Count users belonging to an account within authorized scope.
     */
    public function countByAccountId(int $accountId, ?TenantContextInterface $context = null): int;

    /**
     * Search and paginate users respecting tenant restrictions and filter criteria.
     *
     * @return array{users: User[], total: int}
     */
    public function search(UserSearchFilter $filter, ?TenantContextInterface $context = null): array;

    /**
     * Find accounts switchable by this user (either through ownership or sub-users).
     *
     * @return array<int, string> Map of account_id => account_name
     */
    public function findSwitchableAccounts(int $userId): array;

    /**
     * Find sub-user record for account switching.
     */
    public function findSubUserByAccount(int $currentUserId, int $targetAccountId): ?User;

    /**
     * Create a new user record.
     * (Prepared for write environment; guarded in read-only environment).
     */
    public function createUser(UserSaveData $data): int;

    /**
     * Update an existing user record.
     * (Prepared for write environment; guarded in read-only environment).
     */
    public function updateUser(int $id, UserSaveData $data): bool;

    /**
     * Delete an existing user record.
     * (Prepared for write environment; guarded in read-only environment).
     */
    public function deleteUser(int $id): bool;
}
