<?php

declare(strict_types=1);

namespace App\Web\User;

use App\Domain\User\UserRepositoryInterface;
use App\Security\Authorization\AccessDecisionManager;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * UserDeleteAction.
 * Handles user deletion matching Yii 2 UserController::actionDelete.
 */
final class UserDeleteAction
{
    public function __construct(
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private AccessDecisionManager $accessDecision,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $this->tenantContextProvider->get();
        if ($context->isGuest()) {
            return $this->redirect(\App\Environment::url('/login'));
        }

        $queryParams = $request->getQueryParams();
        $targetId = isset($queryParams['id']) ? (int) $queryParams['id'] : 0;

        $targetUser = $this->userRepo->findById($targetId, $context);
        if ($targetUser === null) {
            $response = $this->responseFactory->createResponse(404);
            $response->getBody()->write('User not found.');
            return $response;
        }

        if ($targetUser->isSuperAdmin()) {
            $response = $this->responseFactory->createResponse(400);
            $response->getBody()->write('Cannot delete the super admin.');
            return $response;
        }

        if (!$this->accessDecision->canDeleteUser($context, $targetUser)) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: You are not authorized to delete this user.');
            return $response;
        }

        try {
            $this->userRepo->deleteUser($targetId);
        } catch (\Throwable $e) {
            return $this->redirect(\App\Environment::url('/users?msg=' . urlencode($e->getMessage())));
        }

        return $this->redirect(\App\Environment::url('/users'));
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
