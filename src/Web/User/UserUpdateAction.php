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
 * UserUpdateAction.
 * Handles user modification matching Yii 2 UserController::actionUpdate.
 */
final class UserUpdateAction
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

        $currentUserId = $context->getUserId();
        $currentUser = $this->userRepo->findById($currentUserId, $context);
        if ($currentUser === null) {
            return $this->redirect(\App\Environment::url('/login'));
        }

        $queryParams = $request->getQueryParams();
        $targetId = isset($queryParams['id']) ? (int) $queryParams['id'] : $currentUserId;

        $targetUser = $this->userRepo->findById($targetId, $context);
        if ($targetUser === null) {
            $response = $this->responseFactory->createResponse(404);
            $response->getBody()->write('The requested user does not exist.');
            return $response;
        }

        // Server-side authorization check matching UserController lines 164-169
        if (!$this->accessDecision->canEditUser($context, $targetUser)) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: You are not allowed to access or edit this profile.');
            return $response;
        }

        $errors = [];
        $infoMessage = null;
        $data = [
            'name' => $targetUser->getName(),
            'email' => $targetUser->getEmail(),
            'status' => $targetUser->getStatus(),
            'account_id' => $targetUser->getAccountId(),
            'role' => $targetUser->getRole(),
        ];

        // Fetch accounts for account dropdown if platform admin
        $accountsList = [];
        if ($currentUser->isAdmin()) {
            $accounts = $this->accountRepo->findAllActive(100, 0, $context);
            foreach ($accounts as $acc) {
                $accountsList[$acc->getId()] = $acc->getBusinessName() ?? "Account #{$acc->getId()}";
            }
        }

        if ($request->getMethod() === 'POST') {
            $parsedBody = $request->getParsedBody();
            $postData = is_array($parsedBody) ? $parsedBody : [];

            $saveData = UserSaveData::fromArray($postData, $targetUser->getRole() ?? User::ROLE_WORKER, $targetUser->getAccountId());

            // Non-admins cannot alter their tenant account association or role
            $effectiveAccountId = $currentUser->isAdmin() ? $saveData->accountId : $targetUser->getAccountId();
            $effectiveRole = $currentUser->isAdmin() ? $saveData->role : ($targetUser->getRole() ?? User::ROLE_WORKER);

            $sanitizedSaveData = new UserSaveData(
                name: $saveData->name,
                email: $saveData->email,
                password: $saveData->password,
                passwordConfirm: $saveData->passwordConfirm,
                role: $effectiveRole,
                status: $saveData->status,
                accountId: $effectiveAccountId,
                ownerId: $targetUser->getOwnerId(),
                extraAccounts: $saveData->extraAccounts,
            );

            // Validation rules
            if ($sanitizedSaveData->name === '') {
                $errors['name'] = 'Name cannot be blank.';
            }
            if ($sanitizedSaveData->email === '') {
                $errors['email'] = 'Email cannot be blank.';
            } elseif (!filter_var($sanitizedSaveData->email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Email is not a valid email address.';
            } else {
                $existing = $this->userRepo->findByEmail($sanitizedSaveData->email, $context);
                if ($existing !== null && $existing->getId() !== $targetId) {
                    $errors['email'] = 'Email already registered.';
                }
            }

            // Optional password update
            if ($sanitizedSaveData->password !== null && $sanitizedSaveData->password !== '') {
                if (strlen($sanitizedSaveData->password) < 6) {
                    $errors['password'] = 'Password should contain at least 6 characters.';
                }
                if ($sanitizedSaveData->password !== $sanitizedSaveData->passwordConfirm) {
                    $errors['password_conf'] = 'Passwords don\'t match.';
                }
            }

            $data = array_merge($data, [
                'name' => $sanitizedSaveData->name,
                'email' => $sanitizedSaveData->email,
                'status' => $sanitizedSaveData->status,
                'account_id' => $effectiveAccountId,
            ]);

            if (empty($errors)) {
                try {
                    $this->userRepo->updateUser($targetId, $sanitizedSaveData);
                    return $this->redirect(\App\Environment::url('/user/view?id=' . $targetId));
                } catch (\Throwable $e) {
                    $errors['general'] = 'Failed to update user: ' . $e->getMessage();
                }
            }
        }

        return $this->viewRenderer
            ->render(__DIR__ . '/views/update', [
                'user' => $targetUser,
                'currentUser' => $currentUser,
                'data' => $data,
                'errors' => $errors,
                'infoMessage' => $infoMessage,
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
