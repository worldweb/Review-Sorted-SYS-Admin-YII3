<?php

declare(strict_types=1);

namespace App\Web\Email\Action;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\Email\EmailRepositoryInterface;
use App\Domain\Email\EmailTemplate;
use App\Domain\User\UserRepositoryInterface;
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
 * EmailTemplateUpdateAction.
 * Handles editing existing email templates.
 * Replicates frontend\controllers\EmailController::actionUpdate in Yii 3.
 */
final class EmailTemplateUpdateAction
{
    public function __construct(
        private EmailRepositoryInterface $emailRepo,
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
            $response->getBody()->write('Forbidden: You are not authorized to edit email templates.');
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

        $flash = new Flash($this->session);
        $errors = [];
        $name = $template->getName();
        $text = $template->getText();
        $status = $template->getStatus();

        if ($request->getMethod() === 'POST') {
            $body = (array) $request->getParsedBody();
            $name = trim((string) ($body['name'] ?? ''));
            $text = trim((string) ($body['text'] ?? ''));
            // If protected, status cannot be deactivated
            $status = $template->isProtected()
                ? EmailTemplate::STATUS_ACTIVE
                : (isset($body['status']) ? (int) $body['status'] : EmailTemplate::STATUS_ACTIVE);

            if ($name === '') {
                $errors['name'] = 'Template name is required.';
            }

            if ($text === '') {
                $errors['text'] = 'Template body content is required.';
            }

            if (empty($errors)) {
                $updatedTemplate = $template->withUpdated($name, $text, $status);

                try {
                    $this->emailRepo->saveTemplate($updatedTemplate);
                    $flash->add('success', sprintf('Template "%s" updated successfully.', $name));
                    return $this->redirect('/sys-yii3/email/template/view?id=' . $template->getId());
                } catch (\Throwable $e) {
                    $flash->add('error', 'Database write error (SELECT-only connection active): ' . $e->getMessage());
                    return $this->redirect('/sys-yii3/email/template/view?id=' . $template->getId());
                }
            }
        }

        return $this->viewRenderer
            ->render(__DIR__ . '/../views/template/update', [
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
