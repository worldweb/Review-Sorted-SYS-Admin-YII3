<?php

declare(strict_types=1);

namespace App\Web\User;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\User\UserSearchFilter;
use App\Security\Authorization\AccessDecisionManager;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * UserListAction.
 * Lists users matching Yii 2 UserController::actionIndex.
 * Enforces server-side RBAC and tenant boundaries.
 */
final class UserListAction
{
    public function __construct(
        private UserRepositoryInterface $userRepo,
        private AccountRepositoryInterface $accountRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private AccessDecisionManager $accessDecision,
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private ?CsrfTokenInterface $csrfToken = null,
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

        // Server-side authorization check matching Yii 2 AccessControl
        if (
            !$currentUser->isAdmin() &&
            !$currentUser->isAccountAdmin() &&
            !$this->accessDecision->getRbac()->can($currentUser, '/user/*')
        ) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: You are not authorized to view the user directory.');
            return $response;
        }

        $queryParams = $request->getQueryParams();
        $filter = UserSearchFilter::fromQueryParams($queryParams);

        $searchResult = $this->userRepo->search($filter, $context);
        $users = $searchResult['users'];
        $total = $searchResult['total'];

        // Determine action buttons visibility
        $canAddAdmin = $currentUser->isAdmin();
        $canAddAccountAdmin = $currentUser->isAdmin();

        $canAddWorker = false;
        if ($currentUser->isAdmin()) {
            $canAddWorker = true;
        } elseif ($currentUser->isAccountAdmin()) {
            $accId = $context->getAccountId() ?? $currentUser->getAccountId();
            if ($accId !== null) {
                $account = $this->accountRepo->findById($accId, $context);
                if ($account !== null && $account->getMaxUsers() > $total) {
                    $canAddWorker = true;
                }
            }
        }

        // Accounts list for filter dropdown if platform admin
        $accountsList = [];
        if ($currentUser->isAdmin()) {
            $allActive = $this->accountRepo->findAllActive(100, 0, $context);
            foreach ($allActive as $acc) {
                $accountsList[$acc->getId()] = $acc->getBusinessName() ?? "Account #{$acc->getId()}";
            }
        }

        return $this->viewRenderer
            ->render(__DIR__ . '/views/index', [
                'users' => $users,
                'total' => $total,
                'filter' => $filter,
                'currentUser' => $currentUser,
                'context' => $context,
                'canAddAdmin' => $canAddAdmin,
                'canAddAccountAdmin' => $canAddAccountAdmin,
                'canAddWorker' => $canAddWorker,
                'accountsList' => $accountsList,
                'csrfToken' => $this->csrfToken?->getValue(),
            ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
