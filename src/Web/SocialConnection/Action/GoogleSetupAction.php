<?php

declare(strict_types=1);

namespace App\Web\SocialConnection\Action;

use App\Domain\SocialConnection\SocialConnectionRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;

final class GoogleSetupAction
{
    private const GOOGLE_SCOPE = 'https://www.googleapis.com/auth/business.manage';

    public function __construct(
        private SocialConnectionRepositoryInterface $connectionRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private SessionInterface $session,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $this->tenantContextProvider->get();
        if ($context->isGuest()) {
            return $this->redirect(\App\Environment::url('/login'));
        }

        $flash = new Flash($this->session);

        // 1. Resolve Google Client ID
        $apis = $this->connectionRepo->findApisByService(\App\Domain\SocialConnection\Api::SERVICE_GOOGLE);
        $api = null;
        $clientId = '';

        foreach ($apis as $candidate) {
            if (!empty($candidate->getClientJson())) {
                $clientConfig = json_decode($candidate->getClientJson(), true);
                if (is_array($clientConfig)) {
                    $clientInfo = $clientConfig['web'] ?? $clientConfig['installed'] ?? $clientConfig;
                    if (!empty($clientInfo['client_id'])) {
                        $api = $candidate;
                        $clientId = (string) $clientInfo['client_id'];
                        break;
                    }
                }
            }
            if (!empty($candidate->getClientId())) {
                $api = $candidate;
                $clientId = (string) $candidate->getClientId();
                break;
            }
        }

        if ($clientId === '') {
            $clientId = trim(\App\Environment::googleClientId());
        }

        if ($clientId === '') {
            $flash->set('error', 'Google integration is not configured. Please configure Google OAuth credentials in API settings or environment configuration.');
            return $this->redirect(\App\Environment::url('/settings/google'));
        }

        // 2. Generate cryptographically secure state for CSRF protection
        $state = bin2hex(random_bytes(16));
        $this->session->set('oauth_state_google', $state);
        if ($api !== null) {
            $this->session->set('oauth_api_id_google', $api->getId());
        } else {
            $this->session->remove('oauth_api_id_google');
        }

        // 3. Build Google OAuth 2.0 authorization URL
        $redirectUri = \App\Environment::googleRedirectUri();
        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => self::GOOGLE_SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);

        return $this->redirect($authUrl);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
