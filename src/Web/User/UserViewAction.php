<?php

declare(strict_types=1);

namespace App\Web\User;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\Authorization\AccessDecisionManager;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * UserViewAction.
 * Displays details of a single user matching Yii 2 UserController::actionView.
 */
final class UserViewAction
{
    public function __construct(
        private UserRepositoryInterface $userRepo,
        private AccountRepositoryInterface $accountRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private AccessDecisionManager $accessDecision,
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $this->tenantContextProvider->get();
        if ($context->isGuest()) {
            return $this->redirect(\App\Environment::url('/login'));
        }

        $currentUserId = $context->getUserId();
        $currentUser = $this->userRepo->findById($currentUserId, $context);
        if ($currentUser === null) {
            return $this->redirect(\App\Environment::url('/login'));
        }

        $queryParams = $request->getQueryParams();
        $targetId = isset($queryParams['id']) ? (int) $queryParams['id'] : $currentUserId;

        $targetUser = $this->userRepo->findById($targetId, $context);
        if ($targetUser === null) {
            $response = $this->responseFactory->createResponse(404);
            $response->getBody()->write('The requested user does not exist.');
            return $response;
        }

        // Server-side authorization check matching Yii 2 UserController lines 61-66
        if (!$this->accessDecision->canViewUser($context, $targetUser)) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('You are not allowed to access this profile.');
            return $response;
        }

        // Fetch associated account if available
        $account = null;
        if ($targetUser->getAccountId() !== null) {
            $account = $this->accountRepo->findById($targetUser->getAccountId(), $context);
        }

        $canEdit = $this->accessDecision->canEditUser($context, $targetUser);
        $canDelete = $this->accessDecision->canDeleteUser($context, $targetUser);

        return $this->viewRenderer
            ->render(__DIR__ . '/views/view', [
                'user' => $targetUser,
                'account' => $account,
                'currentUser' => $currentUser,
                'canEdit' => $canEdit,
                'canDelete' => $canDelete,
            ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
