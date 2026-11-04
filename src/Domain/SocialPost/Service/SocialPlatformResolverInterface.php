<?php

declare(strict_types=1);

namespace App\Domain\SocialPost\Service;

use App\Domain\SocialPost\DTO\PlatformDispatchResult;
use App\Domain\SocialPost\SocialPost;
use App\Domain\SocialPost\SocialPostDestination;

/**
 * Interface for platform-specific social publishing resolvers (e.g. Facebook, Instagram, Google).
 */
interface SocialPlatformResolverInterface
{
    /**
     * Identifies the platform slug this resolver handles (e.g. 'facebook', 'instagram', 'google').
     */
    public function getPlatform(): string;

    /**
     * Publishes a post to the remote destination and classifies the outcome.
     *
     * @param SocialPost $post The parent post entity
     * @param SocialPostDestination $destination The destination delivery target
     * @return PlatformDispatchResult The classified result with outcome and remote ID or error
     */
    public function dispatch(SocialPost $post, SocialPostDestination $destination): PlatformDispatchResult;
}

