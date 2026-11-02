<?php

declare(strict_types=1);

namespace App\Domain\User;

/**
 * Interface for User Repository.
 * Strict read-only operations for domain user retrieval.
 */
interface UserRepositoryInterface
{
    /**
     * Retrieve a user by their primary key ID.
     */
    public function findById(int $id): ?User;

    /**
     * Retrieve a user by email address.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Retrieve users belonging to a specific tenant account ID.
     *
     * @return User[]
     */
    public function findByAccountId(int $accountId, int $limit = 50, int $offset = 0): array;

    /**
     * Retrieve sub-users created/managed by a specific owner user ID.
     *
     * @return User[]
     */
    public function findSubUsers(int $ownerId): array;

    /**
     * Get the RBAC role name assigned to a user ID.
     */
    public function getUserRole(int $userId): ?string;

    /**
     * Count users belonging to an account.
     */
    public function countByAccountId(int $accountId): int;
}
