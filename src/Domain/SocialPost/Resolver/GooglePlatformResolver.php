<?php

declare(strict_types=1);

namespace App\Domain\SocialPost\Resolver;

use App\Domain\SocialConnection\ServiceConfig;
use App\Domain\SocialConnection\SocialConnectionRepositoryInterface;
use App\Domain\SocialPost\Client\GoogleApiClientInterface;
use App\Domain\SocialPost\DTO\PlatformDispatchResult;
use App\Domain\SocialPost\Exception\GoogleApiException;
use App\Domain\SocialPost\Exception\GoogleNetworkException;
use App\Domain\SocialPost\Service\SocialMediaUrlResolverInterface;
use App\Domain\SocialPost\Service\SocialPlatformResolverInterface;
use App\Domain\SocialPost\SocialPost;
use App\Domain\SocialPost\SocialPostDestination;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Platform resolver for publishing to Google Business Profile via MyBusiness v4 Local Posts API.
 *
 * Supports Text posts and Image/Photo posts (topicType: STANDARD).
 * Enforces OAuth access token validation, automatic refresh, and persistence.
 * Prohibits video publishing (unsupported by GBP Local Posts API).
 */
final class GooglePlatformResolver implements SocialPlatformResolverInterface
{
    private const PLATFORM = 'google';
    private const MAX_CONTENT_LENGTH = 1500;

    public function __construct(
        private SocialConnectionRepositoryInterface $connectionRepo,
        private GoogleApiClientInterface $googleClient,
        private SocialMediaUrlResolverInterface $mediaUrlResolver,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function getPlatform(): string
    {
        return self::PLATFORM;
    }

    public function dispatch(SocialPost $post, SocialPostDestination $destination): PlatformDispatchResult
    {
        $destId = $destination->getId();

        // 1. Validate platform target
        if ($destination->getPlatform() !== self::PLATFORM) {
            return PlatformDispatchResult::permanentFailure(
                sprintf('Invalid platform "%s" targeted by GooglePlatformResolver.', $destination->getPlatform())
            );
        }

        // 2. Pre-flight Video rejection: Google GBP Local Posts API does not support video files
        $mediaType = $post->getMediaType();
        $mediaUrl = $post->getMediaUrl();
        if (
            $mediaType === SocialPost::MEDIA_VIDEO
            || (!empty($mediaUrl) && preg_match('/\.(mp4|mov|avi|wmv)$/i', parse_url($mediaUrl, PHP_URL_PATH) ?? $mediaUrl))
        ) {
            return PlatformDispatchResult::permanentFailure(
                'Google Business Profile Local Posts only support static images (PHOTO). Video files cannot be published to Google Business Profile.'
            );
        }

        // 3. Validate ServiceConfig ID
        $configId = $destination->getServiceConfigId();
        if ($configId === null || $configId <= 0) {
            return PlatformDispatchResult::permanentFailure(
                'Destination is missing a valid Google service configuration ID.'
            );
        }

        // 4. Retrieve Google connection
        $serviceConfig = $this->connectionRepo->findById($configId);
        if ($serviceConfig === null) {
            return PlatformDispatchResult::permanentFailure(
                sprintf('Google service configuration #%d not found.', $configId)
            );
        }

        // 5. Strict tenant isolation check
        if (
            $serviceConfig->getAccountId() !== $destination->getAccountId()
            || $serviceConfig->getAccountId() !== $post->getAccountId()
        ) {
            $this->logger?->error(sprintf(
                '[GooglePlatformResolver] Tenant ownership violation for destination #%d: DestAccount=%d, PostAccount=%d, ConfigAccount=%d',
                $destId,
                $destination->getAccountId(),
                $post->getAccountId(),
                (int) $serviceConfig->getAccountId()
            ));

            return PlatformDispatchResult::permanentFailure(
                'Tenant ownership mismatch: destination account does not match connected Google Business Profile account.'
            );
        }

        // 6. Validate connection status and configuration
        if (!$serviceConfig->isLinked()) {
            return PlatformDispatchResult::permanentFailure(
                'Google Business Profile connection is deactivated or unlinked. Please reconnect your Google account in channel settings.'
            );
        }

        if (!$serviceConfig->isGoogle()) {
            return PlatformDispatchResult::permanentFailure(
                'Target service configuration is not a Google connection.'
            );
        }

        $pageTokenRaw = trim((string) $serviceConfig->getPageToken());
        if ($pageTokenRaw === '') {
            return PlatformDispatchResult::permanentFailure(
                'Google Business Profile connection is missing OAuth authorization tokens. Please reconnect your Google account in channel settings.'
            );
        }

        // 7. Parse OAuth tokens & check expiration
        $tokenData = json_decode($pageTokenRaw, true);
        if (!is_array($tokenData) || empty($tokenData['access_token'])) {
            return PlatformDispatchResult::permanentFailure(
                'Invalid Google OAuth token format in channel configuration. Re-authentication is required.'
            );
        }

        $accessToken = (string) $tokenData['access_token'];
        $isExpired = false;
        if (isset($tokenData['created'], $tokenData['expires_in'])) {
            // Buffer of 60 seconds before actual expiration
            $isExpired = ((int) $tokenData['created'] + (int) $tokenData['expires_in'] - 60) < time();
        }

        // 8. Automatic OAuth Token Refresh if expired
        if ($isExpired) {
            $refreshToken = (string) ($tokenData['refresh_token'] ?? '');
            if (trim($refreshToken) === '') {
                return PlatformDispatchResult::permanentFailure(
                    'Google OAuth access token is expired and no refresh token is available. Please re-authenticate your Google channel.'
                );
            }

            $credentials = $this->resolveOAuthCredentials($serviceConfig);
            if ($credentials === null) {
                return PlatformDispatchResult::permanentFailure(
                    'Google OAuth client credentials not found in system configuration. Please configure Google API credentials in channel settings.'
                );
            }

            try {
                $refreshed = $this->googleClient->refreshToken(
                    $refreshToken,
                    $credentials['client_id'],
                    $credentials['client_secret']
                );

                $accessToken = (string) $refreshed['access_token'];
                $tokenData['access_token'] = $accessToken;
                $tokenData['created'] = time();
                if (isset($refreshed['expires_in'])) {
                    $tokenData['expires_in'] = (int) $refreshed['expires_in'];
                }
                if (!empty($refreshed['refresh_token'])) {
                    $tokenData['refresh_token'] = (string) $refreshed['refresh_token'];
                }

                // Persist refreshed token data to database
                $this->connectionRepo->updatePageToken(
                    $configId,
                    (string) json_encode($tokenData, JSON_UNESCAPED_SLASHES)
                );

                $this->logger?->info(sprintf(
                    '[GooglePlatformResolver] Successfully refreshed and persisted OAuth access token for Google config #%d.',
                    $configId
                ));
            } catch (GoogleApiException $e) {
                return $this->classifyApiException($e, $destId);
            } catch (Throwable $e) {
                $this->logger?->warning(sprintf(
                    '[GooglePlatformResolver] Destination #%d token refresh failed: %s',
                    $destId,
                    $e->getMessage()
                ));
                return PlatformDispatchResult::temporaryFailure(
                    'Failed to refresh Google OAuth access token: ' . $e->getMessage()
                );
            }
        }

        // 9. Content length validation
        $content = $post->getContent();
        if (mb_strlen($content) > self::MAX_CONTENT_LENGTH) {
            return PlatformDispatchResult::permanentFailure(
                sprintf('Google Business Profile post content exceeds the maximum limit of %s characters.', number_format(self::MAX_CONTENT_LENGTH))
            );
        }

        // 10. Media validation
        $publicMediaUrl = null;
        if ($mediaType === SocialPost::MEDIA_IMAGE) {
            if (empty($mediaUrl)) {
                return PlatformDispatchResult::permanentFailure(
                    'Media type is "image" but media attachment path is empty.'
                );
            }

            try {
                $publicMediaUrl = $this->mediaUrlResolver->resolvePublicUrl($mediaUrl);
                if ($publicMediaUrl === null) {
                    return PlatformDispatchResult::permanentFailure('Media URL could not be resolved into a public URL.');
                }
            } catch (InvalidArgumentException $e) {
                return PlatformDispatchResult::permanentFailure(
                    'Media URL validation failed: ' . $e->getMessage()
                );
            }
        } elseif (trim($content) === '') {
            return PlatformDispatchResult::permanentFailure(
                'Google Business Profile text post cannot be empty.'
            );
        }

        // 11. Resolve target location parent resource name
        $parent = $this->resolveLocationParent($serviceConfig);
        if ($parent === null) {
            return PlatformDispatchResult::permanentFailure(
                'Google Business Profile configuration lacks a valid location identifier (page_name/token).'
            );
        }

        // 12. Build Local Post payload
        $postData = [
            'languageCode' => 'en-US',
            'summary' => $content,
            'topicType' => 'STANDARD',
        ];

        if ($mediaType === SocialPost::MEDIA_IMAGE && !empty($publicMediaUrl)) {
            $postData['media'] = [
                [
                    'mediaFormat' => 'PHOTO',
                    'sourceUrl' => $publicMediaUrl,
                ],
            ];
        }

        // 13. Execute remote publishing request
        try {
            $response = $this->googleClient->createLocalPost($parent, $postData, $accessToken);
            $remoteId = (string) ($response['name'] ?? '');

            if (trim($remoteId) === '') {
                return PlatformDispatchResult::ambiguousFailure(
                    'Ambiguous delivery: Google API responded with HTTP success but omitted a valid remote post name.',
                    200
                );
            }

            $this->logger?->info(sprintf(
                '[GooglePlatformResolver] Successfully published destination #%d to Google Business Profile %s (Remote ID: %s)',
                $destId,
                $parent,
                $remoteId
            ));

            return PlatformDispatchResult::success($remoteId, 200, $response);
        } catch (GoogleApiException $e) {
            return $this->classifyApiException($e, $destId);
        } catch (GoogleNetworkException $e) {
            // Socket timeout, DNS drop, or connection reset during POST publishing
            $ambiguousMsg = sprintf(
                'Ambiguous delivery: Network connection dropped or timed out during Google publishing request: %s',
                $e->getMessage()
            );
            $this->logger?->warning(sprintf('[GooglePlatformResolver] Destination #%d: %s', $destId, $ambiguousMsg));

            return PlatformDispatchResult::ambiguousFailure($ambiguousMsg);
        } catch (Throwable $e) {
            $ambiguousMsg = sprintf(
                'Ambiguous delivery: Unexpected exception during Google publishing: %s',
                $e->getMessage()
            );
            $this->logger?->error(sprintf('[GooglePlatformResolver] Destination #%d: %s', $destId, $ambiguousMsg));

            return PlatformDispatchResult::ambiguousFailure($ambiguousMsg);
        }
    }

    /**
     * Resolves Google OAuth client_id and client_secret from the linked Api record
     * or active system Google API configuration.
     *
     * @return array{client_id: string, client_secret: string}|null
     */
    private function resolveOAuthCredentials(ServiceConfig $serviceConfig): ?array
    {
        $api = null;
        if ($serviceConfig->getApiId() !== null && $serviceConfig->getApiId() > 0) {
            $api = $this->connectionRepo->findApiById($serviceConfig->getApiId());
        }

        // Fallback: search available API records configured for Google (service_id = 1)
        if ($api === null || empty($api->getClientJson())) {
            $apis = $this->connectionRepo->findApisByService(ServiceConfig::SERVICE_GOOGLE);
            foreach ($apis as $candidate) {
                if (!empty($candidate->getClientJson())) {
                    $api = $candidate;
                    break;
                }
            }
        }

        if ($api !== null && !empty($api->getClientJson())) {
            $clientJsonStr = $api->getClientJson();
            $clientConfig = json_decode($clientJsonStr, true);
            if (is_array($clientConfig)) {
                $clientInfo = $clientConfig['web'] ?? $clientConfig['installed'] ?? $clientConfig;
                $clientId = (string) ($clientInfo['client_id'] ?? '');
                $clientSecret = (string) ($clientInfo['client_secret'] ?? '');

                if ($clientId !== '' && $clientSecret !== '') {
                    return [
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                    ];
                }
            }
        }

        // Secondary fallback: check direct client_id and token/key fields on Api entity
        if ($api !== null && !empty($api->getClientId()) && !empty($api->getToken())) {
            return [
                'client_id' => (string) $api->getClientId(),
                'client_secret' => (string) $api->getToken(),
            ];
        }

        // Tertiary fallback: environment variables
        $envClientId = (string) ($_ENV['GOOGLE_CLIENT_ID'] ?? getenv('GOOGLE_CLIENT_ID') ?: '');
        $envClientSecret = (string) ($_ENV['GOOGLE_CLIENT_SECRET'] ?? getenv('GOOGLE_CLIENT_SECRET') ?: '');
        if ($envClientId !== '' && $envClientSecret !== '') {
            return [
                'client_id' => $envClientId,
                'client_secret' => $envClientSecret,
            ];
        }

        return null;
    }

    /**
     * Resolves the parent location resource path for the Local Posts endpoint.
     * Supported formats:
     * - accounts/{accountId}/locations/{locationId}
     * - locations/{locationId}
     *
     * Validates that the location component is a genuine Google resource identifier
     * (e.g. 'locations/123456789' or numeric '123456789'), prioritizing valid resource
     * IDs in page_id/page_name over human-readable business names.
     * Normalizes pure numeric account IDs with the required 'accounts/' prefix.
     */
    private function resolveLocationParent(ServiceConfig $config): ?string
    {
        $pageName = trim((string) $config->getPageName());
        $pageId = trim((string) $config->getPageId());
        $token = trim((string) $config->getToken());

        // 1. Direct full parent resource match (accounts/{accountId}/locations/{locationId})
        if ($this->isFullLocationPath($pageName)) {
            return trim($pageName, '/');
        }
        if ($this->isFullLocationPath($pageId)) {
            return trim($pageId, '/');
        }
        if ($this->isFullLocationPath($token)) {
            return trim($token, '/');
        }

        // 2. Resolve the location resource component (locations/{locationId})
        // Prioritize page_id if it contains a valid Google location identifier; fallback to page_name if valid.
        // A human-readable business name (e.g. 'Downtown Store') is strictly rejected.
        $locationPart = null;
        if ($this->isValidLocationIdentifier($pageId)) {
            $locationPart = $this->normalizeLocationPart($pageId);
        } elseif ($this->isValidLocationIdentifier($pageName)) {
            $locationPart = $this->normalizeLocationPart($pageName);
        }

        // If no valid location resource identifier could be found, fail safely (never generate an invalid account-only path)
        if ($locationPart === null) {
            return null;
        }

        // 3. Resolve and normalize the account component (accounts/{accountId}) if present
        $accountPart = null;
        if ($token !== '') {
            if (str_starts_with($token, 'accounts/')) {
                $accountPart = trim($token, '/');
            } elseif (preg_match('/^[0-9]+$/', $token)) {
                $accountPart = 'accounts/' . $token;
            }
        }

        // If account component is present, construct full path: accounts/{accId}/locations/{locId}
        if ($accountPart !== null) {
            return $accountPart . '/' . $locationPart;
        }

        return null;
    }

    /**
     * Checks if a string is a complete Google parent location path (accounts/{accountId}/locations/{locationId}).
     */
    private function isFullLocationPath(string $value): bool
    {
        return (bool) preg_match('#^accounts/[^/]+/locations/[0-9]+$#i', $value);
    }

    /**
     * Checks if a string is a valid Google location resource identifier (locations/{numericId} or numeric ID).
     * Human-readable business names (e.g. 'Main Street Store') return false.
     */
    private function isValidLocationIdentifier(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        return (bool) (preg_match('#^locations/[0-9]+$#i', $value) || preg_match('/^[0-9]+$/', $value));
    }

    /**
     * Normalizes a valid location identifier to 'locations/{numericId}'.
     */
    private function normalizeLocationPart(string $value): string
    {
        $trimmed = trim($value, '/');
        if (str_starts_with(strtolower($trimmed), 'locations/')) {
            return $trimmed;
        }

        return 'locations/' . $trimmed;
    }

    private function classifyApiException(GoogleApiException $e, int $destId): PlatformDispatchResult
    {
        $statusCode = $e->getHttpStatusCode();
        $errorCode = $e->getErrorCode();
        $statusStr = $e->getErrorStatus() ?? (string) $errorCode;
        $message = $e->getMessage();

        // 1. Authentication failure (invalid_grant, 401, UNAUTHENTICATED)
        if ($e->isAuthError()) {
            $err = sprintf(
                'Google authentication failed (%s): Access token or refresh token is invalid or expired. Please reconnect your Google account in channel settings.',
                $statusStr
            );
            $this->logger?->error(sprintf('[GooglePlatformResolver] Destination #%d: %s', $destId, $err));
            return PlatformDispatchResult::permanentFailure($err, $statusCode, $e->getRawError());
        }

        // 2. Permission denied (403, PERMISSION_DENIED)
        if ($e->isPermissionError()) {
            $err = sprintf(
                'Permission denied by Google (%s): Connected Google account lacks required management permissions for this location.',
                $statusStr
            );
            $this->logger?->error(sprintf('[GooglePlatformResolver] Destination #%d: %s', $destId, $err));
            return PlatformDispatchResult::permanentFailure($err, $statusCode, $e->getRawError());
        }

        // 3. Location not found (404, NOT_FOUND)
        if ($e->isNotFound()) {
            $err = sprintf('Google Business Profile location not found (404): %s', $message);
            $this->logger?->error(sprintf('[GooglePlatformResolver] Destination #%d: %s', $destId, $err));
            return PlatformDispatchResult::permanentFailure($err, $statusCode, $e->getRawError());
        }

        // 4. Precondition failed (FAILED_PRECONDITION, e.g. location unverified or not eligible)
        if ($e->isPreconditionFailed()) {
            $err = sprintf('Google Business Profile precondition failed (%s): %s', $statusStr, $message);
            $this->logger?->error(sprintf('[GooglePlatformResolver] Destination #%d: %s', $destId, $err));
            return PlatformDispatchResult::permanentFailure($err, $statusCode, $e->getRawError());
        }

        // 5. Rate limits / quota (429, RESOURCE_EXHAUSTED)
        if ($e->isRateLimit()) {
            $err = sprintf('Google API quota or rate limit exceeded (%s): %s', $statusStr, $message);
            $this->logger?->warning(sprintf('[GooglePlatformResolver] Destination #%d: %s', $destId, $err));
            return PlatformDispatchResult::temporaryFailure($err, $statusCode, $e->getRetryAfterSeconds(), $e->getRawError());
        }

        // 6. Server crash or transient outage during POST publishing (5xx, INTERNAL, UNAVAILABLE)
        if ($e->isServerCrash()) {
            $err = sprintf(
                'Ambiguous delivery: Google server error (HTTP %d, %s) occurred during publishing: %s',
                $statusCode,
                $statusStr,
                $message
            );
            $this->logger?->warning(sprintf('[GooglePlatformResolver] Destination #%d: %s', $destId, $err));
            return PlatformDispatchResult::ambiguousFailure($err, $statusCode);
        }

        // 7. Generic permanent failure for other HTTP 4xx errors (e.g. 400 INVALID_ARGUMENT)
        $err = sprintf('Google publishing failed (HTTP %d, %s): %s', $statusCode, $statusStr, $message);
        $this->logger?->error(sprintf('[GooglePlatformResolver] Destination #%d: %s', $destId, $err));

        return PlatformDispatchResult::permanentFailure($err, $statusCode, $e->getRawError());
    }
}

