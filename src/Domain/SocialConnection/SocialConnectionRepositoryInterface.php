<?php

declare(strict_types=1);

namespace App\Domain\SocialConnection;

use App\Security\TenantContextInterface;

/**
 * Repository interface for external social platform connections (Facebook, Google, Instagram).
 */
interface SocialConnectionRepositoryInterface
{
    /**
     * Find a single ServiceConfig by ID, scoped to tenant context if not super admin.
     */
    public function findById(int $id, ?TenantContextInterface $context = null): ?ServiceConfig;

    /**
     * Find all ServiceConfigs for Facebook (service_id = 2), scoped to tenant.
     *
     * @return array<int, ServiceConfig>
     */
    public function findFacebookConfigs(?TenantContextInterface $context = null, int $limit = 50, int $offset = 0): array;

    /**
     * Count Facebook configs for tenant.
     */
    public function countFacebookConfigs(?TenantContextInterface $context = null): int;

    /**
     * Find all ServiceConfigs for Google (service_id = 1), scoped to tenant.
     *
     * @return array<int, ServiceConfig>
     */
    public function findGoogleConfigs(?TenantContextInterface $context = null, int $limit = 50, int $offset = 0): array;

    /**
     * Count Google configs for tenant.
     */
    public function countGoogleConfigs(?TenantContextInterface $context = null): int;

    /**
     * Find a single SubService by ID, scoped to tenant context if not super admin.
     */
    public function findSubServiceById(int $id, ?TenantContextInterface $context = null): ?SubService;

    /**
     * Find all linked Instagram sub-services for a given ServiceConfig ID.
     *
     * @return array<int, SubService>
     */
    public function findSubServicesForConfig(int $serviceConfigId): array;

    /**
     * Find all linked social destinations across platforms for an account.
     *
     * @return array<int, ServiceConfig>
     */
    public function findAllLinkedForAccount(int $accountId): array;

    /**
     * Update label / emailLowReview for a ServiceConfig.
     */
    public function updateConfig(int $id, array $data, ?TenantContextInterface $context = null): bool;

    /**
     * Update the page_token JSON for a ServiceConfig (e.g. for OAuth token refresh persistence).
     */
    public function updatePageToken(int $id, string $tokenJson, ?TenantContextInterface $context = null): bool;

    /**
     * Revoke or unlink a connection (sets linked = 0).
     */
    public function unlinkConfig(int $id, ?TenantContextInterface $context = null): bool;

    /**
     * Delete a connection record.
     */
    public function deleteConfig(int $id, ?TenantContextInterface $context = null): bool;

    /**
     * @return array<int, Api>
     */
    public function findApisByService(int $serviceId): array;

    public function findApiById(int $id): ?Api;

    /**
     * @param array<string, mixed> $data
     */
    public function saveApi(array $data, ?int $id = null): ?int;

    public function deleteApi(int $id): bool;

    public function countAssignedPages(int $apiId): int;
}
