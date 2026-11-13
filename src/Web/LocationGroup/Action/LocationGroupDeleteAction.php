<?php

declare(strict_types=1);

namespace App\Web\LocationGroup\Action;

use App\Domain\LocationGroup\LocationGroupRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;

/**
 * LocationGroupDeleteAction.
 * Deletes a Location Group and cascades member destination assignments.
 * Replicates frontend\controllers\LocationGroupController::actionDelete in Yii 3.
 */
final class LocationGroupDeleteAction
{
    public function __construct(
        private LocationGroupRepositoryInterface $groupRepo,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private SessionInterface $session,
        private ResponseFactoryInterface $responseFactory,
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
        $id = (int) ($queryParams['id'] ?? 0);
        if ($id <= 0) {
            $parsedBody = (array) $request->getParsedBody();
            $id = (int) ($parsedBody['id'] ?? 0);
        }

        if ($id <= 0) {
            return $this->redirect(\App\Environment::url('/location-group/index'));
        }

        $group = $this->groupRepo->findById($id, $context);
        if ($group === null) {
            $response = $this->responseFactory->createResponse(404);
            $response->getBody()->write('Not Found: The requested location group does not exist or you do not have permission to delete it.');
            return $response;
        }

        $flash = new Flash($this->session);
        $groupName = $group->getName();

        try {
            $this->groupRepo->delete($id);
            $flash->add('success', sprintf('Location group "%s" deleted successfully.', $groupName));
        } catch (\Throwable $e) {
            $flash->add('error', 'Database error: ' . $e->getMessage());
        }

        return $this->redirect(\App\Environment::url('/location-group/index'));
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
