<?php

declare(strict_types=1);

namespace App\Domain\Review\Channel;

use App\Domain\Review\ReplyDispatchResult;
use App\Domain\Review\Review;

/**
 * Interface for Facebook Graph API review and comment reply client.
 */
interface FacebookReplyClientInterface
{
    /**
     * Sends a review/comment reply to the Facebook Graph API.
     *
     * @param Review $review The review entity being replied to
     * @param string $message The reply text
     * @param array<string, mixed> $configRow Configuration and credentials from service_config, service, and api tables
     */
    public function reply(Review $review, string $message, array $configRow): ReplyDispatchResult;
}
