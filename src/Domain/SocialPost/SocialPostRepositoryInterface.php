<?php

declare(strict_types=1);

namespace App\Domain\SocialPost;

use App\Security\TenantContextInterface;

/**
 * Interface for Social Post Repository.
 */
interface SocialPostRepositoryInterface
{
    /**
     * Find a single post by ID including destinations.
     */
    public function findById(int $id, ?TenantContextInterface $context = null): ?SocialPost;

    /**
     * Find all posts for the current tenant context.
     *
     * @param array<string, mixed> $filters
     * @return array<int, SocialPost>
     */
    public function findAllPosts(?TenantContextInterface $context = null, int $limit = 20, int $offset = 0, ?string $typeFilter = null, array $filters = []): array;

    /**
     * Count total posts matching criteria.
     *
     * @param array<string, mixed> $filters
     */
    public function countPosts(?TenantContextInterface $context = null, ?string $typeFilter = null, array $filters = []): int;

    /**
     * Find pending queued destinations ready to be published.
     *
     * @return array<int, SocialPostDestination>
     */
    public function findPendingDestinations(int $limit = 20): array;

    /**
     * Create a new post and its associated destinations.
     *
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>> $destinations
     */
    public function createPost(array $data, array $destinations, ?TenantContextInterface $context = null): ?int;

    /**
     * Update post content / schedule.
     */
    public function updatePost(int $id, array $data, ?TenantContextInterface $context = null): bool;

    /**
     * Cancel a scheduled post.
     */
    public function cancelPost(int $id, ?TenantContextInterface $context = null): bool;

    /**
     * Delete a post and its destinations.
     */
    public function deletePost(int $id, ?TenantContextInterface $context = null): bool;

    /**
     * Retry failed destinations for a post.
     */
    public function retryPost(int $id, ?TenantContextInterface $context = null): bool;
}
