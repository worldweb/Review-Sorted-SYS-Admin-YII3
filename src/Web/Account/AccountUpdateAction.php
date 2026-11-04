<?php

declare(strict_types=1);

namespace App\Web\Account;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\Account\AccountSaveData;
use App\Domain\User\UserRepositoryInterface;
use App\Security\Authorization\AccessDecisionManager;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * AccountUpdateAction.
 * Handles Workspace editing matching Yii 2 AccountsController::actionUpdate.
 */
final class AccountUpdateAction
{
    public function __construct(
        private AccountRepositoryInterface $accountRepo,
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
            return $this->redirect(\App\Environment::url('/login'));
        }

        $currentUser = $this->userRepo->findById($context->getUserId(), $context);
        if ($currentUser === null) {
            return $this->redirect(\App\Environment::url('/login'));
        }

        $queryParams = $request->getQueryParams();
        $targetId = isset($queryParams['id']) ? (int) $queryParams['id'] : ($currentUser->getAccountId() ?? 0);

        // Server-side authorization check matching AccountsController
        if (!$currentUser->isAdmin()) {
            if (!$context->canAccessAccount($targetId) && $targetId !== $currentUser->getAccountId()) {
                $response = $this->responseFactory->createResponse(403);
                $response->getBody()->write('Forbidden: You are not allowed to edit this workspace.');
                return $response;
            }
        }

        $account = $this->accountRepo->findById($targetId, $context);
        if ($account === null) {
            $response = $this->responseFactory->createResponse(404);
            $response->getBody()->write('The requested workspace does not exist.');
            return $response;
        }

        $errors = [];
        $infoMessage = null;
        $data = [
            'business_name' => $account->getBusinessName(),
            'first_name' => $account->getFirstName(),
            'last_name' => $account->getLastName(),
            'email' => $account->getEmail(),
            'contact_number' => $account->getContactNumber(),
            'website' => $account->getWebsite(),
            'max_users' => $account->getMaxUsers(),
            'status' => $account->getStatus(),
        ];

        if ($request->getMethod() === 'POST') {
            $parsedBody = $request->getParsedBody();
            $postData = is_array($parsedBody) ? $parsedBody : [];

            $saveData = AccountSaveData::fromArray($postData);

            if ($saveData->businessName === '') {
                $errors['business_name'] = 'Business name cannot be blank.';
            }

            $data = array_merge($data, [
                'business_name' => $saveData->businessName,
                'first_name' => $saveData->firstName,
                'last_name' => $saveData->lastName,
                'email' => $saveData->email,
                'contact_number' => $saveData->contactNumber,
                'website' => $saveData->website,
                'max_users' => $saveData->maxUsers,
                'status' => $saveData->status,
            ]);

            if (empty($errors)) {
                try {
                    $this->accountRepo->updateAccount($targetId, $saveData);
                    return $this->redirect(\App\Environment::url('/accounts/view?id=' . $targetId));
                } catch (\Throwable $e) {
                    $errors['general'] = 'Failed to update account: ' . $e->getMessage();
                }
            }
        }

        return $this->viewRenderer
            ->render(__DIR__ . '/views/update', [
                'account' => $account,
                'currentUser' => $currentUser,
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
