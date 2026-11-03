<?php

declare(strict_types=1);

namespace App\Web\User;

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
 * UserCreateAction.
 * Handles Administrator creation matching Yii 2 UserController::actionCreate.
 */
final class UserCreateAction
{
    public function __construct(
        private UserRepositoryInterface $userRepo,
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
        if ($currentUser === null || !$this->accessDecision->canCreateUserRole($context, User::ROLE_ADMIN)) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: Only platform administrators can create administrators.');
            return $response;
        }

        $errors = [];
        $data = [];
        $infoMessage = null;

        if ($request->getMethod() === 'POST') {
            $parsedBody = $request->getParsedBody();
            $data = is_array($parsedBody) ? $parsedBody : [];

            $saveData = UserSaveData::fromArray($data, User::ROLE_ADMIN);

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
                // Database read-only notification
                $infoMessage = 'Validation passed. Database is running in strict read-only mode during Phase 3, so record insertion was prevented.';
            }
        }

        return $this->viewRenderer
            ->render(__DIR__ . '/views/create', [
                'title' => 'Create Users / Create Administrator',
                'formType' => '_form',
                'currentUser' => $currentUser,
                'data' => $data,
                'errors' => $errors,
                'infoMessage' => $infoMessage,
                'role' => User::ROLE_ADMIN,
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
