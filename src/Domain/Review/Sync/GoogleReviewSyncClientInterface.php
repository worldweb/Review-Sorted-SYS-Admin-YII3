<?php

declare(strict_types=1);

namespace App\Domain\Review\Sync;

use App\Domain\SocialConnection\ServiceConfig;

/**
 * Contract for fetching reviews from Google Business Profile API.
 */
interface GoogleReviewSyncClientInterface
{
    /**
     * Fetches reviews from Google Business Profile API for the given connected location.
     * Handles pagination up to $maxPages and automatic OAuth access token refresh.
     *
     * @param ServiceConfig|array<string, mixed> $config
     * @param int $maxPages Maximum API pages to fetch (default: 2)
     * @return array<int, array<string, mixed>>
     */
    public function fetchReviews(ServiceConfig|array $config, int $maxPages = 2): array;
}

