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
 * EmailContactDeleteAction.
 * Deletes a single email contact after checking tenant ownership.
 * Replicates frontend\controllers\EmailImporterController::actionDelete in Yii 3.
 */
final class EmailContactDeleteAction
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
            return $this->redirect('/sys-yii3/login');
        }

        $currentUser = $this->userRepo->findById($context->getUserId(), $context);
        if ($currentUser === null || (!$currentUser->isAdmin() && !$currentUser->isAccountAdmin())) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: You are not authorized to delete email contacts.');
            return $response;
        }

        $queryParams = $request->getQueryParams();
        $id = (int) ($queryParams['id'] ?? 0);
        if ($id <= 0) {
            $parsedBody = (array) $request->getParsedBody();
            $id = (int) ($parsedBody['id'] ?? 0);
        }

        if ($id <= 0) {
            return $this->redirect('/sys-yii3/email/importer');
        }

        $contact = $this->emailRepo->findContactById($id, $context);
        if ($contact === null) {
            $response = $this->responseFactory->createResponse(404);
            $response->getBody()->write('Not Found: The requested contact does not exist or you do not have permission to delete it.');
            return $response;
        }

        $flash = new Flash($this->session);

        try {
            $this->emailRepo->deleteContact($id);
            $flash->add('success', sprintf('Contact "%s" deleted successfully.', $contact->getEmail()));
        } catch (\Throwable $e) {
            $flash->add('error', 'Database write error (SELECT-only connection active): ' . $e->getMessage());
        }

        return $this->redirect('/sys-yii3/email/importer');
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
