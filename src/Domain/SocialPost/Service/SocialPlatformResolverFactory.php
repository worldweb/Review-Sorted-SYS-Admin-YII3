<?php

declare(strict_types=1);

namespace App\Domain\SocialPost\Service;

/**
 * Social Platform Resolver Factory implementation.
 *
 * For Step 1 (Foundation):
 * - No platform resolvers (Facebook, Instagram, Google) are registered yet.
 * - getSupportedPlatforms() returns an empty array.
 * - getResolver() returns null for all platforms.
 */
final class SocialPlatformResolverFactory implements SocialPlatformResolverFactoryInterface
{
    /**
     * @var array<string, SocialPlatformResolverInterface>
     */
    private array $resolvers = [];

    /**
     * @param iterable<SocialPlatformResolverInterface> $resolvers
     */
    public function __construct(iterable $resolvers = [])
    {
        foreach ($resolvers as $resolver) {
            $this->resolvers[strtolower($resolver->getPlatform())] = $resolver;
        }
    }

    public function getResolver(string $platform): ?SocialPlatformResolverInterface
    {
        $key = strtolower(trim($platform));
        return $this->resolvers[$key] ?? null;
    }

    public function getSupportedPlatforms(): array
    {
        return array_values(array_keys($this->resolvers));
    }
}

