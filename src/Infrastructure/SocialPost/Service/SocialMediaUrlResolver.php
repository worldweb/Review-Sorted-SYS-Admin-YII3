<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialPost\Service;

use App\Domain\SocialPost\Service\SocialMediaUrlResolverInterface;
use App\Environment;
use InvalidArgumentException;

/**
 * Resolves media paths stored on the local application server into absolute, publicly reachable URLs.
 * Uses canonical application configuration rather than untrusted client request headers.
 */
final class SocialMediaUrlResolver implements SocialMediaUrlResolverInterface
{
    public function __construct(
        private bool $allowLocalhost = false,
        private ?string $baseUrl = null,
    ) {
    }

    public function resolvePublicUrl(?string $mediaPath): ?string
    {
        if ($mediaPath === null || trim($mediaPath) === '') {
            return null;
        }

        $trimmed = trim($mediaPath);

        // Security check: reject directory traversal attempts
        if (str_contains($trimmed, '..')) {
            throw new InvalidArgumentException('Directory traversal sequences ("..") are prohibited in media paths.');
        }

        // Security check: reject raw Windows or UNC local filesystem paths
        if (preg_match('/^[a-zA-Z]:[\\\\\/]/', $trimmed) === 1 || str_starts_with($trimmed, '\\\\')) {
            throw new InvalidArgumentException('Local filesystem paths cannot be used as media URLs.');
        }

        // Check if already an absolute HTTP/HTTPS URL
        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
            $absoluteUrl = $trimmed;
        } else {
            // Must be a relative web path, e.g. /uploads/social-post/sp_123.jpg
            $base = $this->baseUrl ?? Environment::appUrl();
            if (trim($base) === '') {
                throw new InvalidArgumentException('Canonical application URL (APP_URL) is not configured.');
            }

            $cleanBase = rtrim($base, '/');
            $cleanPath = '/' . ltrim($trimmed, '/');
            $absoluteUrl = $cleanBase . $cleanPath;
        }

        // Validate URL format
        if (filter_var($absoluteUrl, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException(sprintf('Generated media URL "%s" is not a valid URL.', $absoluteUrl));
        }

        $parts = parse_url($absoluteUrl);
        $scheme = strtolower($parts['scheme'] ?? '');
        if ($scheme !== 'http' && $scheme !== 'https') {
            throw new InvalidArgumentException(sprintf('Unsupported URL scheme "%s". Only HTTP and HTTPS are permitted.', $scheme));
        }

        $host = strtolower($parts['host'] ?? '');
        if ($host === '') {
            throw new InvalidArgumentException('Media URL does not specify a valid host.');
        }

        // Check whether host is publicly accessible
        if (!$this->allowLocalhost && !$this->isPublicHost($host)) {
            throw new InvalidArgumentException(sprintf(
                'The media host "%s" is a local loopback or private network address and cannot be accessed by external platforms. A publicly reachable canonical APP_URL is required.',
                $host
            ));
        }

        return $absoluteUrl;
    }

    /**
     * Checks if a hostname or IP is publicly routable across the public internet.
     */
    private function isPublicHost(string $host): bool
    {
        if (
            $host === 'localhost'
            || $host === '127.0.0.1'
            || $host === '::1'
            || str_ends_with($host, '.local')
            || str_ends_with($host, '.internal')
            || str_ends_with($host, '.test')
        ) {
            return false;
        }

        // If it's an IP address, verify it's not a private or reserved range
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $isPublicIp = filter_var(
                $host,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );
            return $isPublicIp !== false;
        }

        // Single-label hostnames without a domain dot (e.g. "intranet", "nas") are internal
        if (!str_contains($host, '.')) {
            return false;
        }

        return true;
    }
}

