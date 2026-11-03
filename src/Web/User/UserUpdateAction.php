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
            return $this->redirect('/sys-yii3/login');
        }

        $currentUserId = $context->getUserId();
        $currentUser = $this->userRepo->findById($currentUserId, $context);
        if ($currentUser === null) {
            return $this->redirect('/sys-yii3/login');
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

            // Non-admins cannot alter their tenant account association
            $effectiveAccountId = $currentUser->isAdmin() ? $saveData->accountId : $targetUser->getAccountId();

            // Validation rules
            if ($saveData->name === '') {
                $errors['name'] = 'Name cannot be blank.';
            }
            if ($saveData->email === '') {
                $errors['email'] = 'Email cannot be blank.';
            } elseif (!filter_var($saveData->email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Email is not a valid email address.';
            }

            // Optional password update
            if ($saveData->password !== null && $saveData->password !== '') {
                if (strlen($saveData->password) < 6) {
                    $errors['password'] = 'Password should contain at least 6 characters.';
                }
                if ($saveData->password !== $saveData->passwordConfirm) {
                    $errors['password_conf'] = 'Passwords don\'t match.';
                }
            }

            $data = array_merge($data, [
                'name' => $saveData->name,
                'email' => $saveData->email,
                'status' => $saveData->status,
                'account_id' => $effectiveAccountId,
            ]);

            if (empty($errors)) {
                $infoMessage = 'Validation passed. Database is running in strict read-only mode during Phase 3, so record update was prevented.';
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
