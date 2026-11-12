<?php

declare(strict_types=1);

namespace App\Security\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Csrf\CsrfTokenMiddleware;

/**
 * AppCsrfMiddleware.
 * Decorates Yiisoft CsrfTokenMiddleware, exempting external webhook endpoints (e.g. Facebook, Stripe)
 * while maintaining strict CSRF token validation for all standard application requests.
 */
final class AppCsrfMiddleware implements MiddlewareInterface
{
    /**
     * Webhook paths exempt from CSRF token validation.
     */
    private const EXEMPT_PATHS = [
        '/hooks/messages',
        '/hooks/instagram',
        '/stripe-webhook',
        '/webhook/stripe',
    ];

    public function __construct(
        private CsrfTokenMiddleware $csrfTokenMiddleware,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $base = \App\Environment::appBasePath();

        foreach (self::EXEMPT_PATHS as $exempt) {
            if ($path === $exempt || rtrim($path, '/') === $exempt || str_starts_with($path, $exempt . '/')) {
                return $handler->handle($request);
            }
            if ($base !== '') {
                $baseExempt = $base . '/' . ltrim($exempt, '/');
                if ($path === $baseExempt || rtrim($path, '/') === $baseExempt || str_starts_with($path, $baseExempt . '/')) {
                    return $handler->handle($request);
                }
            }
        }

        return $this->csrfTokenMiddleware->process($request, $handler);
    }
}

