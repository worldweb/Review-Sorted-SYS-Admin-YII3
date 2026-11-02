<?php

declare(strict_types=1);

use App\Environment;

// Suppress vendor deprecations under PHP 8.5 pre-release
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Load .env for non-Docker/non-container environments.
// Existing process environment variables take precedence (Docker, CI, server config).
// phpdotenv is a dev dependency — not available in production (composer install --no-dev).
if (empty($_ENV['APP_ENV']) && class_exists(\Dotenv\Dotenv::class)) {
    \Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
}

Environment::prepare();
