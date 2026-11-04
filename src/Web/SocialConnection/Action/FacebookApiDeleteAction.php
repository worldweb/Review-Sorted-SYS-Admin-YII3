<?php

declare(strict_types=1);

namespace App\Web\SocialConnection\Action;

use App\Domain\SocialConnection\Api;
use App\Domain\SocialConnection\SocialConnectionRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;

final class FacebookApiDeleteAction
{
    public function __construct(
        private SocialConnectionRepositoryInterface $connectionRepo,
        private UserRepositoryInterface $userRepo,
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

        $currentUser = $this->userRepo->findById($context->getUserId(), $context);
        if ($currentUser === null || !$currentUser->isAdmin()) {
            return $this->redirect(\App\Environment::url('/settings/facebook'));
        }

        $id = (int) ($request->getQueryParams()['id'] ?? 0);
        $api = $this->connectionRepo->findApiById($id);
        if ($api === null || $api->getServiceId() !== Api::SERVICE_FACEBOOK) {
            return $this->redirect(\App\Environment::url('/settings/facebook-api'));
        }

        $flash = new Flash($this->session);
        $assigned = $this->connectionRepo->countAssignedPages($id);
        if ($assigned > 0) {
            $flash->add('error', 'You cannot delete this App that has pages assigned to it.');
        } else {
            if ($this->connectionRepo->deleteApi($id)) {
                $flash->add('success', 'Facebook Application successfully deleted.');
            } else {
                $flash->add('error', 'Failed to delete Facebook Application.');
            }
        }

        return $this->redirect(\App\Environment::url('/settings/facebook-api'));
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
