<?php

declare(strict_types=1);

namespace App\Domain\SocialPost\Service;

use App\Domain\SocialPost\DTO\PlatformDispatchResult;
use App\Domain\SocialPost\SocialPostDestination;

/**
 * Interface for the Social Posting Service.
 * Orchestrates platform resolver dispatch, retry backoff, outcome handling, and atomic completion fencing.
 */
interface SocialPostingServiceInterface
{
    /**
     * Dispatches a claimed destination to its platform resolver and updates its lifecycle state.
     *
     * @param SocialPostDestination $destination The destination currently claimed in 'processing' status
     * @param string $lockTime The exact locked_at timestamp string used when claiming the destination
     * @return PlatformDispatchResult The final dispatch result
     */
    public function processDestination(SocialPostDestination $destination, string $lockTime): PlatformDispatchResult;
}

