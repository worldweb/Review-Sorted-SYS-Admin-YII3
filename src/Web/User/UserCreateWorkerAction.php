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
 * UserCreateWorkerAction.
 * Handles Team Member creation matching Yii 2 UserController::actionCreateWorker.
 */
final class UserCreateWorkerAction
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

        $currentUser = $this->userRepo->findById($context->getUserId(), $context);
        if ($currentUser === null || !$this->accessDecision->canCreateUserRole($context, User::ROLE_WORKER)) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: You are not authorized to create team members.');
            return $response;
        }

        $accountId = $currentUser->getAccountId();
        if ($accountId === null || $accountId <= 0) {
            $response = $this->responseFactory->createResponse(400);
            $response->getBody()->write('Bad Request: User does not belong to a valid tenant account.');
            return $response;
        }

        $account = $this->accountRepo->findById($accountId, $context);
        $totalUsers = $this->userRepo->countByAccountId($accountId, $context);

        // Check account user limit (reproducing UserController lines 130-135)
        $limitReached = $account !== null && $account->getMaxUsers() <= $totalUsers;

        $errors = [];
        $data = [];
        $infoMessage = null;

        if ($limitReached) {
            $errors['limit'] = 'Account reached user limit (' . ($account?->getMaxUsers() ?? 0) . ' seats max). Please upgrade your plan to add more members.';
        }

        if ($request->getMethod() === 'POST' && !$limitReached) {
            $parsedBody = $request->getParsedBody();
            $data = is_array($parsedBody) ? $parsedBody : [];

            $saveData = UserSaveData::fromArray($data, User::ROLE_WORKER, $accountId);

            // Validation rules
            if ($saveData->name === '') {
                $errors['name'] = 'Name cannot be blank.';
            }
            if ($saveData->email === '') {
                $errors['email'] = 'Email cannot be blank.';
            } elseif (!filter_var($saveData->email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Email is not a valid email address.';
            }
            if ($saveData->password === null || strlen($saveData->password) < 6) {
                $errors['password'] = 'Password should contain at least 6 characters.';
            }
            if ($saveData->password !== $saveData->passwordConfirm) {
                $errors['password_conf'] = 'Passwords don\'t match.';
            }

            if (empty($errors)) {
                $infoMessage = 'Validation passed. Database is running in strict read-only mode during Phase 3, so record insertion was prevented.';
            }
        }

        return $this->viewRenderer
            ->render(__DIR__ . '/views/create', [
                'title' => 'Create Account User',
                'formType' => '_form_worker',
                'currentUser' => $currentUser,
                'data' => $data,
                'errors' => $errors,
                'infoMessage' => $infoMessage,
                'role' => User::ROLE_WORKER,
                'limitReached' => $limitReached,
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
