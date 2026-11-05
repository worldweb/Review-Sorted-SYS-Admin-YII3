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

final class GoogleLinkAction
{
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
        $body = (array) ($request->getParsedBody() ?? []);
        $query = $request->getQueryParams();

        $selection = $body['selection'] ?? $query['selection'] ?? null;
        if (is_array($selection) && !empty($selection)) {
            $ids = array_map('intval', $selection);
            if ($this->connectionRepo->linkGoogleLocations($ids, $context)) {
                $flash->set('success', sprintf('Successfully linked %d Google location(s).', count($ids)));
            } else {
                $flash->set('error', 'Error linking selected Google locations. Please try again.');
            }
        } else {
            $flash->set('info', 'Google location linking completed.');
        }

        return $this->redirect(\App\Environment::url('/settings/google'));
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
