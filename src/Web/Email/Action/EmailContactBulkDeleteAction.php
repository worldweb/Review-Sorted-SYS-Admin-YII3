<?php

declare(strict_types=1);

namespace App\Web\Email\Action;

use App\Domain\Email\EmailRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;

/**
 * EmailContactBulkDeleteAction.
 * Bulk deletes selected email contacts after verifying account ownership.
 * Replicates frontend\controllers\EmailImporterController::actionDeleteAll in Yii 3.
 */
final class EmailContactBulkDeleteAction
{
    public function __construct(
        private EmailRepositoryInterface $emailRepo,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private SessionInterface $session,
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
        if ($currentUser === null || (!$currentUser->isAdmin() && !$currentUser->isAccountAdmin())) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: You are not authorized to delete email contacts.');
            return $response;
        }

        $flash = new Flash($this->session);

        $parsedBody = (array) $request->getParsedBody();
        $rawIds = $parsedBody['ids'] ?? ($request->getQueryParams()['ids'] ?? []);

        if (is_string($rawIds)) {
            $ids = array_filter(array_map('intval', explode(',', $rawIds)));
        } elseif (is_array($rawIds)) {
            $ids = array_filter(array_map('intval', $rawIds));
        } else {
            $ids = [];
        }

        if (empty($ids)) {
            $flash->add('error', 'No contacts selected for deletion.');
            return $this->redirect(\App\Environment::url('/email/importer'));
        }

        $accountId = $context->getScopedAccountId() ?? $currentUser->getAccountId();
        if ($accountId === null || $accountId <= 0) {
            $flash->add('error', 'No active tenant account.');
            return $this->redirect(\App\Environment::url('/email/importer'));
        }

        try {
            $deletedCount = $this->emailRepo->deleteContactsBatch($ids, $accountId);
            $flash->add('success', sprintf('%d contacts deleted successfully.', $deletedCount));
        } catch (\Throwable $e) {
            $flash->add('error', 'Database error: ' . $e->getMessage());
        }

        return $this->redirect(\App\Environment::url('/email/importer'));
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
