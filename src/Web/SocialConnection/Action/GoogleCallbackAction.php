<?php

declare(strict_types=1);

namespace App\Web\SocialConnection\Action;

use App\Domain\SocialConnection\Api;
use App\Domain\SocialConnection\SocialConnectionRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;

final class GoogleCallbackAction
{
    private const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
    private const GBP_ACCOUNTS_V1 = 'https://mybusinessaccountmanagement.googleapis.com/v1/accounts';
    private const GBP_ACCOUNTS_V4 = 'https://mybusiness.googleapis.com/v4/accounts';
    private const GBP_INFO_V1 = 'https://mybusinessbusinessinformation.googleapis.com/v1/';

    private ClientInterface $httpClient;
    private LoggerInterface $logger;

    public function __construct(
        private SocialConnectionRepositoryInterface $connectionRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private SessionInterface $session,
        private ResponseFactoryInterface $responseFactory,
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->httpClient = $httpClient ?? new Client([
            'timeout' => 30.0,
            'connect_timeout' => 10.0,
            'verify' => true,
        ]);
        $this->logger = $logger ?? new NullLogger();
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $this->tenantContextProvider->get();
        if ($context->isGuest()) {
            return $this->redirect(\App\Environment::url('/login'));
        }

        $flash = new Flash($this->session);
        $queryParams = $request->getQueryParams();

        // 1. Check for authorization errors or cancellation
        if (!empty($queryParams['error'])) {
            $errorDesc = (string) ($queryParams['error_description'] ?? $queryParams['error']);
            $this->logger->warning('[GoogleCallback] Authorization denied or cancelled: ' . $errorDesc);
            $flash->set('error', 'Google authorization was cancelled or denied: ' . $errorDesc);
            return $this->redirect(\App\Environment::url('/settings/google'));
        }

        // 2. Validate CSRF state token
        $state = (string) ($queryParams['state'] ?? '');
        $expectedState = (string) $this->session->get('oauth_state_google');
        $this->session->remove('oauth_state_google');

        if ($state === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
            $this->logger->error('[GoogleCallback] CSRF state mismatch during Google OAuth callback.');
            $flash->set('error', 'Invalid OAuth security state token. Please initiate the connection again.');
            return $this->redirect(\App\Environment::url('/settings/google'));
        }

        // 3. Validate authorization code
        $code = trim((string) ($queryParams['code'] ?? ''));
        if ($code === '') {
            $flash->set('error', 'Missing authorization code from Google.');
            return $this->redirect(\App\Environment::url('/settings/google'));
        }

        // 4. Resolve Google OAuth client credentials
        $apiId = (int) ($this->session->get('oauth_api_id_google') ?? 0);
        $this->session->remove('oauth_api_id_google');

        $api = $apiId > 0 ? $this->connectionRepo->findApiById($apiId) : null;
        $credentials = $this->resolveCredentials($api);

        if ($credentials === null) {
            $flash->set('error', 'Google OAuth client credentials not found in system configuration.');
            return $this->redirect(\App\Environment::url('/settings/google'));
        }

        $redirectUri = \App\Environment::googleRedirectUri();

        try {
            // 5. Exchange code for access & refresh tokens
            $tokenResp = $this->httpClient->request('POST', self::TOKEN_ENDPOINT, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ],
                'form_params' => [
                    'code' => $code,
                    'client_id' => $credentials['client_id'],
                    'client_secret' => $credentials['client_secret'],
                    'redirect_uri' => $redirectUri,
                    'grant_type' => 'authorization_code',
                ],
            ]);

            $tokenData = json_decode((string) $tokenResp->getBody(), true);
            $accessToken = (string) ($tokenData['access_token'] ?? '');
            if ($accessToken === '') {
                throw new \RuntimeException('Google token endpoint omitted access_token in response.');
            }

            // Construct standard token payload
            $tokenPayload = [
                'access_token' => $accessToken,
                'token_type' => $tokenData['token_type'] ?? 'Bearer',
                'expires_in' => (int) ($tokenData['expires_in'] ?? 3600),
                'created' => time(),
            ];
            if (!empty($tokenData['refresh_token'])) {
                $tokenPayload['refresh_token'] = (string) $tokenData['refresh_token'];
            }

            // 6. Discover Google Business Profile accounts and locations
            $locations = $this->discoverLocations($accessToken);

            if (empty($locations)) {
                $flash->set('error', 'Google account authorized successfully, but no Google Business Profile locations were found. Please verify that your Google account manages at least one verified Business Profile location.');
                return $this->redirect(\App\Environment::url('/settings/google'));
            }

            $accountId = (int) $context->getAccountId();
            $savedCount = 0;

            // 7. Upsert discovered locations into service_config
            foreach ($locations as $loc) {
                if (empty($loc['name'])) {
                    continue;
                }

                $saved = $this->connectionRepo->upsertGoogleLocation(
                    $accountId,
                    $credentials['api_id'],
                    $loc,
                    $tokenPayload,
                    linked: true
                );

                if ($saved !== null) {
                    $savedCount++;
                }
            }

            $this->logger->info(sprintf(
                '[GoogleCallback] Successfully authorized and synchronized %d Google location(s) for Account #%d.',
                $savedCount,
                $accountId
            ));

            $flash->set('success', sprintf('Google Business Profile connected successfully. Discovered and linked %d location(s).', $savedCount));
        } catch (Throwable $e) {
            $this->logger->error('[GoogleCallback] Error during Google OAuth exchange: ' . $e->getMessage());
            $flash->set('error', 'Google connection failed: ' . $e->getMessage());
        }

        return $this->redirect(\App\Environment::url('/settings/google'));
    }

    /**
     * @return array<int, array{name: string, title?: string, storeCode?: string, account_name?: string}>
     */
    private function discoverLocations(string $accessToken): array
    {
        $accounts = [];

        // Try Google My Business Account Management v1
        try {
            $resp = $this->httpClient->request('GET', self::GBP_ACCOUNTS_V1, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ],
            ]);
            $body = json_decode((string) $resp->getBody(), true);
            $accounts = (array) ($body['accounts'] ?? []);
        } catch (Throwable $e) {
            $this->logger->debug('[GoogleCallback] GBP v1 accounts endpoint failed, attempting v4 fallback: ' . $e->getMessage());
            try {
                $resp = $this->httpClient->request('GET', self::GBP_ACCOUNTS_V4, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Accept' => 'application/json',
                    ],
                ]);
                $body = json_decode((string) $resp->getBody(), true);
                $accounts = (array) ($body['accounts'] ?? []);
            } catch (Throwable $e4) {
                $this->logger->warning('[GoogleCallback] Both GBP v1 and v4 accounts listing failed: ' . $e4->getMessage());
            }
        }

        $allLocations = [];

        foreach ($accounts as $acc) {
            $accName = (string) ($acc['name'] ?? '');
            if ($accName === '') {
                continue;
            }

            $locs = $this->fetchLocationsForAccount($accName, $accessToken);
            foreach ($locs as $loc) {
                $loc['account_name'] = $accName;
                $allLocations[] = $loc;
            }
        }

        return $allLocations;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchLocationsForAccount(string $accountName, string $accessToken): array
    {
        $locations = [];
        $trimmedAcc = trim($accountName, '/');

        // 1. Try Business Information v1: accounts/{accountId}/locations
        try {
            $url = self::GBP_INFO_V1 . $trimmedAcc . '/locations';
            $resp = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ],
                'query' => [
                    'pageSize' => 100,
                    'readMask' => 'name,title,storeCode,labels',
                ],
            ]);

            $body = json_decode((string) $resp->getBody(), true);
            $locations = (array) ($body['locations'] ?? []);
            if (!empty($locations)) {
                return $locations;
            }
        } catch (Throwable $e) {
            $this->logger->debug(sprintf(
                '[GoogleCallback] GBP v1 locations endpoint failed for %s, trying v4: %s',
                $trimmedAcc,
                $e->getMessage()
            ));
        }

        // 2. Fallback to v4: https://mybusiness.googleapis.com/v4/{accountName}/locations
        try {
            $url = 'https://mybusiness.googleapis.com/v4/' . $trimmedAcc . '/locations';
            $resp = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ],
                'query' => [
                    'pageSize' => 100,
                ],
            ]);

            $body = json_decode((string) $resp->getBody(), true);
            $locations = (array) ($body['locations'] ?? []);
        } catch (Throwable $e) {
            $this->logger->warning(sprintf(
                '[GoogleCallback] GBP v4 locations endpoint failed for %s: %s',
                $trimmedAcc,
                $e->getMessage()
            ));
        }

        return $locations;
    }

    /**
     * @return array{client_id: string, client_secret: string, api_id: ?int}|null
     */
    private function resolveCredentials(?Api $api): ?array
    {
        // 1. If API provided or in database, check client_json
        if ($api === null) {
            $apis = $this->connectionRepo->findApisByService(Api::SERVICE_GOOGLE);
            foreach ($apis as $candidate) {
                if (!empty($candidate->getClientJson()) || !empty($candidate->getClientId())) {
                    $api = $candidate;
                    break;
                }
            }
        }

        if ($api !== null && !empty($api->getClientJson())) {
            $clientConfig = json_decode($api->getClientJson(), true);
            if (is_array($clientConfig)) {
                $clientInfo = $clientConfig['web'] ?? $clientConfig['installed'] ?? $clientConfig;
                $cid = trim((string) ($clientInfo['client_id'] ?? ''));
                $secret = trim((string) ($clientInfo['client_secret'] ?? ''));
                if ($cid !== '' && $secret !== '') {
                    return [
                        'client_id' => $cid,
                        'client_secret' => $secret,
                        'api_id' => $api->getId(),
                    ];
                }
            }
        }

        // 2. Direct clientId and token fields
        if ($api !== null && !empty($api->getClientId()) && !empty($api->getToken())) {
            return [
                'client_id' => trim((string) $api->getClientId()),
                'client_secret' => trim((string) $api->getToken()),
                'api_id' => $api->getId(),
            ];
        }

        // 3. Fallback to Environment variables
        $envCid = trim(\App\Environment::googleClientId());
        $envSecret = trim(\App\Environment::googleClientSecret());
        if ($envCid !== '' && $envSecret !== '') {
            return [
                'client_id' => $envCid,
                'client_secret' => $envSecret,
                'api_id' => $api?->getId(),
            ];
        }

        return null;
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}

