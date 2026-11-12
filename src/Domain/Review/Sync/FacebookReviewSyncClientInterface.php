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
     * Fetches all reviews and ratings from Facebook Graph API for the given connected Page.
     *
     * @param ServiceConfig|array<string, mixed> $config
     * @return array<int, array<string, mixed>>
     */
    public function fetchReviews(ServiceConfig|array $config): array;
}

