<?php

declare(strict_types=1);

namespace App\Web\LocationGroup\Action;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\LocationGroup\LocationGroupRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * LocationGroupIndexAction.
 * Lists all Location Groups accessible under the authorized tenant scope with pagination.
 * Replicates frontend\controllers\LocationGroupController::actionIndex in Yii 3.
 */
final class LocationGroupIndexAction
{
    public function __construct(
        private LocationGroupRepositoryInterface $groupRepo,
        private AccountRepositoryInterface $accountRepo,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private SessionInterface $session,
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

        $currentUser = $this->userRepo->findById($context->getUserId(), $context);
        if ($currentUser === null) {
            return $this->redirect('/sys-yii3/login');
        }

        // Enforce strict role authorization: Platform Admin and Account Admin only; Worker forbidden
        if (!$currentUser->isAdmin() && !$currentUser->isAccountAdmin()) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: You are not authorized to manage location groups.');
            return $response;
        }

        $flash = new Flash($this->session);
        $queryParams = $request->getQueryParams();

        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $pageSize = 20;

        $accountIdFilter = isset($queryParams['account_id']) && (int) $queryParams['account_id'] > 0
            ? (int) $queryParams['account_id']
            : null;

        // Verify account filter belongs to accessible scope if specified
        if ($accountIdFilter !== null && !$context->isUnrestricted()) {
            if (!in_array($accountIdFilter, $context->getAccessibleAccountIds(), true)) {
                $accountIdFilter = null;
            }
        }

        $groups = $this->groupRepo->findAll($context, $accountIdFilter, $page, $pageSize);
        $total = $this->groupRepo->countAll($context, $accountIdFilter);

        // Populate destination breakdowns for each group
        $hydratedGroups = [];
        foreach ($groups as $g) {
            $destinations = $this->groupRepo->findDestinationsByGroupId($g->getId());
            $hydratedGroups[] = $g->withDestinations($destinations);
        }

        return $this->viewRenderer
            ->withViewPath('@src/Web/LocationGroup/views')
            ->render('index', [
                'groups' => $hydratedGroups,
                'total' => $total,
                'page' => $page,
                'pageSize' => $pageSize,
                'currentUser' => $currentUser,
                'context' => $context,
                'flash' => $flash,
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
