<?php

declare(strict_types=1);

namespace App\Web\WhatsApp\Action;

use App\Domain\User\UserRepositoryInterface;
use App\Domain\WhatsApp\WhatsAppRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * WhatsAppTemplateViewAction.
 * Displays WhatsApp template details, metadata, and plain-text preview.
 */
final class WhatsAppTemplateViewAction
{
    public function __construct(
        private WhatsAppRepositoryInterface $whatsappRepo,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private WebViewRenderer $viewRenderer,
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
            $response->getBody()->write('Forbidden: You are not authorized to view WhatsApp templates.');
            return $response;
        }

        $id = (int) ($request->getQueryParams()['id'] ?? $request->getAttribute('id') ?? 0);
        if ($id <= 0) {
            return $this->redirect(\App\Environment::url('/whatsapp/templates'));
        }

        $template = $this->whatsappRepo->findTemplateById($id, $context);
        if ($template === null) {
            $response = $this->responseFactory->createResponse(404);
            $response->getBody()->write('Not Found: The requested WhatsApp template does not exist or you do not have permission.');
            return $response;
        }

        return $this->viewRenderer
            ->withViewPath('@src/Web/WhatsApp/views')
            ->render('template/view', [
                'template' => $template,
                'currentUser' => $currentUser,
                'context' => $context,
            ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
