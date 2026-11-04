<?php

declare(strict_types=1);

namespace App\Web\SocialConnection\Action;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * SettingsIndexAction.
 * Main entry point for /settings, redirects to /settings/facebook as per legacy behavior.
 */
final class SettingsIndexAction
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)
            ->withHeader('Location', \App\Environment::url('/settings/facebook'));
    }
}
