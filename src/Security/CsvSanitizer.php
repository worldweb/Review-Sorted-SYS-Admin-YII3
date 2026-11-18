<?php

declare(strict_types=1);

namespace App\Security;

/**
 * CsvSanitizer.
 * Defends against CSV Formula Injection (CWE-1236).
 * Escapes cells beginning with formula trigger characters (=, +, -, @, \t, \r) by prepending a single quote (').
 */
final class CsvSanitizer
{
    private const FORMULA_TRIGGERS = ['=', '+', '-', '@', "\t", "\r"];

    public static function escapeCell(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        if ($value !== '' && in_array($value[0], self::FORMULA_TRIGGERS, true)) {
            return "'" . $value;
        }

        return $value;
    }

    /**
     * @param array<int|string, mixed> $row
     * @return array<int|string, mixed>
     */
    public static function escapeRow(array $row): array
    {
        $escaped = [];
        foreach ($row as $key => $cell) {
            $escaped[$key] = self::escapeCell($cell);
        }
        return $escaped;
    }
}
