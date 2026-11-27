<?php

declare(strict_types=1);

namespace App\Domain\Review\Sync;

use App\Domain\SocialConnection\ServiceConfig;

/**
 * Contract for fetching reviews and recommendations from Facebook Graph API.
 */
interface FacebookReviewSyncClientInterface
{
    /**
     * Fetches reviews and ratings from Facebook Graph API for the given connected Page.
     * Handles pagination up to $maxPages.
     *
     * @param ServiceConfig|array<string, mixed> $config
     * @param int $maxPages Maximum API pages to fetch (default: 2)
     * @return array<int, array<string, mixed>>
     */
    public function fetchReviews(ServiceConfig|array $config, int $maxPages = 2): array;

    /**
     * Fetches Facebook Page tagged mentions (/{pageId}/tagged).
     *
     * @param ServiceConfig|array<string, mixed> $config
     * @return array<int, array<string, mixed>>
     */
    public function fetchPageMentions(ServiceConfig|array $config, ?string $lastObjId = null, int $maxPages = 5): array;

    /**
     * Fetches Instagram Business tagged mentions (/{instaId}/tags).
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchInstagramMentions(
        string $instaObjId,
        string $pageToken,
        string $label,
        ?string $lastObjId = null,
        int $maxPages = 5,
    ): array;

    /**
     * Fetches Facebook Page post comments/replies (/{pageId}/feed -> /{postId}/comments).
     *
     * @param ServiceConfig|array<string, mixed> $config
     * @return array<int, array<string, mixed>>
     */
    public function fetchPostReplies(ServiceConfig|array $config, int $maxPages = 2): array;
}

