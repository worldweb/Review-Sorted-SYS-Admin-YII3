<?php

declare(strict_types=1);

namespace App\Domain\Review\Sync;

/**
 * Value object summarizing the results of a review synchronization execution.
 */
final class ReviewSyncResult
{
    /**
     * @param array<int, string> $errors
     */
    public function __construct(
        private int $channelsProcessed = 0,
        private int $reviewsFetched = 0,
        private int $reviewsInserted = 0,
        private int $reviewsUpdated = 0,
        private int $reviewsUnchanged = 0,
        private array $errors = [],
    ) {
    }

    public function addChannelResult(
        int $fetched,
        int $inserted,
        int $updated,
        int $unchanged,
        ?string $error = null
    ): void {
        $this->channelsProcessed++;
        $this->reviewsFetched += $fetched;
        $this->reviewsInserted += $inserted;
        $this->reviewsUpdated += $updated;
        $this->reviewsUnchanged += $unchanged;

        if ($error !== null && $error !== '') {
            $this->errors[] = $error;
        }
    }

    public function merge(self $other): self
    {
        return new self(
            channelsProcessed: $this->channelsProcessed + $other->getChannelsProcessed(),
            reviewsFetched: $this->reviewsFetched + $other->getReviewsFetched(),
            reviewsInserted: $this->reviewsInserted + $other->getReviewsInserted(),
            reviewsUpdated: $this->reviewsUpdated + $other->getReviewsUpdated(),
            reviewsUnchanged: $this->reviewsUnchanged + $other->getReviewsUnchanged(),
            errors: array_merge($this->errors, $other->getErrors())
        );
    }

    public function getChannelsProcessed(): int
    {
        return $this->channelsProcessed;
    }

    public function getReviewsFetched(): int
    {
        return $this->reviewsFetched;
    }

    public function getReviewsInserted(): int
    {
        return $this->reviewsInserted;
    }

    public function getReviewsUpdated(): int
    {
        return $this->reviewsUpdated;
    }

    public function getReviewsUnchanged(): int
    {
        return $this->reviewsUnchanged;
    }

    /**
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'channels_processed' => $this->channelsProcessed,
            'reviews_fetched' => $this->reviewsFetched,
            'reviews_inserted' => $this->reviewsInserted,
            'reviews_updated' => $this->reviewsUpdated,
            'reviews_unchanged' => $this->reviewsUnchanged,
            'errors' => $this->errors,
            'has_errors' => $this->hasErrors(),
        ];
    }
}

