<?php

declare(strict_types=1);

namespace App\Shared;

use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

/**
 * Reusable native PHP CSV Import Service.
 * RFC 4180-compatible stream-based parsing without external library dependencies.
 */
final class CsvImportService
{
    public const DEFAULT_MAX_FILE_SIZE = 5242880; // 5 MB
    public const DEFAULT_MAX_ROWS = 5000;

    /**
     * Parse an uploaded file or file path into structured records.
     *
     * @param UploadedFileInterface|string $source Uploaded file object or local file path
     * @param string[] $expectedHeaders List of required lowercase header names
     * @param int $maxFileSize Maximum allowed bytes (default 5MB)
     * @param int $maxRows Maximum data rows allowed to process (default 5000)
     */
    public function parse(
        UploadedFileInterface|string $source,
        array $expectedHeaders,
        int $maxFileSize = self::DEFAULT_MAX_FILE_SIZE,
        int $maxRows = self::DEFAULT_MAX_ROWS,
    ): CsvImportResult {
        $tempPath = null;
        $isTemporaryCopy = false;

        if ($source instanceof UploadedFileInterface) {
            if ($source->getError() !== UPLOAD_ERR_OK) {
                return new CsvImportResult(
                    headerError: $this->getUploadErrorMessage($source->getError())
                );
            }

            $size = $source->getSize();
            if ($size !== null && $size > $maxFileSize) {
                return new CsvImportResult(
                    headerError: sprintf('Uploaded file exceeds maximum limit of %d MB.', (int) ($maxFileSize / 1048576))
                );
            }

            // Stream to a safe temporary file for reading
            $tempPath = tempnam(sys_get_temp_dir(), 'csv_import_');
            if ($tempPath === false) {
                return new CsvImportResult(headerError: 'Could not allocate temporary storage for import.');
            }
            $source->moveTo($tempPath);
            $isTemporaryCopy = true;
        } else {
            if (!file_exists($source) || !is_readable($source)) {
                return new CsvImportResult(headerError: 'Source file could not be opened for reading.');
            }
            if (filesize($source) > $maxFileSize) {
                return new CsvImportResult(
                    headerError: sprintf('File exceeds maximum limit of %d MB.', (int) ($maxFileSize / 1048576))
                );
            }
            $tempPath = $source;
        }

        try {
            return $this->parseFileStream($tempPath, $expectedHeaders, $maxRows);
        } finally {
            if ($isTemporaryCopy && $tempPath !== null && file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    /**
     * @param string $filePath
     * @param string[] $expectedHeaders
     * @param int $maxRows
     */
    private function parseFileStream(string $filePath, array $expectedHeaders, int $maxRows): CsvImportResult
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return new CsvImportResult(headerError: 'Failed to open file stream.');
        }

        try {
            // Read first line and strip UTF-8 BOM if present
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                return new CsvImportResult(headerError: 'The uploaded CSV file is empty.');
            }

            // Strip UTF-8 BOM: EF BB BF
            if (str_starts_with($firstLine, "\xEF\xBB\xBF")) {
                $firstLine = substr($firstLine, 3);
            }

            // Parse header line
            $headers = str_getcsv(trim($firstLine), ',', '"', '\\');
            if (empty($headers) || empty(array_filter($headers))) {
                return new CsvImportResult(headerError: 'Invalid or missing CSV header row.');
            }

            // Normalize actual headers: lowercase, trim whitespace
            $normalizedActualHeaders = array_map(static fn ($h): string => strtolower(trim((string) $h)), $headers);
            $normalizedExpectedHeaders = array_map(static fn ($h): string => strtolower(trim((string) $h)), $expectedHeaders);

            // Compare required headers
            $missingHeaders = array_diff($normalizedExpectedHeaders, $normalizedActualHeaders);
            if (!empty($missingHeaders)) {
                return new CsvImportResult(
                    headers: $normalizedActualHeaders,
                    headerError: sprintf(
                        'Invalid CSV headers. Expected columns: %s. Found: %s.',
                        implode(', ', $normalizedExpectedHeaders),
                        implode(', ', $normalizedActualHeaders)
                    )
                );
            }

            $records = [];
            $rowErrors = [];
            $rowNumber = 1; // 1 is header row

            while (($data = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                $rowNumber++;

                // Stop if row limit exceeded
                if ($rowNumber > $maxRows + 1) {
                    $rowErrors[] = [
                        'row' => $rowNumber,
                        'field' => 'general',
                        'message' => sprintf('Row limit of %d exceeded. Remaining rows skipped.', $maxRows),
                    ];
                    break;
                }

                // Check for empty row (single null/empty element)
                if (count($data) === 1 && (trim((string) $data[0]) === '' || $data[0] === null)) {
                    continue;
                }

                // Check column count match
                if (count($data) !== count($normalizedActualHeaders)) {
                    $rowErrors[] = [
                        'row' => $rowNumber,
                        'field' => 'columns',
                        'message' => sprintf('Column count mismatch. Expected %d, found %d.', count($normalizedActualHeaders), count($data)),
                    ];
                    continue;
                }

                $record = [];
                foreach ($normalizedActualHeaders as $idx => $headerName) {
                    $record[$headerName] = trim((string) ($data[$idx] ?? ''));
                }

                $records[] = $record;
            }

            return new CsvImportResult(
                headers: $normalizedActualHeaders,
                records: $records,
                rowErrors: $rowErrors,
                headerError: null,
                totalRowsProcessed: $rowNumber - 1,
            );
        } finally {
            fclose($handle);
        }
    }

    private function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Uploaded file exceeds server size limits.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder on server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'File upload was blocked by a server extension.',
            default => 'An error occurred during file upload.',
        };
    }
}
