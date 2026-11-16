<?php

declare(strict_types=1);

namespace App\Web\Auth;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\Setting\SettingRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\Middleware\AuthenticationMiddleware;
use App\Security\RateLimiter\FileRateLimiter;
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
        private ?FileRateLimiter $rateLimiter = null,
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
        $allowReg = $this->settingRepo->getBool('app_allow_client_self_registration', true);
        $csrfTokenValue = $this->csrfToken?->getValue();

        $queryParams = $request->getQueryParams();
        $successMessage = isset($queryParams['reset']) && $queryParams['reset'] === '1'
            ? 'Your password has been reset successfully. Please sign in with your new password.'
            : null;

        if ($request->getMethod() === 'POST') {
            $parsedBody = $request->getParsedBody();
            $data = is_array($parsedBody) ? $parsedBody : [];

            $limiter = $this->rateLimiter ?? new FileRateLimiter();
            $ip = $limiter->getClientIp($request);
            $email = isset($data['email']) && is_string($data['email']) ? strtolower(trim($data['email'])) : '';
            $ipKey = "login:ip:{$ip}";
            $userKey = $email !== '' ? "login:user:{$email}" : null;

            if (!$limiter->check($ipKey, 10, 900) || ($userKey !== null && !$limiter->check($userKey, 5, 900))) {
                $retryAfter = max($limiter->getRetryAfter($ipKey, 900), $userKey !== null ? $limiter->getRetryAfter($userKey, 900) : 0);
                $minutes = max(1, (int) ceil($retryAfter / 60));
                return $this->viewRenderer
                    ->withLayout('@src/Web/Shared/Layout/Guest/layout.php')
                    ->render(__DIR__ . '/views/login', [
                        'appName' => $appName,
                        'title' => 'Sign in to your account',
                        'email' => $email,
                        'rememberMe' => false,
                        'error' => "Too many failed login attempts. Please try again in {$minutes} minute(s).",
                        'success' => null,
                        'allowRegistration' => $allowReg,
                        'csrfToken' => $csrfTokenValue,
                    ]);
            }

            $form = new LoginForm($data);
            if ($form->validate($this->authRepo, $this->accountRepo, $this->userRepo)) {
                $identity = $form->getIdentity();
                if ($identity !== null) {
                    // Reset rate limiting on successful authentication
                    $limiter->reset($ipKey);
                    if ($userKey !== null) {
                        $limiter->reset($userKey);
                    }

                    // Prevent session fixation
                    $this->session->regenerateId();
                    $this->session->set(AuthenticationMiddleware::SESSION_AUTH_KEY, $identity->getId());

                    // Establish tenant context for the authenticated user
                    $user = $this->userRepo->findById($identity->getId(), TenantContext::unrestricted());
                    if ($user !== null) {
                        $this->tenantContextProvider->set(TenantContext::fromUser($user));
                    }

                    // Create redirect to protected dashboard
                    $response = $this->redirect(\App\Environment::url('/'));

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
                    'success' => null,
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
                'success' => $successMessage,
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
