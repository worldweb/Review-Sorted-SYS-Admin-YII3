<?php

declare(strict_types=1);

namespace App\Web\WhatsApp\Action;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\WhatsApp\WhatsAppQueue;
use App\Domain\WhatsApp\WhatsAppRepositoryInterface;
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
 * WhatsAppSendAction.
 * Handles composing and queueing outbound WhatsApp messages.
 * Replicates frontend\controllers\WhatsappController::actionSend in Yii 3.
 */
final class WhatsAppSendAction
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
            return $this->redirect(\App\Environment::url('/login'));
        }

        $currentUser = $this->userRepo->findById($context->getUserId(), $context);
        if ($currentUser === null || (!$currentUser->isAdmin() && !$currentUser->isAccountAdmin())) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: You are not authorized to compose or send WhatsApp messages.');
            return $response;
        }

        $flash = new Flash($this->session);
        $accountId = $context->getScopedAccountId() ?? ($context->getAccountId() ?? $currentUser->getAccountId());
        $errors = [];

        // Load templates and contacts for the account
        $templates = $this->whatsappRepo->findTemplatesByAccount($context, $accountId);
        $contacts = $this->whatsappRepo->findContactsByAccount($context, $accountId, 1, 500);

        $formData = [
            'template_id' => '',
            'message' => '',
            'phoneList' => [],
            'name' => '',
            'phone' => '',
            'account_id' => $accountId,
        ];

        if ($request->getMethod() === 'POST') {
            $body = (array) $request->getParsedBody();
            $templateId = (int) ($body['template'] ?? ($body['template_id'] ?? 0));
            $message = trim((string) ($body['message'] ?? ''));
            $phoneList = isset($body['phoneList']) && is_array($body['phoneList'])
                ? array_filter(array_map('intval', $body['phoneList']))
                : [];
            $manualName = trim((string) ($body['name'] ?? ''));
            $manualPhone = trim((string) ($body['phone'] ?? ''));

            if ($context->isUnrestricted() && isset($body['account_id']) && (int) $body['account_id'] > 0) {
                $accountId = (int) $body['account_id'];
            }

            $formData = [
                'template_id' => $templateId > 0 ? (string) $templateId : '',
                'message' => $message,
                'phoneList' => $phoneList,
                'name' => $manualName,
                'phone' => $manualPhone,
                'account_id' => $accountId,
            ];

            if ($templateId <= 0) {
                $errors['template'] = 'Please select a base WhatsApp template.';
            }

            if ($message === '') {
                $errors['message'] = 'WhatsApp message content is required.';
            }

            $hasListRecipients = !empty($phoneList);
            $hasManualRecipient = $manualPhone !== '';

            if (!$hasListRecipients && !$hasManualRecipient) {
                $errors['recipients'] = 'Please select at least one contact or enter manual phone details.';
            }

            if (empty($errors)) {
                $targets = [];

                if ($hasListRecipients) {
                    foreach ($contacts as $contact) {
                        if (in_array($contact->getId(), $phoneList, true)) {
                            $targets[] = [
                                'name' => $contact->getName(),
                                'phone' => $contact->getPhone(),
                            ];
                        }
                    }
                }

                if ($hasManualRecipient) {
                    $normalizedManualPhone = $this->normalizePhone($manualPhone);
                    $targets[] = [
                        'name' => $manualName !== '' ? $manualName : $manualPhone,
                        'phone' => $normalizedManualPhone,
                    ];
                }

                $queuedCount = 0;
                try {
                    foreach ($targets as $target) {
                        $contextTokens = [
                            'name' => $target['name'],
                            'phone' => $target['phone'],
                        ];
                        $personalizedBody = $this->templateRenderer->renderWhatsApp($message, $contextTokens);

                        $queueItem = WhatsAppQueue::createQueued(
                            accountId: $accountId,
                            phone: $target['phone'],
                            message: $personalizedBody,
                        );

                        $this->whatsappRepo->enqueueMessage($queueItem);
                        $queuedCount++;
                    }

                    $flash->add('success', sprintf('%d WhatsApp messages queued for delivery.', $queuedCount));
                    return $this->redirect(\App\Environment::url('/whatsapp/history'));
                } catch (\Throwable $e) {
                    $flash->add('error', 'Database error: ' . $e->getMessage());
                    return $this->redirect(\App\Environment::url('/whatsapp/send'));
                }
            }
        }

        $accounts = [];
        if ($context->isUnrestricted()) {
            $accounts = $this->accountRepo->findAllActive(200, 0, $context);
        }

        return $this->viewRenderer
            ->withViewPath('@src/Web/WhatsApp/views')
            ->render('send', [
                'templates' => $templates,
                'contacts' => $contacts,
                'formData' => $formData,
                'errors' => $errors,
                'accounts' => $accounts,
                'supportedTags' => $this->templateRenderer->getSupportedTags(),
                'currentUser' => $currentUser,
                'context' => $context,
                'flash' => $flash,
                'csrfToken' => $this->csrfToken?->getValue(),
            ]);
    }

    private function normalizePhone(string $raw): string
    {
        $cleaned = preg_replace('/[^\d+]/', '', $raw) ?? '';
        if (str_starts_with($cleaned, '04') && strlen($cleaned) === 10) {
            return '+61' . substr($cleaned, 1);
        }
        if (str_starts_with($cleaned, '614') && strlen($cleaned) === 11) {
            return '+' . $cleaned;
        }
        if (!str_starts_with($cleaned, '+') && strlen($cleaned) >= 10) {
            return '+' . $cleaned;
        }
        return $cleaned;
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
