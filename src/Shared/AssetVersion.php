<?php

declare(strict_types=1);

namespace App\Shared;

use App\Environment;

/**
 * Provides automatic asset versioning (cache-busting) for CSS and JavaScript files.
 * Appends a file modification timestamp query parameter (?v=...) to local asset URLs.
 */
final class AssetVersion
{
    /**
     * In-memory cache to prevent redundant stat calls within the same request.
     * @var array<string, string>
     */
    private static array $cache = [];

    /**
     * Custom public directory override (primarily for testing).
     */
    private static ?string $publicDir = null;

    /**
     * Resolves the physical path to the public webroot.
     */
    public static function getPublicDir(): string
    {
        if (self::$publicDir !== null) {
            return self::$publicDir;
        }

        $envDir = Environment::get('PUBLIC_DIR');
        if (is_string($envDir) && $envDir !== '' && is_dir($envDir)) {
            return self::$publicDir = rtrim($envDir, '/\\');
        }

        return self::$publicDir = dirname(__DIR__, 2) . '/public';
    }

    /**
     * Overrides public directory (used in testing).
     */
    public static function setPublicDir(?string $dir): void
    {
        self::$publicDir = $dir;
        self::$cache = [];
    }

    /**
     * Appends an automatic file modification version (?v=<timestamp>) to a local asset URL.
     * External CDN URLs and non-existent files are returned unchanged without warnings.
     *
     * @param string $url Asset URL (e.g., '/themes/modern/css/layout.css' or full URL)
     * @return string Versioned asset URL
     */
    public static function append(string $url): string
    {
        if (isset(self::$cache[$url])) {
            return self::$cache[$url];
        }

        if ($url === '') {
            return self::$cache[$url] = $url;
        }

        // If URL points to an external domain (e.g. cdnjs, fonts.googleapis.com), return unchanged
        if (preg_match('#^(https?:)?//#i', $url)) {
            $parsedHost = parse_url($url, PHP_URL_HOST);
            $appHost = parse_url(Environment::appUrl(), PHP_URL_HOST);
            if ($parsedHost !== null && $appHost !== null && !hash_equals($appHost, $parsedHost)) {
                return self::$cache[$url] = $url;
            }
        }

        $parsedPath = parse_url($url, PHP_URL_PATH);
        if ($parsedPath === null || $parsedPath === '') {
            return self::$cache[$url] = $url;
        }

        // Strip application base path (e.g. '/sys-yii3' on local) to get relative path inside public/
        $basePath = Environment::appBasePath();
        if ($basePath !== '' && str_starts_with($parsedPath, $basePath)) {
            $parsedPath = substr($parsedPath, strlen($basePath));
        }

        $relativeSubpath = ltrim($parsedPath, '/');
        $publicDir = self::getPublicDir();
        $filePath = $publicDir . '/' . $relativeSubpath;

        if (!is_file($filePath)) {
            return self::$cache[$url] = $url;
        }

        $mtime = @filemtime($filePath);
        if ($mtime === false) {
            return self::$cache[$url] = $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';
        return self::$cache[$url] = $url . $separator . 'v=' . $mtime;
    }

    /**
     * Retrieves the version string (modification timestamp) for a local asset, or null if not found.
     */
    public static function version(string $url): ?string
    {
        $versioned = self::append($url);
        if (preg_match('/[?&]v=([0-9]+)/', $versioned, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Clears the runtime in-memory cache.
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
}

