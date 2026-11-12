<?php

declare(strict_types=1);

namespace App\Web\Auth;

use App\Domain\Setting\SettingRepositoryInterface;
use App\Security\Middleware\AuthenticationMiddleware;
use App\Security\UserAuthIdentity;
use App\Security\UserAuthenticationRepositoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * ResetPasswordAction.
 * Handles token verification and setting a new password.
 */
final class ResetPasswordAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private SessionInterface $session,
        private UserAuthenticationRepositoryInterface $authRepo,
        private SettingRepositoryInterface $settingRepo,
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

            $token = isset($data['token']) && is_string($data['token']) ? trim($data['token']) : '';
            $email = isset($data['email']) && is_string($data['email']) ? trim($data['email']) : '';
            $password = isset($data['password']) && is_string($data['password']) ? $data['password'] : '';
            $passwordConf = isset($data['password_conf']) && is_string($data['password_conf']) ? $data['password_conf'] : '';

            $identity = $this->validateTokenAndResolveIdentity($token, $email);
            if ($identity === null) {
                return $this->viewRenderer
                    ->withLayout('@src/Web/Shared/Layout/Guest/layout.php')
                    ->render(__DIR__ . '/views/reset', [
                        'appName' => $appName,
                        'title' => 'Set New Password',
                        'error' => 'This password reset link is invalid or has expired. Please request a new one.',
                        'csrfToken' => $csrfTokenValue,
                        'token' => null,
                        'email' => null,
                        'isInvalidToken' => true,
                    ]);
            }

            // Validate new password rules
            $minLen = $this->settingRepo->getInt('security_password_min_length', 6);
            $validationError = null;

            if ($password === '') {
                $validationError = 'Password cannot be blank.';
            } elseif (strlen($password) < $minLen) {
                $validationError = sprintf('Password must be at least %d characters.', $minLen);
            } elseif ($password !== $passwordConf) {
                $validationError = 'Passwords do not match.';
            }

            if ($validationError !== null) {
                return $this->viewRenderer
                    ->withLayout('@src/Web/Shared/Layout/Guest/layout.php')
                    ->render(__DIR__ . '/views/reset', [
                        'appName' => $appName,
                        'title' => 'Set New Password',
                        'error' => $validationError,
                        'csrfToken' => $csrfTokenValue,
                        'token' => $token,
                        'email' => $email,
                        'isInvalidToken' => false,
                    ]);
            }

            // Securely hash password and update record
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $updated = $this->authRepo->resetPassword($identity->getId(), $passwordHash);

            if ($updated) {
                $this->logger?->info(sprintf('[ResetPassword] Password successfully reset for user ID: %d', $identity->getId()));
                return $this->redirect(\App\Environment::url('/login?reset=1'));
            }

            return $this->viewRenderer
                ->withLayout('@src/Web/Shared/Layout/Guest/layout.php')
                ->render(__DIR__ . '/views/reset', [
                    'appName' => $appName,
                    'title' => 'Set New Password',
                    'error' => 'An error occurred while updating your password. Please try again.',
                    'csrfToken' => $csrfTokenValue,
                    'token' => $token,
                    'email' => $email,
                    'isInvalidToken' => false,
                ]);
        }

        // Handle GET request: validate token from query parameters
        $queryParams = $request->getQueryParams();
        $token = isset($queryParams['token']) && is_string($queryParams['token']) ? trim($queryParams['token']) : '';
        $email = isset($queryParams['email']) && is_string($queryParams['email']) ? trim($queryParams['email']) : '';

        $identity = $this->validateTokenAndResolveIdentity($token, $email);
        if ($identity === null) {
            return $this->viewRenderer
                ->withLayout('@src/Web/Shared/Layout/Guest/layout.php')
                ->render(__DIR__ . '/views/reset', [
                    'appName' => $appName,
                    'title' => 'Set New Password',
                    'error' => 'This password reset link is invalid or has expired. Please request a new one.',
                    'csrfToken' => $csrfTokenValue,
                    'token' => null,
                    'email' => null,
                    'isInvalidToken' => true,
                ]);
        }

        return $this->viewRenderer
            ->withLayout('@src/Web/Shared/Layout/Guest/layout.php')
            ->render(__DIR__ . '/views/reset', [
                'appName' => $appName,
                'title' => 'Set New Password',
                'error' => null,
                'csrfToken' => $csrfTokenValue,
                'token' => $token,
                'email' => $email,
                'isInvalidToken' => false,
            ]);
    }

    /**
     * Validates token format, expiration, user status, and optional email match.
     */
    private function validateTokenAndResolveIdentity(string $token, string $email = ''): ?UserAuthIdentity
    {
        if ($token === '' || !str_contains($token, '_')) {
            return null;
        }

        // Token format: <random_hex>_<timestamp>
        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        if ($timestamp <= 0) {
            return null;
        }

        $expireHours = $this->settingRepo->getInt('security_password_reset_token_expire_hours', 1);
        $expireSec = $expireHours * 3600;

        if (($timestamp + $expireSec) < time()) {
            return null;
        }

        $identity = $this->authRepo->findAuthIdentityByResetToken($token);
        if ($identity === null || !$identity->isActive()) {
            return null;
        }

        if ($email !== '' && strcasecmp($identity->getEmail(), trim($email)) !== 0) {
            return null;
        }

        return $identity;
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}

