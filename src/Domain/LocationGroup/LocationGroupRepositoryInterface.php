<?php

declare(strict_types=1);

namespace App\Domain\LocationGroup;

use App\Security\TenantContextInterface;

/**
 * Repository interface for Location Group domain operations.
 */
interface LocationGroupRepositoryInterface
{
    /**
     * Find a Location Group by its primary ID within tenant boundary.
     */
    public function findById(int $id, ?TenantContextInterface $context = null): ?LocationGroup;

    /**
     * Retrieve paginated list of Location Groups accessible under the given tenant context.
     *
     * @return array<int, LocationGroup>
     */
    public function findAll(
        ?TenantContextInterface $context = null,
        ?int $accountId = null,
        int $page = 1,
        int $pageSize = 20,
    ): array;

    /**
     * Count total Location Groups accessible under tenant context.
     */
    public function countAll(?TenantContextInterface $context = null, ?int $accountId = null): int;

    /**
     * Retrieve all connected destinations currently assigned to a specific Location Group.
     *
     * @return array<int, ConnectedDestination>
     */
    public function findDestinationsByGroupId(int $groupId): array;

    /**
     * Retrieve member destination composite keys for a group (e.g. ['1644:0', '1715:0', '2205:31']).
     *
     * @return string[]
     */
    public function findGroupDestinationKeys(int $groupId): array;

    /**
     * Retrieve all active, connected social destinations for a specific account, grouped by platform.
     *
     * @return array{
     *     google: array<int, ConnectedDestination>,
     *     facebook: array<int, ConnectedDestination>,
     *     instagram: array<int, ConnectedDestination>
     * }
     */
    public function findAvailableDestinationsForAccount(int $accountId): array;

    /**
     * Verify whether the current tenant context is permitted to access/manage the specified group.
     */
    public function checkGroupAccess(int $groupId, TenantContextInterface $context): bool;

    /**
     * Save/persist a Location Group and synchronize its destination assignments.
     * Prepared for database writes (requires write privileges).
     *
     * @param string[] $destinationKeys Array of composite destination keys ("{config_id}:{sub_id}")
     * @return int The ID of the saved Location Group
     */
    public function save(LocationGroup $group, array $destinationKeys = []): int;

    /**
     * Delete a Location Group and cascade its destination junctions.
     * Prepared for database writes (requires write privileges).
     */
    public function delete(int $groupId): bool;
}
