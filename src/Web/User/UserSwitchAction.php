<?php

declare(strict_types=1);

namespace App\Web\User;

use App\Domain\User\UserRepositoryInterface;
use App\Security\Authorization\AccessDecisionManager;
use App\Security\Middleware\AuthenticationMiddleware;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\SessionInterface;

/**
 * UserSwitchAction.
 * Handles tenant workspace context switching matching Yii 2 UserController::actionSwitch.
 */
final class UserSwitchAction
{
    public function __construct(
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private AccessDecisionManager $accessDecision,
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

        $queryParams = $request->getQueryParams();
        $targetAccountId = isset($queryParams['id']) ? (int) $queryParams['id'] : 0;

        if ($targetAccountId <= 0) {
            $response = $this->responseFactory->createResponse(400);
            $response->getBody()->write('Bad Request: Invalid account ID specified.');
            return $response;
        }

        // Server-side authorization check matching Yii 2 rules
        if (!$this->accessDecision->canSwitchToAccount($context, $targetAccountId)) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: You are not authorized to switch to this account.');
            return $response;
        }

        // Resolve target user record matching ownership linkage
        $currentUserId = $context->getUserId();
        $targetUser = $this->userRepo->findSubUserByAccount($currentUserId, $targetAccountId);

        if ($targetUser === null) {
            $response = $this->responseFactory->createResponse(404);
            $response->getBody()->write('Account not found or no associated user record exists for your profile.');
            return $response;
        }

        // Switch session identity
        $this->session->regenerateId();
        $this->session->set(AuthenticationMiddleware::SESSION_AUTH_KEY, $targetUser->getId());

        return $this->redirect('/sys-yii3/');
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
