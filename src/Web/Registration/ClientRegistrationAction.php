<?php

declare(strict_types=1);

namespace App\Web\Registration;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\Account\AccountSaveData;
use App\Domain\Setting\SettingRepositoryInterface;
use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\User\UserSaveData;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * ClientRegistrationAction.
 * Handles public self-registration matching Yii 2 SiteController::actionNewClient.
 */
final class ClientRegistrationAction
{
    public function __construct(
        private SettingRepositoryInterface $settingRepo,
        private UserRepositoryInterface $userRepo,
        private AccountRepositoryInterface $accountRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private ?CsrfTokenInterface $csrfToken = null,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $this->tenantContextProvider->get();
        if (!$context->isGuest()) {
            return $this->redirect(\App\Environment::url('/'));
        }

        // Check if self-registration is enabled in SystemSettings
        $allowRegistration = $this->settingRepo->getBool('app_allow_client_self_registration', true);
        if (!$allowRegistration) {
            return $this->redirect(\App\Environment::url('/login'));
        }

        $appName = $this->settingRepo->get('branding_app_name', 'Reviews Sorted') ?? 'Reviews Sorted';
        $errors = [];
        $data = [];
        $infoMessage = null;

        if ($request->getMethod() === 'POST') {
            $parsedBody = $request->getParsedBody();
            $data = is_array($parsedBody) ? $parsedBody : [];

            $businessName = trim((string) ($data['business_name'] ?? ''));
            $firstName = trim((string) ($data['first_name'] ?? ''));
            $email = trim((string) ($data['email'] ?? ''));
            $password = (string) ($data['password'] ?? '');
            $passwordConf = (string) ($data['password_conf'] ?? '');

            if ($businessName === '') {
                $errors['business_name'] = 'Business Name cannot be blank.';
            }
            if ($firstName === '') {
                $errors['first_name'] = 'First Name cannot be blank.';
            }
            if ($email === '') {
                $errors['email'] = 'Email cannot be blank.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Email is not a valid email address.';
            } else {
                // Check uniqueness
                $existing = $this->userRepo->findByEmail($email, $context);
                if ($existing !== null) {
                    $errors['email'] = 'This email address is already registered.';
                }
            }

            $minPasswordLen = $this->settingRepo->getInt('security_password_min_length', 6);
            if ($password === '' || strlen($password) < $minPasswordLen) {
                $errors['password'] = sprintf('Password must be at least %d characters.', $minPasswordLen);
            }
            if ($password !== $passwordConf) {
                $errors['password_conf'] = 'Passwords don\'t match.';
            }

            if (empty($errors)) {
                try {
                    $accountId = $this->accountRepo->createAccount(new AccountSaveData(
                        businessName: $businessName,
                        firstName: $firstName,
                        email: $email,
                        status: 1,
                    ));

                    $this->userRepo->createUser(new UserSaveData(
                        name: $firstName,
                        email: $email,
                        password: $password,
                        role: User::ROLE_ACCOUNT_ADMIN,
                        status: User::STATUS_ACTIVE,
                        accountId: $accountId,
                    ));

                    return $this->redirect(\App\Environment::url('/login?registered=1'));
                } catch (\Throwable $e) {
                    $errors['general'] = 'Registration failed: ' . $e->getMessage();
                }
            }
        }

        return $this->viewRenderer
            ->withLayout('@src/Web/Shared/Layout/Guest/layout.php')
            ->render(__DIR__ . '/views/new-client', [
                'appName' => $appName,
                'data' => $data,
                'errors' => $errors,
                'infoMessage' => $infoMessage,
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
