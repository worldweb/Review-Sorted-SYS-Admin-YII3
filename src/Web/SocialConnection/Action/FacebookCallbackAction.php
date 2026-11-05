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

final class FacebookCallbackAction
{
    private const GRAPH_URL = 'https://graph.facebook.com/v25.0/';
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
        if (!empty($queryParams['error']) || !empty($queryParams['error_description'])) {
            $errorDesc = (string) ($queryParams['error_description'] ?? $queryParams['error_message'] ?? $queryParams['error']);
            $this->logger->warning('[FacebookCallback] Authorization denied or cancelled: ' . $errorDesc);
            $flash->set('error', 'Facebook authorization was cancelled or denied: ' . $errorDesc);
            return $this->redirect(\App\Environment::url('/settings/facebook'));
        }

        // 2. Validate CSRF state token
        $state = (string) ($queryParams['state'] ?? '');
        $expectedState = (string) $this->session->get('oauth_state_facebook');
        $this->session->remove('oauth_state_facebook');

        if ($state === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
            $this->logger->error('[FacebookCallback] CSRF state mismatch during Facebook OAuth callback.');
            $flash->set('error', 'Invalid OAuth security state token. Please initiate the connection again.');
            return $this->redirect(\App\Environment::url('/settings/facebook'));
        }

        // 3. Validate authorization code
        $code = trim((string) ($queryParams['code'] ?? ''));
        if ($code === '') {
            $flash->set('error', 'Missing authorization code from Facebook.');
            return $this->redirect(\App\Environment::url('/settings/facebook'));
        }

        // 4. Resolve credentials
        $apiId = (int) ($this->session->get('oauth_api_id_facebook') ?? 0);
        $this->session->remove('oauth_api_id_facebook');

        $api = $apiId > 0 ? $this->connectionRepo->findApiById($apiId) : null;
        if ($api === null) {
            $apis = $this->connectionRepo->findApisByService(Api::SERVICE_FACEBOOK);
            foreach ($apis as $candidate) {
                if (!empty($candidate->getClientId())) {
                    $api = $candidate;
                    break;
                }
            }
        }

        $appId = trim($api?->getClientId() ?? \App\Environment::facebookAppId());
        $appSecret = trim($api?->getApiKey() ?? \App\Environment::facebookAppSecret());

        if ($appId === '' || $appSecret === '') {
            $flash->set('error', 'Facebook App credentials not found in system configuration.');
            return $this->redirect(\App\Environment::url('/settings/facebook'));
        }

        $redirectUri = \App\Environment::facebookRedirectUri();

        try {
            // 5. Exchange code for short-lived user access token
            $tokenResp = $this->httpClient->request('GET', self::GRAPH_URL . 'oauth/access_token', [
                'query' => [
                    'client_id' => $appId,
                    'client_secret' => $appSecret,
                    'redirect_uri' => $redirectUri,
                    'code' => $code,
                ],
            ]);

            $tokenData = json_decode((string) $tokenResp->getBody(), true);
            $shortToken = (string) ($tokenData['access_token'] ?? '');
            if ($shortToken === '') {
                throw new \RuntimeException('Facebook token response did not contain an access_token.');
            }

            // 6. Exchange for long-lived user access token (up to 60 days)
            $userToken = $shortToken;
            try {
                $longResp = $this->httpClient->request('GET', self::GRAPH_URL . 'oauth/access_token', [
                    'query' => [
                        'grant_type' => 'fb_exchange_token',
                        'client_id' => $appId,
                        'client_secret' => $appSecret,
                        'fb_exchange_token' => $shortToken,
                    ],
                ]);
                $longData = json_decode((string) $longResp->getBody(), true);
                if (!empty($longData['access_token'])) {
                    $userToken = (string) $longData['access_token'];
                }
            } catch (Throwable $e) {
                $this->logger->warning('[FacebookCallback] Long-lived token exchange failed, falling back to short token: ' . $e->getMessage());
            }

            // 7. Retrieve managed Facebook Pages
            $pagesResp = $this->httpClient->request('GET', self::GRAPH_URL . 'me/accounts', [
                'query' => [
                    'access_token' => $userToken,
                    'fields' => 'id,name,access_token,location',
                    'limit' => 100,
                ],
            ]);

            $pagesData = json_decode((string) $pagesResp->getBody(), true);
            $pages = (array) ($pagesData['data'] ?? []);

            if (empty($pages)) {
                $flash->set('error', 'No accessible Facebook Pages were found for this account. Please verify that you manage at least one Facebook Page and have granted the required page permissions.');
                return $this->redirect(\App\Environment::url('/settings/facebook'));
            }

            $accountId = (int) $context->getAccountId();
            $savedCount = 0;

            // 8. Upsert each Facebook Page and its linked Instagram account
            foreach ($pages as $page) {
                if (empty($page['id']) || empty($page['access_token'])) {
                    continue;
                }

                $pageAccessToken = (string) $page['access_token'];
                $pageId = (string) $page['id'];

                $serviceConfig = $this->connectionRepo->upsertFacebookPage(
                    $accountId,
                    $api?->getId(),
                    $page,
                    $userToken
                );

                if ($serviceConfig !== null) {
                    $savedCount++;

                    // Query linked Instagram business account
                    $this->discoverAndSaveInstagram(
                        $accountId,
                        $serviceConfig->getId(),
                        $pageId,
                        $pageAccessToken
                    );

                    // Subscribe page to webhooks
                    $this->subscribePageWebhooks($pageId, $pageAccessToken);
                }
            }

            $this->logger->info(sprintf(
                '[FacebookCallback] Successfully authorized and synchronized %d Facebook Page(s) for Account #%d.',
                $savedCount,
                $accountId
            ));

            $flash->set('success', sprintf('Facebook authorization successful. Connected %d Facebook Page(s).', $savedCount));
        } catch (Throwable $e) {
            $this->logger->error('[FacebookCallback] Error during Facebook OAuth exchange: ' . $e->getMessage());
            $flash->set('error', 'Facebook connection failed: ' . $e->getMessage());
        }

        return $this->redirect(\App\Environment::url('/settings/facebook'));
    }

    private function discoverAndSaveInstagram(
        int $accountId,
        int $serviceConfigId,
        string $pageId,
        string $pageAccessToken
    ): void {
        try {
            $resp = $this->httpClient->request('GET', self::GRAPH_URL . $pageId, [
                'query' => [
                    'fields' => 'instagram_business_account',
                    'access_token' => $pageAccessToken,
                ],
            ]);
            $body = json_decode((string) $resp->getBody(), true);
            $igId = (string) ($body['instagram_business_account']['id'] ?? '');

            if ($igId !== '') {
                $userResp = $this->httpClient->request('GET', self::GRAPH_URL . $igId, [
                    'query' => [
                        'fields' => 'username',
                        'access_token' => $pageAccessToken,
                    ],
                ]);
                $userBody = json_decode((string) $userResp->getBody(), true);
                $igUsername = (string) ($userBody['username'] ?? '');

                if ($igUsername !== '') {
                    $this->connectionRepo->upsertInstagramSubService($accountId, $serviceConfigId, [
                        'id' => $igId,
                        'username' => $igUsername,
                    ]);
                }
            }
        } catch (Throwable $e) {
            $this->logger->debug(sprintf(
                '[FacebookCallback] Instagram discovery skipped for Page #%s: %s',
                $pageId,
                $e->getMessage()
            ));
        }
    }

    private function subscribePageWebhooks(string $pageId, string $pageAccessToken): void
    {
        try {
            $this->httpClient->request('POST', self::GRAPH_URL . $pageId . '/subscribed_apps', [
                'form_params' => [
                    'subscribed_fields' => 'messages,ratings',
                    'access_token' => $pageAccessToken,
                ],
            ]);
        } catch (Throwable) {
            // Non-critical: ignore webhook subscription failure during initial OAuth
        }
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}

