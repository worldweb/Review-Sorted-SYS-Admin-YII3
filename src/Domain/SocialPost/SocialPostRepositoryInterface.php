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
     * @param int $limit Maximum number of pending items to retrieve
     * @param list<string> $supportedPlatforms If non-empty, only destinations matching these platforms are returned
     * @return array<int, SocialPostDestination>
     */
    public function findPendingDestinations(int $limit = 20, array $supportedPlatforms = []): array;

    /**
     * Atomically claim a queued destination for processing.
     *
     * @param int $id Destination ID
     * @param string $lockTime Lock timestamp string (Y-m-d H:i:s)
     * @return bool True if successfully claimed, false if already claimed or not queued
     */
    public function claimPendingDestination(int $id, string $lockTime): bool;

    /**
     * Releases a claimed destination back to 'queued' status if its platform resolver is unavailable.
     *
     * @param int $id Destination ID
     * @param string $lockTime Lock timestamp string used during claiming
     * @return bool True if successfully released
     */
    public function releaseClaimedDestination(int $id, string $lockTime): bool;

    /**
     * Recovers stale processing destinations older than the given lease timeout.
     * If remote_post_id exists, recovers as published; if absent, marks as failed with 'Ambiguous delivery'.
     *
     * @param int $timeoutMinutes Lease expiration duration in minutes (default 15)
     * @return int Number of recovered destinations
     */
    public function recoverStaleDestinations(int $timeoutMinutes = 15): int;

    /**
     * Fenced atomic update marking a destination as published with its confirmed remote post ID.
     *
     * @param int $id Destination ID
     * @param string $remotePostId Remote platform post ID
     * @param string $lockTime Original lock timestamp for fencing
     * @return bool True if updated, false if lock expired or row was superseded
     */
    public function markDestinationPublished(int $id, string $remotePostId, string $lockTime): bool;

    /**
     * Fenced atomic update marking a destination as failed.
     *
     * @param int $id Destination ID
     * @param string $errorMessage Failure description
     * @param string $lockTime Original lock timestamp for fencing
     * @param bool $isAmbiguous Whether this failure represents an ambiguous delivery
     * @return bool True if updated
     */
    public function markDestinationFailed(int $id, string $errorMessage, string $lockTime, bool $isAmbiguous = false): bool;

    /**
     * Fenced atomic update rescheduling a destination for temporary failure retry.
     *
     * @param int $id Destination ID
     * @param int $retryCount New retry count
     * @param int $delaySeconds Delay in seconds before next attempt
     * @param string $lockTime Original lock timestamp for fencing
     * @return bool True if rescheduled
     */
    public function rescheduleDestination(int $id, int $retryCount, int $delaySeconds, string $lockTime): bool;

    /**
     * Find a single destination by ID with tenant access check.
     */
    public function findDestinationById(int $destinationId, ?TenantContextInterface $context = null): ?SocialPostDestination;

    /**
     * Retry a specific single destination.
     *
     * @param int $destinationId Destination ID
     * @param TenantContextInterface|null $context Tenant context
     * @param bool $isAmbiguousOverride Whether this retry is an explicit override of an ambiguous failure
     * @return bool True if successfully reset to queued, false if conditions not met
     */
    public function retryDestination(int $destinationId, ?TenantContextInterface $context = null, bool $isAmbiguousOverride = false): bool;

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
     * Retry normal failed destinations for a post (strictly ignores ambiguous delivery failures).
     */
    public function retryPost(int $id, ?TenantContextInterface $context = null): bool;
}
