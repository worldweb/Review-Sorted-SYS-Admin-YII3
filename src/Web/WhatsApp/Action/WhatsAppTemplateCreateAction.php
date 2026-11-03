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
 * WhatsAppTemplateCreateAction.
 * Handles creating new custom WhatsApp templates (plain text).
 * Replicates frontend\controllers\WhatsappController::actionCreate in Yii 3.
 */
final class WhatsAppTemplateCreateAction
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
            $response->getBody()->write('Forbidden: You are not authorized to create WhatsApp templates.');
            return $response;
        }

        $flash = new Flash($this->session);
        $errors = [];
        $name = '';
        $text = '';
        $status = WhatsAppTemplate::STATUS_ACTIVE;
        $targetAccountId = $context->getScopedAccountId() ?? $currentUser->getAccountId();

        if ($request->getMethod() === 'POST') {
            $body = (array) $request->getParsedBody();
            $name = trim((string) ($body['name'] ?? ''));
            $text = trim((string) ($body['text'] ?? ''));
            $status = isset($body['status']) ? (int) $body['status'] : WhatsAppTemplate::STATUS_ACTIVE;

            if ($context->isUnrestricted() && isset($body['account_id']) && (int) $body['account_id'] > 0) {
                $targetAccountId = (int) $body['account_id'];
            }

            if ($name === '') {
                $errors['name'] = 'Template name is required.';
            }

            if ($text === '') {
                $errors['text'] = 'Template message text is required.';
            }

            if (empty($errors)) {
                // Ensure plain text (strip tags)
                $cleanText = trim(strip_tags($text));

                $newTemplate = WhatsAppTemplate::createCustom(
                    name: $name,
                    text: $cleanText,
                    accountId: $targetAccountId,
                    status: $status,
                );

                try {
                    $this->whatsappRepo->saveTemplate($newTemplate);
                    $flash->add('success', sprintf('WhatsApp template "%s" created successfully.', $name));
                    return $this->redirect('/sys-yii3/whatsapp/templates');
                } catch (\Throwable $e) {
                    $flash->add('error', 'Database write error (SELECT-only connection active): ' . $e->getMessage());
                    return $this->redirect('/sys-yii3/whatsapp/templates');
                }
            }
        }

        $accounts = [];
        if ($context->isUnrestricted()) {
            $accounts = $this->accountRepo->findAllActive(200, 0, $context);
        }

        return $this->viewRenderer
            ->withViewPath('@src/Web/WhatsApp/views')
            ->render('template/create', [
                'name' => $name,
                'text' => $text,
                'status' => $status,
                'targetAccountId' => $targetAccountId,
                'accounts' => $accounts,
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
