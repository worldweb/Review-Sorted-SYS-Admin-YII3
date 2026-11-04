<?php

declare(strict_types=1);

namespace App\Domain\SocialPost\DTO;

enum DispatchOutcome: string
{
    case SUCCESS = 'success';
    case TEMPORARY_FAILURE = 'temporary_failure';
    case PERMANENT_FAILURE = 'permanent_failure';
    case AMBIGUOUS_FAILURE = 'ambiguous_failure';

    public function isSuccess(): bool
    {
        return $this === self::SUCCESS;
    }

    public function isRetryable(): bool
    {
        return $this === self::TEMPORARY_FAILURE;
    }

    public function isAmbiguous(): bool
    {
        return $this === self::AMBIGUOUS_FAILURE;
    }

    public function isPermanentFailure(): bool
    {
        return $this === self::PERMANENT_FAILURE;
    }
}

