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
        self::setString('APP_NAME', 'Reviews Sorted');
        self::setString('APP_URL', 'http://localhost/sys-yii3');
        self::setStringAllowEmpty('APP_BASE_PATH', '/sys-yii3');
        self::setString('APP_TIMEZONE', 'Australia/Perth');
        date_default_timezone_set(self::appTimezone());
        self::setString('FRONTEND_URL', 'http://localhost/sys-yii3');
        self::setString('BACKEND_URL', 'http://localhost/sys-yii3');
        self::setString('API_BASE_URL', 'http://localhost/sys-yii3/accountapi');
        self::setString('ASSET_BASE_URL', '/sys-yii3/assets');
        self::setNonEmptyStringOrNull('APP_HOST_PATH', null);

        // Filesystem directory paths
        self::setString('PUBLIC_DIR', dirname(__DIR__) . '/public');
        self::setString('RUNTIME_DIR', dirname(__DIR__) . '/runtime');
        self::setString('STORAGE_DIR', dirname(__DIR__) . '/runtime/storage');
        self::setString('UPLOAD_DIR', dirname(__DIR__) . '/public/assets/qr');

        // Database
        self::setString('DB_DRIVER', 'mysql');
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
        self::setString('FACEBOOK_REDIRECT_URI', 'http://localhost/sys-yii3/settings/facebook/callback');
        self::setString('FACEBOOK_VERIFY_TOKEN', self::getRawValue('FACEBOOK_VERIFY_TOKEN') ?? self::getRawValue('RS_FACEBOOK_VERIFY_TOKEN') ?? '');
        self::setString('GOOGLE_CLIENT_ID', '');
        self::setString('GOOGLE_CLIENT_SECRET', '');
        self::setString('GOOGLE_REDIRECT_URI', 'http://localhost/sys-yii3/settings/google/callback');

        // QR Code Custom Landing & Development Safe Host
        self::setString('QR_CODE_GENERATION_BASE_URL', self::getRawValue('QR_CODE_GENERATION_BASE_URL') ?? self::getRawValue('RS_QR_CODE_GENERATION_BASE_URL') ?? '');
        self::setString('LOCAL_QR_SAFE_HOST', self::getRawValue('LOCAL_QR_SAFE_HOST') ?? self::getRawValue('RS_LOCAL_QR_SAFE_HOST') ?? 'sys.test');

        // WYSIWYG / TinyMCE
        self::setString('TINYMCE_LICENSE_KEY', self::getRawValue('TINYMCE_LICENSE_KEY') ?? 'gpl');

        // Security / Cookies
        self::setBoolean('SESSION_COOKIE_SECURE', false);
        self::setBoolean('SESSION_COOKIE_HTTPONLY', true);
        self::setString('SESSION_COOKIE_SAMESITE', 'lax');
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

    public static function frontendUrl(): string
    {
        return (string) self::$values['FRONTEND_URL'];
    }

    public static function backendUrl(): string
    {
        return (string) self::$values['BACKEND_URL'];
    }

    public static function apiBaseUrl(): string
    {
        return (string) self::$values['API_BASE_URL'];
    }

    public static function assetBaseUrl(): string
    {
        return (string) self::$values['ASSET_BASE_URL'];
    }

    public static function appHostPath(): ?string
    {
        return self::$values['APP_HOST_PATH'] ?? null;
    }

    public static function appC3(): bool
    {
        return (bool) (self::$values['APP_C3'] ?? false);
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
    public static function dbDriver(): string
    {
        return (string) self::$values['DB_DRIVER'];
    }

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

    public static function googleClientId(): string
    {
        return (string) self::$values['GOOGLE_CLIENT_ID'];
    }

    public static function googleClientSecret(): string
    {
        return (string) self::$values['GOOGLE_CLIENT_SECRET'];
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

    // WYSIWYG Editor
    public static function tinymceLicenseKey(): string
    {
        return (string) (self::$values['TINYMCE_LICENSE_KEY'] ?? 'gpl');
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
