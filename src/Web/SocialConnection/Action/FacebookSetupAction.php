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

final class FacebookSetupAction
{
    private const FB_SCOPES = 'pages_manage_engagement,pages_read_engagement,pages_manage_posts,pages_read_user_content,pages_manage_metadata,pages_show_list,pages_messaging,instagram_basic,instagram_manage_comments,instagram_content_publish,business_management';

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

        // 1. Resolve Facebook App credentials
        $apis = $this->connectionRepo->findApisByService(\App\Domain\SocialConnection\Api::SERVICE_FACEBOOK);
        $api = null;
        foreach ($apis as $candidate) {
            if (!empty($candidate->getClientId())) {
                $api = $candidate;
                break;
            }
        }

        $appId = trim($api?->getClientId() ?? \App\Environment::facebookAppId());
        if ($appId === '') {
            $flash->set('error', 'Facebook integration is not configured. Please configure your Facebook App ID in API settings or environment configuration.');
            return $this->redirect(\App\Environment::url('/settings/facebook'));
        }

        // 2. Generate cryptographically secure state for CSRF protection
        $state = bin2hex(random_bytes(16));
        $this->session->set('oauth_state_facebook', $state);
        if ($api !== null) {
            $this->session->set('oauth_api_id_facebook', $api->getId());
        } else {
            $this->session->remove('oauth_api_id_facebook');
        }

        // 3. Build Facebook OAuth dialog URL
        $redirectUri = \App\Environment::facebookRedirectUri();
        $authUrl = 'https://www.facebook.com/v25.0/dialog/oauth?' . http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'response_type' => 'code',
            'scope' => self::FB_SCOPES,
        ]);

        return $this->redirect($authUrl);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
