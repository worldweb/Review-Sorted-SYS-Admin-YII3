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
     * Fetches all reviews from Google Business Profile API for the given connected location.
     * Handles pagination and automatic OAuth access token refresh.
     *
     * @param ServiceConfig|array<string, mixed> $config
     * @return array<int, array<string, mixed>>
     */
    public function fetchReviews(ServiceConfig|array $config): array;
}

