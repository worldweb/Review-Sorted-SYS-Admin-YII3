<?php

declare(strict_types=1);

namespace App\Web\Account;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * AccountDeleteAction.
 * Handles Workspace deletion matching Yii 2 AccountsController::actionDelete.
 */
final class AccountDeleteAction
{
    public function __construct(
        private AccountRepositoryInterface $accountRepo,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
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
        if ($currentUser === null || !$currentUser->isAdmin()) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: Only platform administrators can delete workspaces.');
            return $response;
        }

        $queryParams = $request->getQueryParams();
        $id = isset($queryParams['id']) ? (int) $queryParams['id'] : 0;
        if ($id <= 0) {
            $body = (array) ($request->getParsedBody() ?? []);
            $id = isset($body['id']) ? (int) $body['id'] : 0;
        }

        if ($id > 0) {
            $this->accountRepo->deleteAccount($id);
        }

        return $this->redirect(\App\Environment::url('/accounts'));
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
