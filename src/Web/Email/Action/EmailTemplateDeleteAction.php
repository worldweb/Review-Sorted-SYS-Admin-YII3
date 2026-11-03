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
 * EmailTemplateDeleteAction.
 * Deletes custom email templates. Strictly rejects deletion of protected system templates.
 * Replicates frontend\controllers\EmailController::actionDelete in Yii 3.
 */
final class EmailTemplateDeleteAction
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
            $response->getBody()->write('Forbidden: You are not authorized to delete email templates.');
            return $response;
        }

        $queryParams = $request->getQueryParams();
        $id = (int) ($queryParams['id'] ?? 0);
        if ($id <= 0) {
            $body = (array) $request->getParsedBody();
            $id = (int) ($body['id'] ?? 0);
        }

        if ($id <= 0) {
            return $this->redirect('/sys-yii3/email/templates');
        }

        $template = $this->emailRepo->findTemplateById($id, $context);
        if ($template === null) {
            $response = $this->responseFactory->createResponse(404);
            $response->getBody()->write('Not Found: The requested template does not exist or you do not have permission.');
            return $response;
        }

        // Enforce legacy rule: Protected system templates CANNOT be deleted
        if ($template->isProtected()) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: Protected system templates cannot be deleted.');
            return $response;
        }

        $flash = new Flash($this->session);

        try {
            $this->emailRepo->deleteTemplate($id);
            $flash->add('success', sprintf('Template "%s" deleted successfully.', $template->getName()));
        } catch (\Throwable $e) {
            $flash->add('error', 'Database write error (SELECT-only connection active): ' . $e->getMessage());
        }

        return $this->redirect('/sys-yii3/email/templates');
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
