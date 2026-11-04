<?php

declare(strict_types=1);

namespace App\Domain\SocialPost\Service;

/**
 * Interface for the Social Platform Resolver Factory.
 * Manages discovery and retrieval of platform-specific publishing resolvers.
 */
interface SocialPlatformResolverFactoryInterface
{
    /**
     * Resolves the platform-specific publishing resolver for a given platform slug.
     *
     * @param string $platform Platform identifier (e.g. 'facebook', 'instagram', 'google')
     * @return SocialPlatformResolverInterface|null The resolver if registered, null otherwise
     */
    public function getResolver(string $platform): ?SocialPlatformResolverInterface;

    /**
     * Returns the list of all registered and supported platform slugs.
     *
     * @return list<string>
     */
    public function getSupportedPlatforms(): array;
}

