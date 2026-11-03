<?php

declare(strict_types=1);

namespace App\Web\Account;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\Account\AccountSearchFilter;
use App\Domain\Subscription\SubscriptionRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\Authorization\AccessDecisionManager;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * AccountListAction.
 * Lists tenant accounts and displays workspace billing metrics
 * matching Yii 2 AccountsController::actionIndex.
 */
final class AccountListAction
{
    public function __construct(
        private AccountRepositoryInterface $accountRepo,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private AccessDecisionManager $accessDecision,
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private ?CsrfTokenInterface $csrfToken = null,
        private ?SubscriptionRepositoryInterface $subscriptionRepo = null,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $this->tenantContextProvider->get();
        if ($context->isGuest()) {
            return $this->redirect('/sys-yii3/login');
        }

        $currentUserId = $context->getUserId();
        $currentUser = $this->userRepo->findById($currentUserId, $context);
        if ($currentUser === null) {
            return $this->redirect('/sys-yii3/login');
        }

        // Standard workers are not permitted to manage workspaces or billing
        if (!$currentUser->isAdmin() && !$currentUser->isAccountAdmin()) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: You are not authorized to view workspace management.');
            return $response;
        }

        $queryParams = $request->getQueryParams();
        $filter = AccountSearchFilter::fromQueryParams($queryParams);

        $searchResult = $this->accountRepo->search($filter, $context);
        $accounts = $searchResult['accounts'];
        $total = $searchResult['total'];

        // Determine billing owner and fetch metrics
        $billingAccountId = $currentUser->getAccountId() ?? ($accounts[0]?->getId() ?? 1);
        $billingOwnerId = $currentUser->getOwnerId() ?? $currentUser->getId();
        $billingData = $this->accountRepo->getBillingSummary($billingAccountId, $billingOwnerId);

        $availablePlans = [];
        if ($this->subscriptionRepo !== null) {
            try {
                $availablePlans = $this->subscriptionRepo->findAllPlans();
            } catch (\Throwable) {
                $availablePlans = [];
            }
        }

        return $this->viewRenderer
            ->render(__DIR__ . '/views/index', [
                'accounts' => $accounts,
                'total' => $total,
                'filter' => $filter,
                'billingData' => $billingData,
                'currentUser' => $currentUser,
                'context' => $context,
                'csrfToken' => $this->csrfToken?->getValue(),
                'availablePlans' => $availablePlans,
            ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
