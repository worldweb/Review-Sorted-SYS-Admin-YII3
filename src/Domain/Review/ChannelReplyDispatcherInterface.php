<?php

declare(strict_types=1);

namespace App\Domain\Review;

/**
 * Interface for channel reply dispatchers.
 * Abstracts Google MyBusiness and Facebook Graph API review reply interactions.
 */
interface ChannelReplyDispatcherInterface
{
    /**
     * Dispatches a review reply to the appropriate third-party platform.
     */
    public function dispatch(Review $review, string $message): ReplyDispatchResult;
}
