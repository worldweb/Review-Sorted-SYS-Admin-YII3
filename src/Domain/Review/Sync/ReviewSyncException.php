<?php

declare(strict_types=1);

namespace App\Domain\Review\Sync;

use RuntimeException;

/**
 * Exception thrown when a review synchronization channel encounters an unrecoverable error
 * such as missing credentials, token expiration, revocation, or upstream API authorization failure.
 */
final class ReviewSyncException extends RuntimeException
{
}
