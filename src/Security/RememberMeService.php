<?php

declare(strict_types=1);

namespace App\Security;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * RememberMeService.
 * Handles secure, encrypted/signed remember-me cookies matching Yii 2 duration rules (30 days).
 * Operates safely without requiring database write privileges.
 */
final class RememberMeService
{
    public const COOKIE_NAME = '_rs_identity';
    public const DURATION = 3600 * 24 * 30; // 30 days matching Yii 2

    public function __construct(
        private UserAuthenticationRepositoryInterface $authRepo,
    ) {
    }

    /**
     * Generates a signed authentication cookie attached to the response.
     */
    public function createRememberCookie(UserAuthIdentity $identity, ResponseInterface $response): ResponseInterface
    {
        $userId = $identity->getId();
        $expires = time() + self::DURATION;
        $signature = $this->generateSignature($userId, $expires);

        $payload = base64_encode(json_encode([
            'uid' => $userId,
            'exp' => $expires,
            'sig' => $signature,
        ], JSON_THROW_ON_ERROR));

        $cookieHeader = sprintf(
            '%s=%s; Path=/; Max-Age=%d; HttpOnly; SameSite=Lax',
            self::COOKIE_NAME,
            urlencode($payload),
            self::DURATION
        );

        return $response->withAddedHeader('Set-Cookie', $cookieHeader);
    }

    /**
     * Extracts and validates an identity from the request's remember-me cookie.
     */
    public function extractIdentityFromCookie(ServerRequestInterface $request): ?UserAuthIdentity
    {
        $cookies = $request->getCookieParams();
        $rawCookie = $cookies[self::COOKIE_NAME] ?? null;

        if (!is_string($rawCookie) || $rawCookie === '') {
            return null;
        }

        try {
            $data = json_decode(base64_decode(urldecode($rawCookie)), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        if (!is_array($data)) {
            return null;
        }

        $userId = (int) ($data['uid'] ?? 0);
        $expires = (int) ($data['exp'] ?? 0);
        $signature = (string) ($data['sig'] ?? '');

        if ($userId <= 0 || $expires <= time() || $signature === '') {
            return null;
        }

        // Verify HMAC signature using timing-safe comparison
        $expectedSignature = $this->generateSignature($userId, $expires);
        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        // Retrieve identity and verify it is still active
        $identity = $this->authRepo->findAuthIdentityById($userId);
        if ($identity === null || !$identity->isActive()) {
            return null;
        }

        return $identity;
    }

    /**
     * Clears the remember-me cookie on the response.
     */
    public function clearRememberCookie(ResponseInterface $response): ResponseInterface
    {
        $cookieHeader = sprintf(
            '%s=deleted; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT; Max-Age=0; HttpOnly; SameSite=Lax',
            self::COOKIE_NAME
        );

        return $response->withAddedHeader('Set-Cookie', $cookieHeader);
    }

    /**
     * Generate an HMAC-SHA256 signature binding the user ID and expiration to an application secret.
     */
    private function generateSignature(int $userId, int $expires): string
    {
        $secret = $_ENV['APP_KEY'] ?? 'ReviewsSortedMigrationAuthSecret2026!';
        return hash_hmac('sha256', "{$userId}|{$expires}", $secret);
    }
}
