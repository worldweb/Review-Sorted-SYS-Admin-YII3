<?php

declare(strict_types=1);

namespace App\Security\Middleware;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\RememberMeService;
use App\Security\TenantContext;
use App\Security\TenantContextProviderInterface;
use App\Security\UserAuthenticationRepositoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Session\SessionInterface;

/**
 * AuthenticationMiddleware.
 * Handles user session restoration, remember-me auto-login, request-level tenant context initialization,
 * and route authorization boundaries.
 */
final class AuthenticationMiddleware implements MiddlewareInterface
{
    public const SESSION_AUTH_KEY = '_auth_user_id';

    /**
     * Public paths that do not require an authenticated session.
     */
    private const PUBLIC_PATHS = [
        '/login',
        '/forgot-password',
        '/reset-password',
        '/new-client',
        '/qrcode/scan',
    ];

    public function __construct(
        private SessionInterface $session,
        private UserAuthenticationRepositoryInterface $authRepo,
        private UserRepositoryInterface $userRepo,
        private AccountRepositoryInterface $accountRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private RememberMeService $rememberMeService,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 0. Ensure request-level isolation: reset context to guest before processing
        $this->tenantContextProvider->reset();

        $path = $request->getUri()->getPath();

        // 1. Resolve Identity: Check active session
        $userId = $this->session->get(self::SESSION_AUTH_KEY);
        $identity = null;

        if (is_int($userId) || (is_string($userId) && is_numeric($userId))) {
            $identity = $this->authRepo->findAuthIdentityById((int) $userId);
        }

        // 2. If not in session, attempt remember-me auto-login
        if ($identity === null) {
            $identity = $this->rememberMeService->extractIdentityFromCookie($request);
            if ($identity !== null) {
                // Restore session from valid remember-me token
                $this->session->set(self::SESSION_AUTH_KEY, $identity->getId());
            }
        }

        // 3. Verify user status & tenant account status
        $authenticatedUser = null;
        if ($identity !== null && $identity->isActive()) {
            $role = $identity->getRole();
            $accountActive = true;

            // Verify tenant account is active for non-admin roles
            if ($role === 'account_admin' || $role === 'worker') {
                $accountId = $identity->getAccountId();
                if (($accountId === null || $accountId <= 0) && $identity->getOwnerId() !== null && $identity->getOwnerId() > 0) {
                    $owner = $this->userRepo->findById($identity->getOwnerId(), TenantContext::unrestricted());
                    $accountId = $owner?->getAccountId();
                }

                if ($accountId !== null && $accountId > 0) {
                    $account = $this->accountRepo->findById($accountId, TenantContext::unrestricted());
                    if ($account === null || !$account->isActive()) {
                        $accountActive = false;
                    }
                }
            }

            if ($accountActive) {
                // Resolve full domain User DTO
                $authenticatedUser = $this->userRepo->findById($identity->getId(), TenantContext::unrestricted());
            }
        }

        // 4. Establish Request-Level Tenant Context
        if ($authenticatedUser !== null) {
            $tenantContext = TenantContext::fromUser($authenticatedUser);
            $this->tenantContextProvider->set($tenantContext);

            // Attach user and tenant context to PSR-7 request attributes
            $request = $request
                ->withAttribute('currentUser', $authenticatedUser)
                ->withAttribute('tenantContext', $tenantContext);
        } else {
            // Clean up any stale/invalid session
            if ($this->session->has(self::SESSION_AUTH_KEY)) {
                $this->session->remove(self::SESSION_AUTH_KEY);
            }
            $this->tenantContextProvider->reset();
        }

        $base = \App\Environment::appBasePath();
        $loginUrl = \App\Environment::url('/login');
        $homeUrl = \App\Environment::url('/');

        $isPublic = $this->isPublicPath($path, $base);
        $isLoginPath = $path === $loginUrl || $path === '/login' || ($base !== '' && $path === $base . '/login');

        // 5. Route Protection Rules
        // Authenticated users trying to access login page are redirected to dashboard (matches Yii 2 behavior)
        if ($authenticatedUser !== null && $isLoginPath) {
            return $this->createRedirectResponse($homeUrl);
        }

        // Unauthenticated guests accessing protected routes are redirected to login
        if ($authenticatedUser === null && !$isPublic) {
            return $this->createRedirectResponse($loginUrl);
        }

        return $handler->handle($request);
    }

    /**
     * Determines whether a path is public or a static asset.
     */
    private function isPublicPath(string $path, string $base = ''): bool
    {
        // Allow static assets
        if (
            str_starts_with($path, ($base !== '' ? $base : '') . '/assets/') ||
            str_starts_with($path, '/assets/') ||
            str_starts_with($path, ($base !== '' ? $base : '') . '/themes/') ||
            str_starts_with($path, '/themes/') ||
            str_ends_with($path, '.ico') ||
            str_ends_with($path, '.svg') ||
            str_ends_with($path, '.png') ||
            str_ends_with($path, '.css') ||
            str_ends_with($path, '.js')
        ) {
            return true;
        }

        foreach (self::PUBLIC_PATHS as $publicPath) {
            if ($path === $publicPath || rtrim($path, '/') === $publicPath) {
                return true;
            }
            if ($base !== '') {
                $basePublic = $base . '/' . ltrim($publicPath, '/');
                if ($path === $basePublic || rtrim($path, '/') === $basePublic) {
                    return true;
                }
            }
        }

        return false;
    }

    private function createRedirectResponse(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
