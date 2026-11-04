<?php

declare(strict_types=1);

namespace App\Web\User;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\User\UserSaveData;
use App\Security\Authorization\AccessDecisionManager;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * UserCreateAccAdminAction.
 * Handles Account Administrator creation matching Yii 2 UserController::actionCreateAccAdmin.
 */
final class UserCreateAccAdminAction
{
    public function __construct(
        private UserRepositoryInterface $userRepo,
        private AccountRepositoryInterface $accountRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private AccessDecisionManager $accessDecision,
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
        if ($currentUser === null || !$this->accessDecision->canCreateUserRole($context, User::ROLE_ACCOUNT_ADMIN)) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: You are not authorized to create Account Administrators.');
            return $response;
        }

        $errors = [];
        $data = [];
        $infoMessage = null;

        // Fetch accounts for account dropdown
        $accounts = $this->accountRepo->findAllActive(100, 0, $context);
        $accountsList = [];
        foreach ($accounts as $acc) {
            $accountsList[$acc->getId()] = $acc->getBusinessName() ?? "Account #{$acc->getId()}";
        }

        if ($request->getMethod() === 'POST') {
            $parsedBody = $request->getParsedBody();
            $data = is_array($parsedBody) ? $parsedBody : [];

            $saveData = UserSaveData::fromArray($data, User::ROLE_ACCOUNT_ADMIN);

            // Validation rules
            if ($saveData->name === '') {
                $errors['name'] = 'Name cannot be blank.';
            }
            if ($saveData->email === '') {
                $errors['email'] = 'Email cannot be blank.';
            } elseif (!filter_var($saveData->email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Email is not a valid email address.';
            }
            if ($saveData->accountId === null || $saveData->accountId <= 0) {
                $errors['account_id'] = 'Account cannot be blank.';
            }
            if ($saveData->password === null || strlen($saveData->password) < 6) {
                $errors['password'] = 'Password should contain at least 6 characters.';
            }
            if ($saveData->password !== $saveData->passwordConfirm) {
                $errors['password_conf'] = 'Passwords don\'t match.';
            }

            if (empty($errors)) {
                try {
                    $newUserId = $this->userRepo->createUser($saveData);
                    if ($newUserId > 0) {
                        return $this->redirect(\App\Environment::url('/users/view?id=' . $newUserId));
                    }
                    $errors['general'] = 'Failed to create account administrator.';
                } catch (\Throwable $e) {
                    $errors['general'] = 'Failed to create account administrator: ' . $e->getMessage();
                }
            }
        }

        return $this->viewRenderer
            ->render(__DIR__ . '/views/create', [
                'title' => 'Create Account Administrator',
                'formType' => '_form_acc_admin',
                'currentUser' => $currentUser,
                'data' => $data,
                'errors' => $errors,
                'infoMessage' => $infoMessage,
                'role' => User::ROLE_ACCOUNT_ADMIN,
                'accountsList' => $accountsList,
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
