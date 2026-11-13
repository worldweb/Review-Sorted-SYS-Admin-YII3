<?php

declare(strict_types=1);

namespace App\Web\LocationGroup\Action;

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
 * LocationGroupViewAction.
 * Displays details of a single Location Group and all its member social destinations.
 * Replicates frontend\controllers\LocationGroupController::actionView in Yii 3.
 */
final class LocationGroupViewAction
{
    public function __construct(
        private LocationGroupRepositoryInterface $groupRepo,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private SessionInterface $session,
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private ?CsrfTokenInterface $csrfToken = null,
        private ?\App\Security\Authorization\RbacInterface $rbac = null,
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

        if (!$currentUser->isAdmin() && !$currentUser->isAccountAdmin() && !($this->rbac?->can($currentUser, '/location-group/*') ?? false)) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: You are not authorized to manage location groups.');
            return $response;
        }

        $queryParams = $request->getQueryParams();
        $id = (int) ($queryParams['id'] ?? $request->getAttribute('id') ?? 0);
        if ($id <= 0) {
            return $this->redirect(\App\Environment::url('/location-group/index'));
        }

        $group = $this->groupRepo->findById($id, $context);
        if ($group === null) {
            $response = $this->responseFactory->createResponse(404);
            $response->getBody()->write('Not Found: The requested location group does not exist or you do not have permission to view it.');
            return $response;
        }

        $flash = new Flash($this->session);

        return $this->viewRenderer
            ->withViewPath('@src/Web/LocationGroup/views')
            ->render('view', [
                'model' => $group,
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
