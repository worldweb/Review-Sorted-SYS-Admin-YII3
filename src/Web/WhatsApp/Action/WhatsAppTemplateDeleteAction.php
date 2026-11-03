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
 * WhatsAppTemplateDeleteAction.
 * Deletes custom WhatsApp templates. Strictly rejects deletion of protected system templates.
 * Replicates frontend\controllers\WhatsappController::actionDelete in Yii 3.
 */
final class WhatsAppTemplateDeleteAction
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
            return $this->redirect('/sys-yii3/login');
        }

        $currentUser = $this->userRepo->findById($context->getUserId(), $context);
        if ($currentUser === null || (!$currentUser->isAdmin() && !$currentUser->isAccountAdmin())) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: You are not authorized to delete WhatsApp templates.');
            return $response;
        }

        $queryParams = $request->getQueryParams();
        $id = (int) ($queryParams['id'] ?? 0);
        if ($id <= 0) {
            $body = (array) $request->getParsedBody();
            $id = (int) ($body['id'] ?? 0);
        }

        if ($id <= 0) {
            return $this->redirect('/sys-yii3/whatsapp/templates');
        }

        $template = $this->whatsappRepo->findTemplateById($id, $context);
        if ($template === null) {
            $response = $this->responseFactory->createResponse(404);
            $response->getBody()->write('Not Found: The requested WhatsApp template does not exist or you do not have permission.');
            return $response;
        }

        if ($template->isProtected()) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: Protected system templates cannot be deleted.');
            return $response;
        }

        $flash = new Flash($this->session);

        try {
            $this->whatsappRepo->deleteTemplate($id);
            $flash->add('success', sprintf('Template "%s" deleted successfully.', $template->getName()));
        } catch (\Throwable $e) {
            $flash->add('error', 'Database write error (SELECT-only connection active): ' . $e->getMessage());
        }

        return $this->redirect('/sys-yii3/whatsapp/templates');
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
