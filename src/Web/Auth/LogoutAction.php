<?php

declare(strict_types=1);

namespace App\Web\Auth;

use App\Security\Middleware\AuthenticationMiddleware;
use App\Security\RememberMeService;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Session\SessionInterface;

/**
 * LogoutAction.
 * Safely terminates authenticated sessions, removes cookies, and clears request tenant context.
 */
final class LogoutAction
{
    public function __construct(
        private SessionInterface $session,
        private TenantContextProviderInterface $tenantContextProvider,
        private RememberMeService $rememberMeService,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function __invoke(): ResponseInterface
    {
        // 1. Clear authentication state from session
        if ($this->session->has(AuthenticationMiddleware::SESSION_AUTH_KEY)) {
            $this->session->remove(AuthenticationMiddleware::SESSION_AUTH_KEY);
        }

        // 2. Regenerate session ID to prevent fixation
        $this->session->regenerateId();

        // 3. Reset tenant context back to guest
        $this->tenantContextProvider->reset();

        // 4. Create redirect response to login page
        $response = $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', '/sys-yii3/login');

        // 5. Invalidate remember-me cookie
        return $this->rememberMeService->clearRememberCookie($response);
    }
}
