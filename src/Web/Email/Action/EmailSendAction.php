<?php

declare(strict_types=1);

namespace App\Web\Email\Action;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\Email\EmailQueue;
use App\Domain\Email\EmailRepositoryInterface;
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
 * EmailSendAction.
 * Handles campaign composition, recipient target selection, and outbound email queueing.
 * Replicates frontend\controllers\EmailSendController in Yii 3.
 */
final class EmailSendAction
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
            $response->getBody()->write('Forbidden: You are not authorized to compose or send email campaigns.');
            return $response;
        }

        $flash = new Flash($this->session);
        $accountId = $context->getScopedAccountId() ?? $currentUser->getAccountId();
        $errors = [];

        // Load templates and contacts for the account
        $templates = $this->emailRepo->findTemplatesByAccount($context, $accountId);
        $contacts = $this->emailRepo->findContactsByAccount($context, $accountId, 1, 500);

        // Separate 'to' and 'bcc' contacts
        $toContacts = [];
        $bccContacts = [];
        foreach ($contacts as $c) {
            if ($c->getType() === 'bcc') {
                $bccContacts[] = $c;
            } else {
                $toContacts[] = $c;
            }
        }

        $formData = [
            'template_id' => '',
            'subject' => '',
            'message' => '',
            'emailList' => [],
            'bccEmailList' => [],
            'name' => '',
            'email' => '',
            'account_id' => $accountId,
        ];

        if ($request->getMethod() === 'POST') {
            $body = (array) $request->getParsedBody();
            $templateId = (int) ($body['template'] ?? ($body['template_id'] ?? 0));
            $subject = trim((string) ($body['subject'] ?? ''));
            $message = trim((string) ($body['message'] ?? ''));
            $emailList = isset($body['emailList']) && is_array($body['emailList'])
                ? array_filter(array_map('intval', $body['emailList']))
                : [];
            $bccEmailList = isset($body['bccEmailList']) && is_array($body['bccEmailList'])
                ? array_filter(array_map('intval', $body['bccEmailList']))
                : [];
            $manualName = trim((string) ($body['name'] ?? ''));
            $manualEmail = trim((string) ($body['email'] ?? ''));

            if ($context->isUnrestricted() && isset($body['account_id']) && (int) $body['account_id'] > 0) {
                $accountId = (int) $body['account_id'];
            }

            $formData = [
                'template_id' => $templateId > 0 ? (string) $templateId : '',
                'subject' => $subject,
                'message' => $message,
                'emailList' => $emailList,
                'bccEmailList' => $bccEmailList,
                'name' => $manualName,
                'email' => $manualEmail,
                'account_id' => $accountId,
            ];

            // Validation rules matching legacy EmailSendEditor
            if ($templateId <= 0) {
                $errors['template'] = 'Please select a base template.';
            }

            if ($subject === '') {
                $errors['subject'] = 'Email subject line is required.';
            }

            if ($message === '') {
                $errors['message'] = 'Email message content is required.';
            }

            // At least one recipient: either selected from list OR manual entry
            $hasListRecipients = !empty($emailList);
            $hasManualRecipient = $manualEmail !== '';

            if (!$hasListRecipients && !$hasManualRecipient) {
                $errors['recipients'] = 'Please select at least one contact or enter manual recipient details.';
            }

            if ($hasManualRecipient && !filter_var($manualEmail, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid manual recipient email address.';
            }

            if (empty($errors)) {
                // Collect targets
                $targets = [];

                if ($hasListRecipients) {
                    foreach ($toContacts as $contact) {
                        if (in_array($contact->getId(), $emailList, true)) {
                            $targets[] = [
                                'name' => $contact->getName(),
                                'email' => $contact->getEmail(),
                            ];
                        }
                    }
                }

                if ($hasManualRecipient) {
                    $targets[] = [
                        'name' => $manualName !== '' ? $manualName : $manualEmail,
                        'email' => $manualEmail,
                    ];
                }

                // Collect BCC emails
                $bccAddresses = [];
                if (!empty($bccEmailList)) {
                    foreach ($bccContacts as $contact) {
                        if (in_array($contact->getId(), $bccEmailList, true)) {
                            $bccAddresses[] = $contact->getEmail();
                        }
                    }
                }
                $bccJson = !empty($bccAddresses) ? json_encode($bccAddresses) : null;

                $queuedCount = 0;
                try {
                    foreach ($targets as $target) {
                        // Render placeholders per recipient
                        $contextTokens = [
                            'name' => $target['name'],
                            'email' => $target['email'],
                        ];
                        $personalizedBody = $this->templateRenderer->renderEmail($message, $contextTokens);

                        $queueItem = EmailQueue::createQueued(
                            accountId: $accountId,
                            name: $target['name'],
                            email: $target['email'],
                            subject: $subject,
                            body: $personalizedBody,
                            templateId: $templateId,
                            bcc: $bccJson,
                        );

                        $this->emailRepo->enqueueEmail($queueItem);
                        $queuedCount++;
                    }

                    $flash->add('success', sprintf('%d emails queued for delivery.', $queuedCount));
                    return $this->redirect('/sys-yii3/email/queue');
                } catch (\Throwable $e) {
                    $flash->add('error', 'Database write error (SELECT-only connection active): ' . $e->getMessage());
                    return $this->redirect('/sys-yii3/email/send');
                }
            }
        }

        $accounts = [];
        if ($context->isUnrestricted()) {
            $accounts = $this->accountRepo->findAllActive(200, 0, $context);
        }

        return $this->viewRenderer
            ->withViewPath('@src/Web/Email/views')
            ->render('send', [
                'templates' => $templates,
                'toContacts' => $toContacts,
                'bccContacts' => $bccContacts,
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

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
