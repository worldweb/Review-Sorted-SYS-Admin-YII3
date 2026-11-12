<?php

declare(strict_types=1);

namespace App\Domain\Review;

use App\Security\TenantContextInterface;

/**
 * Review Repository Interface.
 * Defines safe, tenant-isolated read-only query operations for reviews and replies.
 */
interface ReviewRepositoryInterface
{
    /**
     * Finds a single Review by its ID with full joined metadata (channel, service, replies count).
     * Enforces TenantContext boundary.
     */
    public function findById(int $id, ?TenantContextInterface $context = null): ?Review;

    /**
     * Executes a paginated, filtered search of reviews enforcing TenantContext.
     *
     * @return array{reviews: Review[], total: int}
     */
    public function search(ReviewSearchFilter $filter, ?TenantContextInterface $context = null): array;

    /**
     * Retrieves KPI metrics (Total Reviews, Unread Reviews) scoped by TenantContext.
     *
     * @return array{total: int, unread: int}
     */
    public function getMetrics(?TenantContextInterface $context = null): array;

    /**
     * Retrieves all reply records for a given review in chronological order.
     *
     * @return ReviewReply[]
     */
    public function getRepliesForReview(int $reviewId, ?TenantContextInterface $context = null): array;

    /**
     * Returns an associative array of service configs (connected channels/pages) for filter dropdowns.
     *
     * @return array<int, string>
     */
    public function getServiceConfigOptions(?TenantContextInterface $context = null): array;

    /**
     * Returns standard service channel options (Google, Facebook, Instagram).
     *
     * @return array<int, string>
     */
    public function getServiceOptions(): array;

    /**
     * Retrieves review data formatted for CSV export based on date range and tenant restrictions.
     * Returns an iterable cursor generator to support low-memory streaming.
     *
     * @param array{date_range?: string, review_date_min?: string, review_date_max?: string} $params
     * @return iterable<int, array{date: string, customer: string, page: string, rating: string|float|null, status: string, text: string}>
     */
    public function getExportData(array $params, ?TenantContextInterface $context = null): iterable;

    /**
     * Automatically marks a review as read when an authorized user opens it.
     * Enforces TenantContext boundary.
     */
    public function markAsRead(int $reviewId, ?TenantContextInterface $context = null): bool;

    /**
     * Safely deletes a review and cascades deletion to associated replies.
     * Enforces TenantContext boundary.
     */
    public function delete(int $reviewId, ?TenantContextInterface $context = null): bool;

    /**
     * Persists or updates a review reply in the database.
     * Enforces TenantContext boundary.
     */
    public function saveReply(
        int $reviewId,
        int $userId,
        string $message,
        string $objId,
        string $replyDate,
        ?string $replyExtra = null,
        ?TenantContextInterface $context = null,
    ): ?ReviewReply;

    /**
     * Retrieves reviews by a list of IDs for bulk processing.
     * Enforces TenantContext boundary and restricts to TYPE_REVIEW.
     *
     * @param int[] $ids
     * @return Review[]
     */
    public function findReviewsByIds(array $ids, ?TenantContextInterface $context = null): array;

    /**
     * Finds a review by its external platform object ID (Google reviewId or Facebook open_graph_story.id).
     */
    public function findReviewByObjectId(string $objId): ?Review;

    /**
     * Finds a review by customer details for Google duplicate fallback.
     */
    public function findReviewByCustomerDetails(string $customerName, string $reviewDate, string $reviewText, ?int $serviceConfigId = null): ?Review;

    /**
     * Safely upserts a review record with deduplication and change detection.
     *
     * @param array<string, mixed> $data
     * @return array{review_id: int, action: string, changed: bool}
     */
    public function upsertReview(array $data): array;

    /**
     * Idempotently imports or updates an external review reply comment (e.g. from Google or Facebook).
     */
    public function insertOrUpdateReviewReplyComment(
        int $reviewId,
        int $accountId,
        int $serviceConfigId,
        string $objId,
        ?string $customerId,
        ?string $customerName,
        string $replyText,
        string $replyDate,
        ?string $replyExtra = null
    ): ?int;

    /**
     * Retrieves all existing review object IDs for a given service config ID.
     *
     * @return string[]
     */
    public function findExistingObjectIdsForConfig(int $serviceConfigId): array;
}
