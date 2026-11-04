<?php

declare(strict_types=1);

namespace App\Web\WhatsApp\Action;

use App\Domain\User\UserRepositoryInterface;
use App\Domain\WhatsApp\WhatsAppRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;

/**
 * WhatsAppContactBulkDeleteAction.
 * Bulk deletes selected WhatsApp contacts after verifying account ownership.
 * Replicates frontend\controllers\WhatsappController::actionDeleteAll in Yii 3.
 */
final class WhatsAppContactBulkDeleteAction
{
    public function __construct(
        private WhatsAppRepositoryInterface $whatsappRepo,
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
            $response->getBody()->write('Forbidden: You are not authorized to delete WhatsApp contacts.');
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
            return $this->redirect(\App\Environment::url('/whatsapp/importer'));
        }

        $accountId = $context->getScopedAccountId() ?? $currentUser->getAccountId();
        if ($accountId === null || $accountId <= 0) {
            $flash->add('error', 'No active tenant account.');
            return $this->redirect(\App\Environment::url('/whatsapp/importer'));
        }

        try {
            $deletedCount = $this->whatsappRepo->deleteContactsBatch($ids, $accountId);
            $flash->add('success', sprintf('%d phone numbers deleted successfully.', $deletedCount));
        } catch (\Throwable $e) {
            $flash->add('error', 'Database error: ' . $e->getMessage());
        }

        return $this->redirect(\App\Environment::url('/whatsapp/importer'));
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
