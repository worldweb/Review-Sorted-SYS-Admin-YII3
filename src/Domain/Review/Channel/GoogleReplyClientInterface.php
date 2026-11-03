<?php

declare(strict_types=1);

namespace App\Domain\Review\Channel;

use App\Domain\Review\ReplyDispatchResult;
use App\Domain\Review\Review;

/**
 * Interface for Google Business Profile (MyBusiness) review reply client.
 */
interface GoogleReplyClientInterface
{
    /**
     * Sends a review reply to the Google MyBusiness API.
     *
     * @param Review $review The review entity being replied to
     * @param string $message The reply text
     * @param array<string, mixed> $configRow Configuration and credentials from service_config, service, and api tables
     */
    public function reply(Review $review, string $message, array $configRow): ReplyDispatchResult;
}
