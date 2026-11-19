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
    public function syncGoogleReviews(?int $accountId = null, ?int $serviceConfigId = null, int $maxPages = 50): ReviewSyncResult;

    /**
     * Synchronizes Facebook reviews/recommendations for all accounts or a specific account / service config.
     */
    public function syncFacebookReviews(?int $accountId = null, ?int $serviceConfigId = null, int $maxPages = 50): ReviewSyncResult;

    /**
     * Synchronizes all connected channels across both platforms.
     */
    public function syncAll(int $maxPages = 50): ReviewSyncResult;

    /**
     * Synchronizes reviews for a specific Facebook Page by platform Page ID (used by webhooks).
     */
    public function syncFacebookPageReviewsByPlatformId(string $pageId): ReviewSyncResult;

    /**
     * Synchronizes a keyset-chunk of review channels (ordered by id ASC).
     *
     * @param int|null $accountId Scoped account ID (or null for all accounts)
     * @param int|null $serviceConfigId Specific channel ID (or null for all channels)
     * @param int $lastChannelId Primary key ID of the last processed channel (0 for initial chunk)
     * @param int $limit Maximum number of channels to process in this chunk (default: 10)
     * @param int $maxPages Maximum API pages per channel (default: 2)
     * @return array{
     *     total_channels: int,
     *     channels_processed: int,
     *     last_channel_id: int,
     *     has_more: bool,
     *     reviews_fetched: int,
     *     reviews_inserted: int,
     *     reviews_updated: int,
     *     errors: array<int, string>
     * }
     */
    public function syncChunk(
        ?int $accountId = null,
        ?int $serviceConfigId = null,
        int $lastChannelId = 0,
        int $limit = 10,
        int $maxPages = 2
    ): array;
}

