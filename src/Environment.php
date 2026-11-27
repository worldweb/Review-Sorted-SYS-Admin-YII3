<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

use function in_array;
use function sprintf;

final class Environment
{
    public const DEV = 'dev';
    public const LOCAL = 'local';
    public const TEST = 'test';
    public const STAGING = 'staging';
    public const PROD = 'prod';
    public const PRODUCTION = 'production';

    public const ENVIRONMENTS = [
        self::DEV,
        self::LOCAL,
        self::TEST,
        self::STAGING,
        self::PROD,
        self::PRODUCTION,
    ];

    private static array $values = [];

    public static function prepare(): void
    {
        self::setEnvironment();
        self::setBoolean('APP_C3', false);
        self::setBoolean('APP_DEBUG', false);
        self::setString('APP_KEY', '');
        self::setString('APP_NAME', 'Reviews Sorted');
        self::setString('APP_URL', 'http://localhost/sys-yii3');
        self::setStringAllowEmpty('APP_BASE_PATH', '/sys-yii3');
        self::setString('APP_TIMEZONE', 'Australia/Perth');
        date_default_timezone_set(self::appTimezone());
        self::setNonEmptyStringOrNull('APP_HOST_PATH', null);

        // Filesystem directory paths
        self::setString('PUBLIC_DIR', dirname(__DIR__) . '/public');
        self::setString('RUNTIME_DIR', dirname(__DIR__) . '/runtime');
        self::setString('STORAGE_DIR', dirname(__DIR__) . '/runtime/storage');
        self::setString('UPLOAD_DIR', dirname(__DIR__) . '/public/assets/qr');

        // Database
        self::setString('DB_HOST', 'localhost');
        self::setString('DB_PORT', '3306');
        self::setString('DB_NAME', 'sortedreviews_sys');
        self::setString('DB_USER', 'root');
        self::setString('DB_PASSWORD', '');
        self::setString('DB_CHARSET', 'utf8mb4');
        self::setString('DB_TABLE_PREFIX', '');

        // Email / SMTP & Mailgun
        self::setString('MAIL_HOST', '');
        self::setInteger('MAIL_PORT', 587);
        self::setString('MAIL_USERNAME', '');
        self::setString('MAIL_PASSWORD', '');
        self::setString('MAIL_ENCRYPTION', 'tls');
        self::setString('MAIL_FROM_ADDRESS', 'noreply@reviewssorted.com');
        self::setString('MAIL_FROM_NAME', 'Reviews Sorted');
        self::setBoolean('MAILGUN_ENABLED', false);
        self::setString('MAILGUN_API_KEY', '');
        self::setString('MAILGUN_DOMAIN', 'reviewssorted.com');
        self::setString('MAILGUN_ENDPOINT', 'https://api.eu.mailgun.net');

        // Meta WhatsApp Cloud API
        self::setBoolean('WHATSAPP_ENABLED', false);
        self::setString('WHATSAPP_TOKEN', self::getRawValue('WHATSAPP_TOKEN') ?? self::getRawValue('RS_WHATSAPP_ACCESS_TOKEN') ?? '');
        self::setString('WHATSAPP_PHONE_NUMBER_ID', self::getRawValue('WHATSAPP_PHONE_NUMBER_ID') ?? self::getRawValue('RS_WHATSAPP_PHONE_NUMBER_ID') ?? '');
        self::setString('WHATSAPP_API_VERSION', self::getRawValue('WHATSAPP_API_VERSION') ?? self::getRawValue('RS_WHATSAPP_API_VERSION') ?? 'v18.0');

        // Stripe
        self::setString('STRIPE_MODE', 'test');
        self::setString('STRIPE_TEST_PUBLIC_KEY', '');
        self::setString('STRIPE_TEST_SECRET_KEY', '');
        self::setString('STRIPE_TEST_WEBHOOK_SECRET', '');
        self::setString('STRIPE_LIVE_PUBLIC_KEY', '');
        self::setString('STRIPE_LIVE_SECRET_KEY', '');
        self::setString('STRIPE_LIVE_WEBHOOK_SECRET', '');

        // Social Platforms & Webhooks
        self::setString('FACEBOOK_APP_ID', '');
        self::setString('FACEBOOK_APP_SECRET', '');
        self::setString('FACEBOOK_REDIRECT_URI', '');
        self::setString('FACEBOOK_VERIFY_TOKEN', self::getRawValue('FACEBOOK_VERIFY_TOKEN') ?? self::getRawValue('RS_FACEBOOK_VERIFY_TOKEN') ?? '');
        self::setString('GOOGLE_CLIENT_ID', '');
        self::setString('GOOGLE_CLIENT_SECRET', '');
        self::setString('GOOGLE_REDIRECT_URI', '');

        // QR Code Custom Landing & Development Safe Host
        self::setString('QR_CODE_GENERATION_BASE_URL', self::getRawValue('QR_CODE_GENERATION_BASE_URL') ?? self::getRawValue('RS_QR_CODE_GENERATION_BASE_URL') ?? '');
        self::setString('LOCAL_QR_SAFE_HOST', self::getRawValue('LOCAL_QR_SAFE_HOST') ?? self::getRawValue('RS_LOCAL_QR_SAFE_HOST') ?? 'sys.test');

        // Security / Cookies
        self::setBoolean('SESSION_COOKIE_SECURE', false);
        self::setBoolean('SESSION_COOKIE_HTTPONLY', true);
        self::setString('SESSION_COOKIE_SAMESITE', 'lax');

        // Cron Logging
        self::setBoolean('CRON_LOG_ENABLED', true);

        // Validate production secrets if running in genuine production
        self::validateProductionConfig();
    }

    /**
     * Validates required production secrets to prevent silent startup failures.
     * Only throws if running in genuine production (not local, dev, or localhost testbeds).
     */
    public static function validateProductionConfig(): void
    {
        $issues = self::getProductionSecretIssues();
        if (!empty($issues) && self::isStrictProduction()) {
            throw new RuntimeException(
                'Production configuration validation failed. The following required setting(s) are missing or invalid: '
                . implode(', ', $issues)
                . '. Please configure these environment variables before starting the production application.'
            );
        }
    }

    /**
     * Checks for missing or unconfigured production secrets.
     * Returns an array of descriptive issue messages (without exposing any secret values).
     *
     * @return list<string>
     */
    public static function getProductionSecretIssues(): array
    {
        $issues = [];

        if (self::isStrictProduction() || self::isProd()) {
            $keyIssue = self::validateAppKey(self::appKey());
            if ($keyIssue !== null) {
                $issues[] = $keyIssue;
            }
        }

        if (self::isStrictProduction()) {
            if (self::dbName() === '') {
                $issues[] = 'DB_NAME must not be empty';
            }

            if (str_starts_with(self::appUrl(), 'https://') && !self::sessionCookieSecure()) {
                $issues[] = 'SESSION_COOKIE_SECURE must be true when application URL is HTTPS';
            }
        }

        if (self::mailgunEnabled()) {
            if (trim(self::mailgunApiKey()) === '') {
                $issues[] = 'MAILGUN_API_KEY must be configured when MAILGUN_ENABLED is true';
            }
            if (trim(self::mailgunDomain()) === '') {
                $issues[] = 'MAILGUN_DOMAIN must be configured when MAILGUN_ENABLED is true';
            }
        }

        if (self::whatsappEnabled()) {
            if (trim(self::whatsappToken()) === '') {
                $issues[] = 'WHATSAPP_TOKEN must be configured when WHATSAPP_ENABLED is true';
            }
            if (trim(self::whatsappPhoneNumberId()) === '') {
                $issues[] = 'WHATSAPP_PHONE_NUMBER_ID must be configured when WHATSAPP_ENABLED is true';
            }
        }

        if (self::stripeMode() === 'live') {
            if (trim(self::stripeSecretKey()) === '') {
                $issues[] = 'STRIPE_LIVE_SECRET_KEY must be configured when STRIPE_MODE is live';
            }
            if (trim(self::stripeWebhookSecret()) === '') {
                $issues[] = 'STRIPE_LIVE_WEBHOOK_SECRET must be configured when STRIPE_MODE is live';
            }
        }

        if (trim(self::facebookAppId()) !== '' && trim(self::facebookAppSecret()) === '') {
            $issues[] = 'FACEBOOK_APP_SECRET must be configured when FACEBOOK_APP_ID is set';
        }

        if (trim(self::googleClientId()) !== '' && trim(self::googleClientSecret()) === '') {
            $issues[] = 'GOOGLE_CLIENT_SECRET must be configured when GOOGLE_CLIENT_ID is set';
        }

        return $issues;
    }

    /**
     * Identifies whether the application is running in a live production environment
     * rather than a developer workstation, test suite, or local development server.
     */
    public static function isStrictProduction(): bool
    {
        if (self::isDev() || self::isTest()) {
            return false;
        }

        $appUrl = strtolower(self::appUrl());
        if (
            str_contains($appUrl, 'localhost')
            || str_contains($appUrl, '127.0.0.1')
            || str_contains($appUrl, '.test')
            || str_contains($appUrl, '.local')
            || str_contains($appUrl, 'sys-yii3')
        ) {
            return false;
        }

        return self::isProd();
    }

    public static function appEnv(): string
    {
        return (string) self::$values['APP_ENV'];
    }

    public static function isDev(): bool
    {
        $env = self::appEnv();
        return $env === self::DEV || $env === self::LOCAL;
    }

    public static function isTest(): bool
    {
        return self::appEnv() === self::TEST;
    }

    public static function isProd(): bool
    {
        $env = self::appEnv();
        return $env === self::PROD || $env === self::PRODUCTION;
    }

    public static function appDebug(): bool
    {
        return (bool) self::$values['APP_DEBUG'];
    }

    public static function appName(): string
    {
        return (string) self::$values['APP_NAME'];
    }

    public static function appUrl(): string
    {
        return (string) self::$values['APP_URL'];
    }

    public static function appBasePath(): string
    {
        $path = (string) self::$values['APP_BASE_PATH'];
        if ($path === '/' || $path === '') {
            return '';
        }
        return '/' . trim($path, '/');
    }

    public static function url(string $path = ''): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
            return $path;
        }
        $base = self::appBasePath();
        if (str_starts_with($path, '/sys-yii3/')) {
            $path = substr($path, 9);
        } elseif ($path === '/sys-yii3') {
            $path = '/';
        }
        $cleanPath = '/' . ltrim($path, '/');
        if ($cleanPath === '/') {
            return $base !== '' ? $base : '/';
        }
        return ($base !== '' ? $base : '') . $cleanPath;
    }

    public static function appTimezone(): string
    {
        return (string) self::$values['APP_TIMEZONE'];
    }

    public static function appHostPath(): ?string
    {
        return self::$values['APP_HOST_PATH'] ?? null;
    }

    public static function appC3(): bool
    {
        return (bool) (self::$values['APP_C3'] ?? false);
    }

    public static function cronLogEnabled(): bool
    {
        return (bool) (self::$values['CRON_LOG_ENABLED'] ?? true);
    }

    public static function appKey(): string
    {
        return (string) (self::$values['APP_KEY'] ?? '');
    }

    /**
     * Validates that an APP_KEY is cryptographically secure and formatted as an encoded 32-byte (256-bit) key.
     * Supported formats:
     * 1. Exactly 64 hexadecimal characters representing 32 bytes (e.g. openssl rand -hex 32).
     * 2. Valid Base64 that strictly decodes to exactly 32 bytes, with optional 'base64:' prefix (e.g. openssl rand -base64 32).
     *
     * @return string|null Error description if invalid, or null if valid.
     */
    public static function validateAppKey(string $key): ?string
    {
        $clean = trim($key);
        if ($clean === '') {
            return 'APP_KEY must be configured in production environment';
        }

        // 1. Base64 with 'base64:' prefix
        if (str_starts_with($clean, 'base64:')) {
            $rawB64 = substr($clean, 7);
            $decoded = base64_decode($rawB64, true);
            if ($decoded === false || strlen($decoded) !== 32) {
                return 'APP_KEY with base64: prefix is malformed or does not represent exactly 32 bytes';
            }
            return null;
        }

        // 2. Exactly 64-character hexadecimal string representing 32 bytes
        if (strlen($clean) === 64 && ctype_xdigit($clean)) {
            return null;
        }

        // 3. Standard 44-character Base64 string representing exactly 32 bytes
        if (strlen($clean) === 44 && preg_match('/^[A-Za-z0-9+\/]{43}=$/', $clean)) {
            $decoded = base64_decode($clean, true);
            if ($decoded !== false && strlen($decoded) === 32) {
                return null;
            }
        }

        return 'APP_KEY is weak, malformed, or invalid. Production requires a cryptographically secure 32-byte key encoded as either 64 hexadecimal characters or Base64 strictly decoding to 32 bytes (e.g. openssl rand -hex 32 or openssl rand -base64 32)';
    }

    // Session / Cookies
    public static function sessionCookieSecure(): bool
    {
        return (bool) (self::$values['SESSION_COOKIE_SECURE'] ?? false);
    }

    public static function sessionCookieHttpOnly(): bool
    {
        return (bool) (self::$values['SESSION_COOKIE_HTTPONLY'] ?? true);
    }

    public static function sessionCookieSameSite(): string
    {
        return (string) (self::$values['SESSION_COOKIE_SAMESITE'] ?? 'lax');
    }

    // Database
    public static function dbHost(): string
    {
        return (string) self::$values['DB_HOST'];
    }

    public static function dbPort(): string
    {
        return (string) self::$values['DB_PORT'];
    }

    public static function dbName(): string
    {
        return (string) self::$values['DB_NAME'];
    }

    public static function dbUser(): string
    {
        return (string) self::$values['DB_USER'];
    }

    public static function dbPassword(): string
    {
        return (string) self::$values['DB_PASSWORD'];
    }

    public static function dbCharset(): string
    {
        return (string) self::$values['DB_CHARSET'];
    }

    public static function dbTablePrefix(): string
    {
        return (string) self::$values['DB_TABLE_PREFIX'];
    }

    // Stripe
    public static function stripeMode(): string
    {
        return (string) self::$values['STRIPE_MODE'];
    }

    public static function stripePublicKey(): string
    {
        return self::stripeMode() === 'live'
            ? (string) self::$values['STRIPE_LIVE_PUBLIC_KEY']
            : (string) self::$values['STRIPE_TEST_PUBLIC_KEY'];
    }

    public static function stripeSecretKey(): string
    {
        return self::stripeMode() === 'live'
            ? (string) self::$values['STRIPE_LIVE_SECRET_KEY']
            : (string) self::$values['STRIPE_TEST_SECRET_KEY'];
    }

    public static function stripeWebhookSecret(): string
    {
        return self::stripeMode() === 'live'
            ? (string) self::$values['STRIPE_LIVE_WEBHOOK_SECRET']
            : (string) self::$values['STRIPE_TEST_WEBHOOK_SECRET'];
    }

    // Social
    public static function facebookAppId(): string
    {
        return (string) self::$values['FACEBOOK_APP_ID'];
    }

    public static function facebookAppSecret(): string
    {
        return (string) self::$values['FACEBOOK_APP_SECRET'];
    }

    public static function facebookRedirectUri(): string
    {
        $uri = trim((string) (self::$values['FACEBOOK_REDIRECT_URI'] ?? ''));
        return $uri !== '' ? $uri : self::url('/settings/facebook/callback');
    }

    public static function googleClientId(): string
    {
        return (string) self::$values['GOOGLE_CLIENT_ID'];
    }

    public static function googleClientSecret(): string
    {
        return (string) self::$values['GOOGLE_CLIENT_SECRET'];
    }

    public static function googleRedirectUri(): string
    {
        $uri = trim((string) (self::$values['GOOGLE_REDIRECT_URI'] ?? ''));
        return $uri !== '' ? $uri : self::url('/settings/google/callback');
    }

    // Mailgun
    public static function mailgunEnabled(): bool
    {
        return (bool) (self::$values['MAILGUN_ENABLED'] ?? false);
    }

    public static function mailgunApiKey(): string
    {
        return (string) (self::$values['MAILGUN_API_KEY'] ?? '');
    }

    public static function mailgunDomain(): string
    {
        return (string) (self::$values['MAILGUN_DOMAIN'] ?? 'reviewssorted.com');
    }

    public static function mailgunEndpoint(): string
    {
        return (string) (self::$values['MAILGUN_ENDPOINT'] ?? 'https://api.eu.mailgun.net');
    }

    // WhatsApp
    public static function whatsappEnabled(): bool
    {
        return (bool) (self::$values['WHATSAPP_ENABLED'] ?? false);
    }

    public static function whatsappToken(): string
    {
        return (string) (self::$values['WHATSAPP_TOKEN'] ?? '');
    }

    public static function whatsappPhoneNumberId(): string
    {
        return (string) (self::$values['WHATSAPP_PHONE_NUMBER_ID'] ?? '');
    }

    public static function whatsappApiVersion(): string
    {
        return (string) (self::$values['WHATSAPP_API_VERSION'] ?? 'v18.0');
    }

    // Facebook / Social Webhooks
    public static function facebookVerifyToken(): string
    {
        return (string) (self::$values['FACEBOOK_VERIFY_TOKEN'] ?? '');
    }

    // QR Code
    public static function qrCodeGenerationBaseUrl(): ?string
    {
        $val = trim((string) (self::$values['QR_CODE_GENERATION_BASE_URL'] ?? ''));
        return $val !== '' ? $val : null;
    }

    public static function localQrSafeHost(): string
    {
        $val = trim((string) (self::$values['LOCAL_QR_SAFE_HOST'] ?? ''));
        return $val !== '' ? $val : 'sys.test';
    }

    // Helper to get raw value
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$values[$key] ?? self::getRawValue($key) ?? $default;
    }

    private static function setEnvironment(): void
    {
        $raw = self::getRawValue('APP_ENV');
        $environment = $raw ? strtolower($raw) : self::PROD;

        // Map dev/local to dev for internal runner compatibility
        if ($environment === 'local') {
            $environment = self::DEV;
        } elseif ($environment === 'staging' || $environment === 'production') {
            $environment = self::PROD;
        }

        if (!in_array($environment, self::ENVIRONMENTS, true)) {
            $environment = self::PROD;
        }

        self::$values['APP_ENV'] = $environment;
    }

    private static function setBoolean(string $key, bool $default): void
    {
        $value = self::getRawValue($key);
        self::$values[$key] = $value === null
            ? $default
            : (filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default);
    }

    private static function setInteger(string $key, int $default): void
    {
        $value = self::getRawValue($key);
        self::$values[$key] = $value === null || $value === '' ? $default : (int) $value;
    }

    private static function setString(string $key, string $default): void
    {
        $value = self::getRawValue($key);
        self::$values[$key] = $value !== null && $value !== '' ? $value : $default;
    }

    private static function setStringAllowEmpty(string $key, string $default): void
    {
        $value = self::getRawValue($key);
        self::$values[$key] = $value !== null ? $value : $default;
    }

    private static function setNonEmptyStringOrNull(string $key, ?string $default): void
    {
        $value = self::getRawValue($key);
        self::$values[$key] = $value === null || $value === '' ? $default : $value;
    }

    private static function getRawValue(string $key): ?string
    {
        $value = getenv($key, true);
        if ($value !== false && $value !== null) {
            return (string) $value;
        }

        $value = getenv($key);
        if ($value !== false && $value !== null) {
            return (string) $value;
        }

        if (isset($_ENV[$key])) {
            return (string) $_ENV[$key];
        }

        if (isset($_SERVER[$key])) {
            return (string) $_SERVER[$key];
        }

        return null;
    }
}
