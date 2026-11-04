<?php

declare(strict_types=1);

use App\Environment;

// Suppress vendor deprecations under PHP 8.5 pre-release
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Load .env configuration
$root = dirname(__DIR__);
$envFile = $root . '/.env';

if (file_exists($envFile)) {
    if (class_exists(\Dotenv\Dotenv::class)) {
        \Dotenv\Dotenv::createImmutable($root)->safeLoad();
    } else {
        // Fallback for minimal production setups without dev dependencies
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines !== false) {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                if (str_contains($line, '=')) {
                    [$key, $value] = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value, " \t\n\r\0\x0B\"'");
                    if (!isset($_SERVER[$key]) && !isset($_ENV[$key])) {
                        putenv("{$key}={$value}");
                        $_ENV[$key] = $value;
                        $_SERVER[$key] = $value;
                    }
                }
            }
        }
    }
}

Environment::prepare();

if (!function_exists('app_url')) {
    function app_url(string $path = ''): string
    {
        return Environment::url($path);
    }
}
