<?php

declare(strict_types=1);

namespace App\Domain\SocialPost\Exception;

use Throwable;

/**
 * Exception thrown when network transport, DNS, or socket connection fails during a Google API call.
 */
final class GoogleNetworkException extends GoogleApiClientException
{
    public function __construct(
        string $message,
        private bool $isAmbiguousPossible = false,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Indicates whether this transport failure occurred during an operation where
     * the remote endpoint might have received and executed the publishing action.
     */
    public function isAmbiguousPossible(): bool
    {
        return $this->isAmbiguousPossible;
    }
}

