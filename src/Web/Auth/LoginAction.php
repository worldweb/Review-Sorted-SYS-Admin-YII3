<?php

declare(strict_types=1);

namespace App\Web\Auth;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\Setting\SettingRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\Middleware\AuthenticationMiddleware;
use App\Security\RememberMeService;
use App\Security\TenantContext;
use App\Security\TenantContextProviderInterface;
use App\Security\UserAuthenticationRepositoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * LoginAction.
 * Handles GET login page rendering and POST credential processing.
 */
final class LoginAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private SessionInterface $session,
        private UserAuthenticationRepositoryInterface $authRepo,
        private AccountRepositoryInterface $accountRepo,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private RememberMeService $rememberMeService,
        private SettingRepositoryInterface $settingRepo,
        private ResponseFactoryInterface $responseFactory,
        private ?CsrfTokenInterface $csrfToken = null,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        // If already authenticated, redirect to home
        $userId = $this->session->get(AuthenticationMiddleware::SESSION_AUTH_KEY);
        if ($userId !== null) {
            return $this->redirect('/sys-yii3/');
        }

        $appName = $this->settingRepo->get('branding_app_name', 'Reviews Sorted') ?? 'Reviews Sorted';
        $allowReg = $this->settingRepo->getBool('app_allow_client_self_registration', true);
        $csrfTokenValue = $this->csrfToken?->getValue();

        if ($request->getMethod() === 'POST') {
            $parsedBody = $request->getParsedBody();
            $data = is_array($parsedBody) ? $parsedBody : [];

            $form = new LoginForm($data);
            if ($form->validate($this->authRepo, $this->accountRepo)) {
                $identity = $form->getIdentity();
                if ($identity !== null) {
                    // Prevent session fixation
                    $this->session->regenerateId();
                    $this->session->set(AuthenticationMiddleware::SESSION_AUTH_KEY, $identity->getId());

                    // Establish tenant context for the authenticated user
                    $user = $this->userRepo->findById($identity->getId(), TenantContext::unrestricted());
                    if ($user !== null) {
                        $this->tenantContextProvider->set(TenantContext::fromUser($user));
                    }

                    // Create redirect to protected dashboard
                    $response = $this->redirect('/sys-yii3/');

                    // If Remember Me was requested, attach signed cookie
                    if ($form->isRememberMe()) {
                        $response = $this->rememberMeService->createRememberCookie($identity, $response);
                    }

                    return $response;
                }
            }

            // Render view with error message
            return $this->viewRenderer
                ->withLayout('@src/Web/Shared/Layout/Guest/layout.php')
                ->render(__DIR__ . '/views/login', [
                    'appName' => $appName,
                    'title' => 'Sign in to your account',
                    'email' => $form->getEmail(),
                    'rememberMe' => $form->isRememberMe(),
                    'error' => $form->getFirstError() ?? 'Incorrect email or password.',
                    'allowRegistration' => $allowReg,
                    'csrfToken' => $csrfTokenValue,
                ]);
        }

        // Render initial GET login view
        return $this->viewRenderer
            ->withLayout('@src/Web/Shared/Layout/Guest/layout.php')
            ->render(__DIR__ . '/views/login', [
                'appName' => $appName,
                'title' => 'Sign in to your account',
                'email' => '',
                'rememberMe' => true,
                'error' => null,
                'allowRegistration' => $allowReg,
                'csrfToken' => $csrfTokenValue,
            ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
