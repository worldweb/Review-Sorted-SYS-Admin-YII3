<?php

declare(strict_types=1);

namespace App\Web\Subscription\Action;

use App\Domain\Subscription\SubscriptionRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;

final class SubscriptionDeleteAction
{
    public function __construct(
        private SubscriptionRepositoryInterface $subRepo,
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
            return $this->redirect('/sys-yii3/login');
        }

        $currentUser = $this->userRepo->findById($context->getUserId(), $context);
        if ($currentUser === null || !$currentUser->isAdmin()) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: Only platform administrators can manage pricing plans.');
            return $response;
        }

        $id = (int) ($request->getQueryParams()['id'] ?? 0);
        $flash = new Flash($this->session);

        $this->subRepo->deletePlan($id);
        $flash->set('info', 'Plan removed.');

        return $this->redirect('/sys-yii3/subscription');
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
