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
 * EmailTemplateCreateAction.
 * Handles creating new custom email templates.
 * Replicates frontend\controllers\EmailController::actionCreate in Yii 3.
 */
final class EmailTemplateCreateAction
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
            $response->getBody()->write('Forbidden: You are not authorized to create email templates.');
            return $response;
        }

        $flash = new Flash($this->session);
        $errors = [];
        $name = '';
        $text = '';
        $status = EmailTemplate::STATUS_ACTIVE;
        $targetAccountId = $context->getScopedAccountId() ?? $currentUser->getAccountId();

        if ($request->getMethod() === 'POST') {
            $body = (array) $request->getParsedBody();
            $name = trim((string) ($body['name'] ?? ''));
            $text = trim((string) ($body['text'] ?? ''));
            $status = isset($body['status']) ? (int) $body['status'] : EmailTemplate::STATUS_ACTIVE;

            if ($context->isUnrestricted() && isset($body['account_id']) && (int) $body['account_id'] > 0) {
                $targetAccountId = (int) $body['account_id'];
            }

            if ($name === '') {
                $errors['name'] = 'Template name is required.';
            }

            if ($text === '') {
                $errors['text'] = 'Template body content is required.';
            }

            if (empty($errors)) {
                $newTemplate = EmailTemplate::createCustom(
                    name: $name,
                    text: $text,
                    accountId: $targetAccountId,
                    status: $status,
                );

                try {
                    $newId = $this->emailRepo->saveTemplate($newTemplate);
                    $flash->add('success', sprintf('Email template "%s" created successfully.', $name));
                    return $this->redirect('/sys-yii3/email/templates');
                } catch (\Throwable $e) {
                    $flash->add('error', 'Database write error (SELECT-only connection active): ' . $e->getMessage());
                    return $this->redirect('/sys-yii3/email/templates');
                }
            }
        }

        $accounts = [];
        if ($context->isUnrestricted()) {
            $accounts = $this->accountRepo->findAllActive(200, 0, $context);
        }

        return $this->viewRenderer
            ->withViewPath('@src/Web/Email/views')
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
