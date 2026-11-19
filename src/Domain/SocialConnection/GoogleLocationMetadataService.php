<?php

declare(strict_types=1);

namespace App\Domain\SocialConnection;

use App\Domain\Review\Review;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * Service to retrieve and persist Google Place IDs and Maps URIs
 * for connected Google Business Profile locations without modifying page_id.
 */
final class GoogleLocationMetadataService
{
    private const GBP_LOCATION_BASE = 'https://mybusinessbusinessinformation.googleapis.com/v1/';
    private const GOOGLE_TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';

    private ClientInterface $httpClient;
    private LoggerInterface $logger;

    /**
     * In-memory cache of refreshed access tokens per account_id to avoid redundant OAuth calls.
     * @var array<int, array{access_token: string, expires_at: int}>
     */
    private array $accountTokenCache = [];

    public function __construct(
        private ConnectionInterface $db,
        private SocialConnectionRepositoryInterface $connectionRepo,
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->httpClient = $httpClient ?? new Client([
            'timeout' => 20.0,
            'connect_timeout' => 10.0,
            'verify' => true,
        ]);
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Processes metadata retrieval for eligible Google locations.
     *
     * @param ?callable(array{config_id: int, label: string, status: string, message: string, place_id: ?string, maps_uri: ?string}): void $progressCallback
     * @return array{
     *     total_eligible: int,
     *     processed: int,
     *     updated: int,
     *     skipped: int,
     *     failed: int,
     *     errors: list<string>
     * }
     */
    public function processAllLocations(
        ?int $accountId = null,
        ?int $configId = null,
        int $limit = 0,
        bool $force = false,
        bool $dryRun = false,
        ?callable $progressCallback = null,
    ): array {
        $eligibleRows = $this->findGoogleConfigsToProcess($accountId, $configId, $limit, $force);

        $summary = [
            'total_eligible' => count($eligibleRows),
            'processed' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($eligibleRows as $row) {
            $configId = (int) $row['id'];
            $label = (string) ($row['label'] ?? "Location #{$configId}");

            try {
                $result = $this->processLocationRow($row, $force, $dryRun);
            } catch (Throwable $e) {
                $safeErr = $this->extractSafeErrorMessage($e);
                $this->logger->error("[GoogleLocationMetadata] Location #{$configId} ({$label}): Unhandled error: {$safeErr}");
                $result = [
                    'config_id' => $configId,
                    'status' => 'failed',
                    'message' => "Unhandled error: {$safeErr}",
                    'place_id' => null,
                    'maps_uri' => null,
                ];
            }

            $summary['processed']++;

            if ($result['status'] === 'success') {
                $summary['updated']++;
            } elseif ($result['status'] === 'skipped') {
                $summary['skipped']++;
            } else {
                $summary['failed']++;
                if (!empty($result['message'])) {
                    $summary['errors'][] = "Location #{$configId} ({$label}): {$result['message']}";
                }
            }

            if ($progressCallback !== null) {
                try {
                    $progressCallback([
                        'config_id' => $configId,
                        'label' => $label,
                        'status' => $result['status'],
                        'message' => $result['message'],
                        'place_id' => $result['place_id'] ?? null,
                        'maps_uri' => $result['maps_uri'] ?? null,
                    ]);
                } catch (Throwable) {
                    // Prevent callback display errors from terminating loop
                }
            }
        }

        return $summary;
    }

    /**
     * Fetches and stores metadata for a single location by its service_config ID.
     *
     * @return array{
     *     config_id: int,
     *     status: 'success'|'skipped'|'failed',
     *     message: string,
     *     place_id: ?string,
     *     maps_uri: ?string
     * }
     */
    public function fetchAndStoreMetadataForLocation(int $configId, bool $force = false, bool $dryRun = false): array
    {
        $row = (new Query($this->db))
            ->from('{{%service_config}}')
            ->where(['id' => $configId, 'service_id' => ServiceConfig::SERVICE_GOOGLE])
            ->one();

        if ($row === null || $row === false) {
            return [
                'config_id' => $configId,
                'status' => 'failed',
                'message' => "Google ServiceConfig #{$configId} not found.",
                'place_id' => null,
                'maps_uri' => null,
            ];
        }

        return $this->processLocationRow($row, $force, $dryRun);
    }

    /**
     * Core processing logic for a single service_config database row.
     *
     * @param array<string, mixed> $row
     * @return array{
     *     config_id: int,
     *     status: 'success'|'skipped'|'failed',
     *     message: string,
     *     place_id: ?string,
     *     maps_uri: ?string
     * }
     */
    private function processLocationRow(array $row, bool $force, bool $dryRun): array
    {
        $configId = (int) $row['id'];
        $label = (string) ($row['label'] ?? "Location #{$configId}");
        $accountId = (int) ($row['account_id'] ?? 0);
        $pageId = trim((string) ($row['page_id'] ?? ''));
        $existingPlaceId = trim((string) ($row['google_place_id'] ?? ''));
        $existingMapsUri = trim((string) ($row['google_maps_uri'] ?? ''));

        // Skip if metadata is already populated unless explicitly forced
        if (!$force && $existingPlaceId !== '' && $existingMapsUri !== '') {
            return [
                'config_id' => $configId,
                'status' => 'skipped',
                'message' => 'Location already has both Place ID and Google Maps URI.',
                'place_id' => $existingPlaceId,
                'maps_uri' => $existingMapsUri,
            ];
        }

        if ($pageId === '') {
            return [
                'config_id' => $configId,
                'status' => 'skipped',
                'message' => 'Missing page_id in service_config.',
                'place_id' => null,
                'maps_uri' => null,
            ];
        }

        // Special case: if page_id is already a valid Place ID (starts with ChIJ... and contains no slashes)
        if (Review::isValidGooglePlaceId($pageId) && !str_starts_with($pageId, 'locations/')) {
            if (!$dryRun && $existingPlaceId !== $pageId) {
                $this->db->createCommand()
                    ->update('{{%service_config}}', [
                        'google_place_id' => $pageId,
                        'updated_at' => time(),
                    ], ['id' => $configId])
                    ->execute();
            }

            return [
                'config_id' => $configId,
                'status' => 'success',
                'message' => "Extracted Place ID directly from stored page_id: [{$pageId}].",
                'place_id' => $pageId,
                'maps_uri' => $existingMapsUri !== '' ? $existingMapsUri : null,
            ];
        }

        // Normalize resource name: must be 'locations/{id}'
        $resourceName = $pageId;
        if (!str_starts_with($resourceName, 'locations/')) {
            if (is_numeric($resourceName)) {
                $resourceName = 'locations/' . $resourceName;
            } else {
                return [
                    'config_id' => $configId,
                    'status' => 'skipped',
                    'message' => "Unrecognized page_id format: '{$pageId}' is not a Google locations resource.",
                    'place_id' => null,
                    'maps_uri' => null,
                ];
            }
        }

        // Resolve access token
        $tokenRes = $this->resolveAccessToken($row);
        if (!$tokenRes['success']) {
            $msg = $tokenRes['error'] ?? 'OAuth token resolution failed.';
            $this->logger->warning("[GoogleLocationMetadata] Location #{$configId} ({$label}): {$msg}");
            return [
                'config_id' => $configId,
                'status' => 'failed',
                'message' => $msg,
                'place_id' => null,
                'maps_uri' => null,
            ];
        }

        $accessToken = (string) $tokenRes['access_token'];

        // Request Google Business Information API
        $url = self::GBP_LOCATION_BASE . $resourceName;

        try {
            $resp = $this->requestLocationWithRetry($url, $accessToken, $row);
            $body = json_decode((string) $resp->getBody(), true);

            if (!is_array($body)) {
                return [
                    'config_id' => $configId,
                    'status' => 'failed',
                    'message' => 'Invalid JSON response from Google Business Information API.',
                    'place_id' => null,
                    'maps_uri' => null,
                ];
            }

            $metadata = (array) ($body['metadata'] ?? []);
            $rawPlaceId = isset($metadata['placeId']) ? (string) $metadata['placeId'] : null;
            $rawMapsUri = isset($metadata['mapsUri']) ? (string) $metadata['mapsUri'] : null;

            $validPlaceId = Review::isValidGooglePlaceId($rawPlaceId) ? $rawPlaceId : null;
            $validMapsUri = Review::isValidGoogleMapsUrl($rawMapsUri) ? $rawMapsUri : null;

            if ($validPlaceId === null && $validMapsUri === null) {
                return [
                    'config_id' => $configId,
                    'status' => 'skipped',
                    'message' => 'Google API returned location but metadata.placeId and metadata.mapsUri were empty or invalid.',
                    'place_id' => null,
                    'maps_uri' => null,
                ];
            }

            if (!$dryRun) {
                $updates = ['updated_at' => time()];
                if ($validPlaceId !== null) {
                    $updates['google_place_id'] = $validPlaceId;
                }
                if ($validMapsUri !== null) {
                    $updates['google_maps_uri'] = $validMapsUri;
                }

                $this->db->createCommand()
                    ->update('{{%service_config}}', $updates, ['id' => $configId])
                    ->execute();
            }

            $details = [];
            if ($validPlaceId !== null) {
                $details[] = "Place ID: {$validPlaceId}";
            }
            if ($validMapsUri !== null) {
                $details[] = "Maps URI: {$validMapsUri}";
            }

            $this->logger->info(sprintf(
                '[GoogleLocationMetadata] Location #%d (%s): Successfully retrieved %s.',
                $configId,
                $label,
                implode(', ', $details)
            ));

            return [
                'config_id' => $configId,
                'status' => 'success',
                'message' => ($dryRun ? '[DRY-RUN] ' : '') . 'Retrieved ' . implode(', ', $details),
                'place_id' => $validPlaceId,
                'maps_uri' => $validMapsUri,
            ];
        } catch (Throwable $e) {
            $response = method_exists($e, 'getResponse') ? $e->getResponse() : null;
            $status = $response !== null ? $response->getStatusCode() : 0;
            $errorDesc = $this->extractSafeErrorMessage($e);

            if ($status === 404) {
                $msg = 'Location not found on Google (404). It may have been deleted, unverified, or transferred.';
            } elseif ($status === 403) {
                $msg = 'Permission denied (403). The connected Google account may lack permission for this location or the Business Information API is disabled.';
            } elseif ($status === 401) {
                $msg = 'Authorization failed (401). Access token rejected and could not be refreshed.';
            } elseif ($status === 429) {
                $msg = 'Google API rate limit exceeded (429).';
            } elseif ($status > 0) {
                $msg = "Google API HTTP error ({$status}): {$errorDesc}";
            } else {
                $msg = "Google API request failed: {$errorDesc}";
            }

            $this->logger->warning("[GoogleLocationMetadata] Location #{$configId} ({$label}): {$msg}");
            return [
                'config_id' => $configId,
                'status' => 'failed',
                'message' => $msg,
                'place_id' => null,
                'maps_uri' => null,
            ];
        }
    }

    /**
     * Performs GET location request with automatic 401 token refresh and single retry.
     */
    private function requestLocationWithRetry(string $url, string $accessToken, array $row): \Psr\Http\Message\ResponseInterface
    {
        try {
            return $this->httpClient->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ],
                'query' => [
                    'readMask' => 'name,title,metadata',
                ],
            ]);
        } catch (Throwable $e) {
            $response = method_exists($e, 'getResponse') ? $e->getResponse() : null;
            $statusCode = $response !== null ? $response->getStatusCode() : 0;

            if ($statusCode === 401) {
                // Invalidate cache and attempt token refresh
                $accountId = (int) ($row['account_id'] ?? 0);
                unset($this->accountTokenCache[$accountId]);

                $tokenRes = $this->resolveAccessToken($row, forceRefresh: true);
                if ($tokenRes['success']) {
                    $newAccessToken = (string) $tokenRes['access_token'];
                    return $this->httpClient->request('GET', $url, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $newAccessToken,
                            'Accept' => 'application/json',
                        ],
                        'query' => [
                            'readMask' => 'name,title,metadata',
                        ],
                    ]);
                }
            }

            if ($statusCode === 429) {
                sleep(2);
                return $this->httpClient->request('GET', $url, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Accept' => 'application/json',
                    ],
                    'query' => [
                        'readMask' => 'name,title,metadata',
                    ],
                ]);
            }

            throw $e;
        }
    }

    /**
     * Resolves a valid access token for the service configuration row,
     * using memory caching, expiration verification, and refresh-token fallback.
     *
     * @return array{success: bool, access_token?: string, error?: string}
     */
    private function resolveAccessToken(array $configRow, bool $forceRefresh = false): array
    {
        $accountId = (int) ($configRow['account_id'] ?? 0);
        $now = time();

        // 1. Check in-memory cache for this account
        if (!$forceRefresh && isset($this->accountTokenCache[$accountId])) {
            $cached = $this->accountTokenCache[$accountId];
            if ($cached['expires_at'] > ($now + 60)) {
                return ['success' => true, 'access_token' => $cached['access_token']];
            }
        }

        // 2. Inspect page_token on the current row
        $tokenData = [];
        if (!empty($configRow['page_token'])) {
            $decoded = json_decode((string) $configRow['page_token'], true);
            if (is_array($decoded)) {
                $tokenData = $decoded;
            }
        }

        // 3. If access_token in page_token is still valid and not forced, use it
        if (!$forceRefresh && !empty($tokenData['access_token'])) {
            $created = (int) ($tokenData['created'] ?? 0);
            $expiresIn = (int) ($tokenData['expires_in'] ?? 3600);
            if ($created > 0 && ($created + $expiresIn - 60) > $now) {
                $this->accountTokenCache[$accountId] = [
                    'access_token' => (string) $tokenData['access_token'],
                    'expires_at' => $created + $expiresIn,
                ];
                return ['success' => true, 'access_token' => (string) $tokenData['access_token']];
            }
        }

        // 4. Token expired, missing, or forced refresh: locate refresh token
        $refreshToken = (string) ($tokenData['refresh_token'] ?? '');

        // If no refresh token on this row, find one from another Google location in the same account
        if ($refreshToken === '' && $accountId > 0) {
            $otherRow = (new Query($this->db))
                ->select(['page_token'])
                ->from('{{%service_config}}')
                ->where(['account_id' => $accountId, 'service_id' => ServiceConfig::SERVICE_GOOGLE])
                ->andWhere(['like', 'page_token', 'refresh_token'])
                ->orderBy(['id' => SORT_DESC])
                ->one();

            if ($otherRow !== false && !empty($otherRow['page_token'])) {
                $otherDecoded = json_decode((string) $otherRow['page_token'], true);
                if (is_array($otherDecoded) && !empty($otherDecoded['refresh_token'])) {
                    $refreshToken = (string) $otherDecoded['refresh_token'];
                }
            }
        }

        // Also check account_api table if still not found
        if ($refreshToken === '' && $accountId > 0) {
            $apiRow = (new Query($this->db))
                ->select(['token'])
                ->from('{{%account_api}}')
                ->where(['account_id' => $accountId])
                ->andWhere(['like', 'token', 'refresh_token'])
                ->one();

            if ($apiRow !== false && !empty($apiRow['token'])) {
                $apiDecoded = json_decode((string) $apiRow['token'], true);
                if (is_array($apiDecoded) && !empty($apiDecoded['refresh_token'])) {
                    $refreshToken = (string) $apiDecoded['refresh_token'];
                }
            }
        }

        if ($refreshToken === '') {
            return [
                'success' => false,
                'error' => "Google access token expired and no OAuth refresh token is available for Account #{$accountId}.",
            ];
        }

        // 5. Perform token refresh
        $refreshed = $this->refreshOAuthToken($refreshToken, (int) ($configRow['api_id'] ?? 0));
        if (!$refreshed['success']) {
            return [
                'success' => false,
                'error' => $refreshed['error'] ?? 'Token refresh failed.',
            ];
        }

        $newAccessToken = (string) $refreshed['access_token'];
        $expiresIn = (int) ($refreshed['expires_in'] ?? 3600);

        // Store in memory cache
        $this->accountTokenCache[$accountId] = [
            'access_token' => $newAccessToken,
            'expires_at' => $now + $expiresIn,
        ];

        // Persist refreshed token back to service_config
        $tokenData['access_token'] = $newAccessToken;
        $tokenData['refresh_token'] = $refreshToken;
        $tokenData['expires_in'] = $expiresIn;
        $tokenData['created'] = $now;
        $tokenJson = (string) json_encode($tokenData, JSON_UNESCAPED_SLASHES);

        $this->db->createCommand()
            ->update('{{%service_config}}', ['page_token' => $tokenJson], ['id' => (int) $configRow['id']])
            ->execute();

        return ['success' => true, 'access_token' => $newAccessToken];
    }

    /**
     * Refreshes an OAuth access token using client credentials and a refresh token.
     *
     * @return array{success: bool, access_token?: string, expires_in?: int, error?: string}
     */
    private function refreshOAuthToken(string $refreshToken, int $apiId): array
    {
        $clientId = '';
        $clientSecret = '';

        if ($apiId > 0) {
            $api = $this->connectionRepo->findApiById($apiId);
            if ($api !== null && !empty($api->getClientJson())) {
                $json = json_decode($api->getClientJson(), true);
                if (is_array($json)) {
                    $info = $json['web'] ?? $json['installed'] ?? $json;
                    $clientId = (string) ($info['client_id'] ?? '');
                    $clientSecret = (string) ($info['client_secret'] ?? '');
                }
            }
        }

        if ($clientId === '' || $clientSecret === '') {
            $apis = $this->connectionRepo->findApisByService(\App\Domain\SocialConnection\Api::SERVICE_GOOGLE);
            foreach ($apis as $api) {
                if (!empty($api->getClientJson())) {
                    $json = json_decode($api->getClientJson(), true);
                    if (is_array($json)) {
                        $info = $json['web'] ?? $json['installed'] ?? $json;
                        if (!empty($info['client_id']) && !empty($info['client_secret'])) {
                            $clientId = (string) $info['client_id'];
                            $clientSecret = (string) $info['client_secret'];
                            break;
                        }
                    }
                }
            }
        }

        if ($clientId === '' || $clientSecret === '') {
            $clientId = \App\Environment::googleClientId();
            $clientSecret = \App\Environment::googleClientSecret();
        }

        if ($clientId === '' || $clientSecret === '') {
            return [
                'success' => false,
                'error' => 'Missing Google client_id / client_secret in API settings and Environment.',
            ];
        }

        try {
            $response = $this->httpClient->request('POST', self::GOOGLE_TOKEN_ENDPOINT, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ],
                'form_params' => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'refresh_token' => $refreshToken,
                    'grant_type' => 'refresh_token',
                ],
                'timeout' => 15.0,
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode((string) $response->getBody(), true);
                if (is_array($data) && !empty($data['access_token'])) {
                    return [
                        'success' => true,
                        'access_token' => (string) $data['access_token'],
                        'expires_in' => (int) ($data['expires_in'] ?? 3600),
                    ];
                }
            }
        } catch (Throwable $e) {
            $response = method_exists($e, 'getResponse') ? $e->getResponse() : null;
            $body = $response !== null ? (string) $response->getBody() : '';
            $errJson = json_decode($body, true);
            $errDesc = is_array($errJson) ? ($errJson['error_description'] ?? $errJson['error'] ?? 'Unknown error') : 'OAuth client error';
            if ($errDesc === 'OAuth client error' && $e->getMessage() !== '') {
                $errDesc = (string) preg_replace('/(client_secret=|refresh_token=)[^\s&]+/i', '$1[REDACTED]', $e->getMessage());
            }
            return [
                'success' => false,
                'error' => "Google OAuth refresh rejected: {$errDesc}",
            ];
        }

        return [
            'success' => false,
            'error' => 'Google OAuth token refresh returned an invalid response.',
        ];
    }

    /**
     * Finds eligible Google service_config records to process.
     *
     * @return list<array<string, mixed>>
     */
    private function findGoogleConfigsToProcess(?int $accountId, ?int $configId, int $limit, bool $force): array
    {
        $query = (new Query($this->db))
            ->from('{{%service_config}}')
            ->where([
                'service_id' => ServiceConfig::SERVICE_GOOGLE,
                'linked' => ServiceConfig::LINKED_YES,
            ]);

        if ($accountId !== null && $accountId > 0) {
            $query->andWhere(['account_id' => $accountId]);
        }

        if ($configId !== null && $configId > 0) {
            $query->andWhere(['id' => $configId]);
        }

        // Resumability: Skip records that already have metadata unless force is set
        if (!$force) {
            $query->andWhere([
                'or',
                ['google_place_id' => null],
                ['google_place_id' => ''],
                ['google_maps_uri' => null],
                ['google_maps_uri' => ''],
            ]);
        }

        $query->orderBy(['id' => SORT_ASC]);

        if ($limit > 0) {
            $query->limit($limit);
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $query->all();
        return $rows;
    }

    /**
     * Extracts safe error message without exposing credentials or internal tokens.
     */
    private function extractSafeErrorMessage(Throwable $e): string
    {
        $response = method_exists($e, 'getResponse') ? $e->getResponse() : null;
        if ($response !== null) {
            try {
                $body = (string) $response->getBody();
                $data = json_decode($body, true);
                if (is_array($data) && isset($data['error'])) {
                    $err = $data['error'];
                    if (is_array($err)) {
                        return (string) ($err['message'] ?? $err['status'] ?? 'Google API error');
                    }
                    return (string) $err;
                }
            } catch (Throwable) {
                // If body cannot be parsed, fall back to sanitized message below
            }
        }

        $rawMsg = $e->getMessage();
        return (string) preg_replace('/(bearer\s+|access_token=|refresh_token=|client_secret=)[^\s&]+/i', '$1[REDACTED]', $rawMsg);
    }
}
