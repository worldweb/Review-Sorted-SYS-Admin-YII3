<?php

declare(strict_types=1);

namespace App\Web\Account;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;

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
        private ?SessionInterface $session = null,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() !== 'POST') {
            return $this->responseFactory->createResponse(405)->withHeader('Allow', 'POST');
        }

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

        $body = (array) ($request->getParsedBody() ?? []);
        $id = isset($body['id']) ? (int) $body['id'] : ((int) ($request->getQueryParams()['id'] ?? 0));

        if ($id > 0) {
            try {
                $hasDeps = $this->accountRepo->hasDependencies($id);
                $this->accountRepo->deleteAccount($id);
                if ($this->session !== null) {
                    $msg = $hasDeps
                        ? 'Workspace has existing activity records and was deactivated successfully.'
                        : 'Workspace deleted successfully.';
                    (new Flash($this->session))->add('success', $msg);
                }
            } catch (\Throwable) {
                if ($this->session !== null) {
                    (new Flash($this->session))->add('error', 'Unable to delete workspace. Please deactivate it instead.');
                }
            }
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
