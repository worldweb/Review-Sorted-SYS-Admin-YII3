<?php

declare(strict_types=1);

namespace App\Web\WhatsApp\Action;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\WhatsApp\WhatsAppRepositoryInterface;
use App\Domain\WhatsApp\WhatsAppTemplate;
use App\Security\TenantContextProviderInterface;
use App\Shared\TemplateRendererService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * WhatsAppTemplateUpdateAction.
 * Handles editing existing WhatsApp templates.
 * Replicates frontend\controllers\WhatsappController::actionUpdate in Yii 3.
 */
final class WhatsAppTemplateUpdateAction
{
    public function __construct(
        private WhatsAppRepositoryInterface $whatsappRepo,
        private AccountRepositoryInterface $accountRepo,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private TemplateRendererService $templateRenderer,
        private SessionInterface $session,
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private ?CsrfTokenInterface $csrfToken = null,
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
            $response->getBody()->write('Forbidden: You are not authorized to edit WhatsApp templates.');
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

        $flash = new Flash($this->session);
        $errors = [];
        $name = $template->getName();
        $text = $template->getText();
        $status = $template->getStatus();

        if ($request->getMethod() === 'POST') {
            $body = (array) $request->getParsedBody();
            $name = trim((string) ($body['name'] ?? ''));
            $text = trim((string) ($body['text'] ?? ''));
            $status = $template->isProtected()
                ? WhatsAppTemplate::STATUS_ACTIVE
                : (isset($body['status']) ? (int) $body['status'] : WhatsAppTemplate::STATUS_ACTIVE);

            if ($name === '') {
                $errors['name'] = 'Template name is required.';
            }

            if ($text === '') {
                $errors['text'] = 'Template message text is required.';
            }

            if (empty($errors)) {
                $cleanText = trim(strip_tags($text));
                $updatedTemplate = $template->withUpdated($name, $cleanText, $status);

                try {
                    $this->whatsappRepo->saveTemplate($updatedTemplate);
                    $flash->add('success', sprintf('WhatsApp template "%s" updated successfully.', $name));
                    return $this->redirect('/sys-yii3/whatsapp/template/view?id=' . $template->getId());
                } catch (\Throwable $e) {
                    $flash->add('error', 'Database write error (SELECT-only connection active): ' . $e->getMessage());
                    return $this->redirect('/sys-yii3/whatsapp/template/view?id=' . $template->getId());
                }
            }
        }

        return $this->viewRenderer
            ->withViewPath('@src/Web/WhatsApp/views')
            ->render('template/update', [
                'template' => $template,
                'name' => $name,
                'text' => $text,
                'status' => $status,
                'supportedTags' => $this->templateRenderer->getSupportedTags(),
                'errors' => $errors,
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
