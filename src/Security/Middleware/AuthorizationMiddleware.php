<?php

declare(strict_types=1);

namespace App\Security\Middleware;

use App\Domain\User\User;
use App\Environment;
use App\Security\Authorization\RbacInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * AuthorizationMiddleware.
 * Enforces centralized backend RBAC authorization across GET, POST, and AJAX requests.
 * Blocks unauthorized access with a branded 403 Access Denied page or 403 JSON response.
 */
final class AuthorizationMiddleware implements MiddlewareInterface
{
    /**
     * Universal routes accessible to all authenticated users regardless of feature permissions.
     */
    private const UNIVERSAL_AUTHENTICATED_PATHS = [
        '/',
        '/logout',
        '/profile',
        '/profile/edit',
        '/site/search',
    ];

    /**
     * Universal path prefixes accessible to all authenticated users.
     */
    private const UNIVERSAL_AUTHENTICATED_PREFIXES = [
        '/notification/',
    ];

    /**
     * Public paths that do not require role permissions.
     */
    private const PUBLIC_PATHS = [
        '/login',
        '/forgot-password',
        '/reset-password',
        '/site/forgot-password',
        '/site/request-password-reset',
        '/site/reset-password',
        '/new-client',
        '/qrcode/scan',
        '/hooks/messages',
        '/hooks/instagram',
        '/stripe-webhook',
        '/webhook/stripe',
    ];

    public function __construct(
        private RbacInterface $rbac,
        private ResponseFactoryInterface $responseFactory,
        private WebViewRenderer $viewRenderer,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var User|null $currentUser */
        $currentUser = $request->getAttribute('currentUser');

        // 1. Unauthenticated requests are handled by AuthenticationMiddleware
        if ($currentUser === null) {
            return $handler->handle($request);
        }

        // 2. Platform Administrators (super, admin) retain unrestricted access
        if ($currentUser->isSuperAdmin() || $currentUser->isAdmin()) {
            return $handler->handle($request);
        }

        // 3. Resolve application-relative clean path
        $cleanPath = $this->resolveCleanPath($request->getUri()->getPath());

        // 4. Universal and public paths bypass feature authorization checks
        if ($this->isUniversalOrPublicPath($cleanPath)) {
            return $handler->handle($request);
        }

        // 5. Centralized RBAC check
        if ($this->rbac->can($currentUser, $cleanPath)) {
            return $handler->handle($request);
        }

        // 6. Access Denied: Block request with 403 response
        return $this->denyAccess($request, $cleanPath, $currentUser);
    }

    /**
     * Resolves the application-relative route path, stripping basePath.
     */
    private function resolveCleanPath(string $rawPath): string
    {
        $base = Environment::appBasePath();
        if ($base !== '' && str_starts_with($rawPath, $base)) {
            $rawPath = substr($rawPath, strlen($base));
        }

        $clean = '/' . trim($rawPath, '/');
        if ($clean === '//' || $clean === '') {
            return '/';
        }

        return $clean;
    }

    /**
     * Checks if the path is a universal authenticated route, public path, or static asset.
     */
    private function isUniversalOrPublicPath(string $path): bool
    {
        // Allow static assets
        if (
            str_starts_with($path, '/assets/') ||
            str_starts_with($path, '/themes/') ||
            str_ends_with($path, '.ico') ||
            str_ends_with($path, '.svg') ||
            str_ends_with($path, '.png') ||
            str_ends_with($path, '.css') ||
            str_ends_with($path, '.js')
        ) {
            return true;
        }

        // Universal authenticated paths
        if (in_array($path, self::UNIVERSAL_AUTHENTICATED_PATHS, true)) {
            return true;
        }

        foreach (self::UNIVERSAL_AUTHENTICATED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        // Public paths
        foreach (self::PUBLIC_PATHS as $publicPath) {
            if ($path === $publicPath || str_starts_with($path, $publicPath . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Renders a branded 403 HTML page or 403 JSON response.
     */
    private function denyAccess(ServerRequestInterface $request, string $path, User $currentUser): ResponseInterface
    {
        if ($this->isJsonExpected($request)) {
            $response = $this->responseFactory->createResponse(403)
                ->withHeader('Content-Type', 'application/json; charset=UTF-8');
            $response->getBody()->write((string) json_encode([
                'success' => false,
                'error' => 'Access Denied',
                'message' => 'You don\'t have permission to access this page.',
            ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            return $response;
        }

        return $this->viewRenderer
            ->withViewPath('@src/Web/Error/views')
            ->render('access-denied', [
                'path' => $path,
                'currentUser' => $currentUser,
            ])
            ->withStatus(403);
    }

    /**
     * Determines whether client expects a JSON response.
     */
    private function isJsonExpected(ServerRequestInterface $request): bool
    {
        $headerX = $request->getHeaderLine('X-Requested-With');
        if (strtolower($headerX) === 'xmlhttprequest') {
            return true;
        }

        $accept = $request->getHeaderLine('Accept');
        if (str_contains($accept, 'application/json')) {
            return true;
        }

        $contentType = $request->getHeaderLine('Content-Type');
        if (str_contains($contentType, 'application/json')) {
            return true;
        }

        $path = $request->getUri()->getPath();
        if (str_contains($path, '/accountapi') || str_contains($path, '/ajax')) {
            return true;
        }

        return false;
    }
}

