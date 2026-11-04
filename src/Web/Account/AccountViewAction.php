<?php

declare(strict_types=1);

namespace App\Web\Account;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\Authorization\AccessDecisionManager;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * AccountViewAction.
 * Displays workspace and billing details matching Yii 2 AccountsController::actionView.
 */
final class AccountViewAction
{
    public function __construct(
        private AccountRepositoryInterface $accountRepo,
        private UserRepositoryInterface $userRepo,
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

        $currentUser = $this->userRepo->findById($context->getUserId(), $context);
        if ($currentUser === null) {
            return $this->redirect(\App\Environment::url('/login'));
        }

        $queryParams = $request->getQueryParams();
        $targetId = isset($queryParams['id']) ? (int) $queryParams['id'] : ($currentUser->getAccountId() ?? 0);

        // Server-side authorization check matching AccountsController
        if (!$currentUser->isAdmin()) {
            if (!$context->canAccessAccount($targetId) && $targetId !== $currentUser->getAccountId()) {
                $response = $this->responseFactory->createResponse(403);
                $response->getBody()->write('Forbidden: You are not allowed to access this workspace.');
                return $response;
            }
        }

        $account = $this->accountRepo->findById($targetId, $context);
        if ($account === null) {
            $response = $this->responseFactory->createResponse(404);
            $response->getBody()->write('The requested workspace does not exist.');
            return $response;
        }

        // Fetch team members in this account
        $members = $this->userRepo->findByAccountId($targetId, 50, 0, $context);

        // Fetch billing summary
        $billingData = $this->accountRepo->getBillingSummary($targetId, $currentUser->getId());

        return $this->viewRenderer
            ->render(__DIR__ . '/views/view', [
                'account' => $account,
                'members' => $members,
                'billingData' => $billingData,
                'currentUser' => $currentUser,
            ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
