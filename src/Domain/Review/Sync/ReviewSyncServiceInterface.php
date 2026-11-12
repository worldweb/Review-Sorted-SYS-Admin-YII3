<?php

declare(strict_types=1);

namespace App\Domain\Review\Sync;

/**
 * ReviewSyncServiceInterface.
 * Orchestrates fetching, parsing, deduplication, safe upsert, and notification
 * for Google Business Profile and Facebook Page reviews.
 */
interface ReviewSyncServiceInterface
{
    /**
     * Synchronizes Google reviews for all eligible locations, a specific account, or a specific service config ID.
     */
    public function syncGoogleReviews(?int $accountId = null, ?int $serviceConfigId = null): ReviewSyncResult;

    /**
     * Synchronizes Facebook reviews/recommendations for all accounts or a specific account / service config.
     */
    public function syncFacebookReviews(?int $accountId = null, ?int $serviceConfigId = null): ReviewSyncResult;

    /**
     * Synchronizes all connected channels across both platforms.
     */
    public function syncAll(): ReviewSyncResult;

    /**
     * Synchronizes reviews for a specific Facebook Page by platform Page ID (used by webhooks).
     */
    public function syncFacebookPageReviewsByPlatformId(string $pageId): ReviewSyncResult;
}

