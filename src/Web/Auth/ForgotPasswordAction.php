<?php

declare(strict_types=1);

namespace App\Web\Auth;

use App\Domain\Email\Provider\MailgunClientInterface;
use App\Domain\Setting\SettingRepositoryInterface;
use App\Security\Middleware\AuthenticationMiddleware;
use App\Security\UserAuthenticationRepositoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * ForgotPasswordAction.
 * Handles requesting a password reset email securely without exposing user existence.
 */
final class ForgotPasswordAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private SessionInterface $session,
        private UserAuthenticationRepositoryInterface $authRepo,
        private SettingRepositoryInterface $settingRepo,
        private MailgunClientInterface $mailgunClient,
        private ResponseFactoryInterface $responseFactory,
        private ?CsrfTokenInterface $csrfToken = null,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        // If already authenticated, redirect to home
        $userId = $this->session->get(AuthenticationMiddleware::SESSION_AUTH_KEY);
        if ($userId !== null) {
            return $this->redirect(\App\Environment::url('/'));
        }

        $appName = $this->settingRepo->get('branding_app_name', 'Reviews Sorted') ?? 'Reviews Sorted';
        $csrfTokenValue = $this->csrfToken?->getValue();

        if ($request->getMethod() === 'POST') {
            $parsedBody = $request->getParsedBody();
            $data = is_array($parsedBody) ? $parsedBody : [];
            $email = isset($data['email']) && is_string($data['email']) ? trim($data['email']) : '';

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->viewRenderer
                    ->withLayout('@src/Web/Shared/Layout/Guest/layout.php')
                    ->render(__DIR__ . '/views/forgot', [
                        'appName' => $appName,
                        'title' => 'Reset Password',
                        'error' => 'Please provide a valid email address.',
                        'success' => null,
                        'csrfToken' => $csrfTokenValue,
                    ]);
            }

            // Look up active user safely
            $identity = $this->authRepo->findAuthIdentityByEmail($email);
            if ($identity !== null && $identity->isActive()) {
                // Generate secure random token with timestamp suffix
                $token = bin2hex(random_bytes(32)) . '_' . time();
                $saved = $this->authRepo->setPasswordResetToken($identity->getId(), $token);

                if ($saved) {
                    $expireHours = $this->settingRepo->getInt('security_password_reset_token_expire_hours', 1);
                    $expireStr = $expireHours . ' ' . ($expireHours === 1 ? 'hour' : 'hours');
                    $resetLink = \App\Environment::url(
                        '/reset-password?token=' . urlencode($token) . '&email=' . urlencode($identity->getEmail())
                    );

                    $emailData = [
                        'userName' => $identity->getName(),
                        'appName' => $appName,
                        'resetLink' => $resetLink,
                        'expireStr' => $expireStr,
                    ];

                    $htmlBody = $this->renderEmailBody($emailData);
                    $from = $this->settingRepo->get('email_system_from_email')
                        ?? \App\Environment::string('MAIL_FROM_ADDRESS', 'noreply@reviewssorted.com');
                    $subject = "Reset Your Password — {$appName}";

                    try {
                        $this->mailgunClient->send(
                            to: $identity->getEmail(),
                            from: $from,
                            subject: $subject,
                            htmlBody: $htmlBody,
                        );
                        $this->logger?->info(sprintf('[ForgotPassword] Reset email dispatched for user ID: %d', $identity->getId()));
                    } catch (\Throwable $e) {
                        $this->logger?->error(sprintf(
                            '[ForgotPassword] Failed to send password reset email to %s: %s',
                            $identity->getEmail(),
                            $e->getMessage()
                        ));
                    }
                }
            }

            // Generic safe success message preventing email enumeration
            $genericSuccess = 'If that email address is registered with an active account, you will receive password reset instructions shortly.';

            return $this->viewRenderer
                ->withLayout('@src/Web/Shared/Layout/Guest/layout.php')
                ->render(__DIR__ . '/views/forgot', [
                    'appName' => $appName,
                    'title' => 'Reset Password',
                    'error' => null,
                    'success' => $genericSuccess,
                    'csrfToken' => $csrfTokenValue,
                ]);
        }

        // Render initial GET view
        return $this->viewRenderer
            ->withLayout('@src/Web/Shared/Layout/Guest/layout.php')
            ->render(__DIR__ . '/views/forgot', [
                'appName' => $appName,
                'title' => 'Reset Password',
                'error' => null,
                'success' => null,
                'csrfToken' => $csrfTokenValue,
            ]);
    }

    /**
     * Renders the HTML email template for password reset.
     *
     * @param array<string, mixed> $data
     */
    private function renderEmailBody(array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        include __DIR__ . '/views/email/user-password-reset.php';
        return (string) ob_get_clean();
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}

