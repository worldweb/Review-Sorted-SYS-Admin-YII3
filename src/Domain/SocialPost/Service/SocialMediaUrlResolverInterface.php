<?php

declare(strict_types=1);

namespace App\Domain\SocialPost\Service;

use InvalidArgumentException;

/**
 * Service interface for resolving public, fully-qualified HTTP/HTTPS URLs for social post media attachments.
 */
interface SocialMediaUrlResolverInterface
{
    /**
     * Resolves a stored media path or URL into an absolute, publicly accessible HTTP(S) URL.
     *
     * @param string|null $mediaPath Stored relative path (e.g. '/uploads/social-post/sp_123.jpg') or absolute URL
     * @return string|null Resolved absolute public URL, or null if input is empty
     * @throws InvalidArgumentException If the media path is invalid, traverses directories, or has an unsupported scheme / non-public host
     */
    public function resolvePublicUrl(?string $mediaPath): ?string;
}

