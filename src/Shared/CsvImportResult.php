<?php

declare(strict_types=1);

namespace App\Shared;

/**
 * Result data transfer object for CSV file parsing.
 */
final class CsvImportResult
{
    /**
     * @param string[] $headers
     * @param array<int, array<string, string>> $records
     * @param array<int, array{row: int, field: string, message: string}> $rowErrors
     * @param string|null $headerError
     */
    public function __construct(
        private array $headers = [],
        private array $records = [],
        private array $rowErrors = [],
        private ?string $headerError = null,
        private int $totalRowsProcessed = 0,
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->headerError === null && empty($this->rowErrors);
    }

    public function hasHeaderError(): bool
    {
        return $this->headerError !== null;
    }

    public function getHeaderError(): ?string
    {
        return $this->headerError;
    }

    /**
     * @return string[]
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    /**
     * @return array<int, array{row: int, field: string, message: string}>
     */
    public function getRowErrors(): array
    {
        return $this->rowErrors;
    }

    public function getTotalRowsProcessed(): int
    {
        return $this->totalRowsProcessed;
    }

    public function getValidCount(): int
    {
        return count($this->records);
    }

    public function getErrorCount(): int
    {
        return count($this->rowErrors);
    }
}
