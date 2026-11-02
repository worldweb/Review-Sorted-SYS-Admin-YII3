<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * Interface for Account Repository.
 * Strict read-only operations for domain account retrieval.
 */
interface AccountRepositoryInterface
{
    /**
     * Retrieve an account by its primary key ID.
     */
    public function findById(int $id): ?Account;

    /**
     * Retrieve an account by its unique activation key.
     */
    public function findByActivationKey(string $key): ?Account;

    /**
     * Retrieve all active accounts with pagination.
     *
     * @return Account[]
     */
    public function findAllActive(int $limit = 50, int $offset = 0): array;

    /**
     * Retrieve accounts accessible by a list of tenant account IDs.
     *
     * @param int[] $accountIds
     * @return Account[]
     */
    public function findAccessibleByAccountIds(array $accountIds): array;

    /**
     * Count total accounts matching filter criteria.
     */
    public function count(array $filter = []): int;
}
