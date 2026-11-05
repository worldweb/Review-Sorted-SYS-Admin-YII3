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

    /**
     * Finds a ServiceConfig by service ID and platform page/location ID, scoped to tenant context if provided.
     */
    public function findConfigByPlatformPageId(int $serviceId, string $pageId, ?TenantContextInterface $context = null): ?ServiceConfig;

    /**
     * Upsert a Facebook Page connection in service_config.
     * Prevents duplicate rows by matching (account_id, service_id = 2, page_id).
     *
     * @param array{id: string, name: string, access_token: string, location?: array} $pageData
     */
    public function upsertFacebookPage(int $accountId, ?int $apiId, array $pageData, ?string $userToken = null): ?ServiceConfig;

    /**
     * Upsert an Instagram SubService linked to a Facebook Page ServiceConfig.
     *
     * @param array{id: string, username: string} $instaData
     */
    public function upsertInstagramSubService(int $accountId, int $serviceConfigId, array $instaData): ?SubService;

    /**
     * Upsert a Google Business Profile location connection in service_config.
     * Prevents duplicate rows by matching (account_id, service_id = 1, page_id).
     *
     * @param array{name: string, title?: string, storeCode?: string, account_name?: string} $locationData
     * @param array<string, mixed> $tokenPayload
     */
    public function upsertGoogleLocation(int $accountId, ?int $apiId, array $locationData, array $tokenPayload, bool $linked = true): ?ServiceConfig;

    /**
     * Link selected Google location service configurations (set linked = 1).
     *
     * @param array<int> $serviceConfigIds
     */
    public function linkGoogleLocations(array $serviceConfigIds, ?TenantContextInterface $context = null): bool;

    /**
     * Find unlinked Google locations (linked = 0) for the current tenant.
     *
     * @return array<int, ServiceConfig>
     */
    public function findNotLinkedGoogleConfigs(?TenantContextInterface $context = null): array;
}
