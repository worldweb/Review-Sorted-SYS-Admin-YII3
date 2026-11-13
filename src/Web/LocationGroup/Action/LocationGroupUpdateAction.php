<?php

declare(strict_types=1);

namespace App\Web\LocationGroup\Action;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\LocationGroup\LocationGroup;
use App\Domain\LocationGroup\LocationGroupRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * LocationGroupUpdateAction.
 * Updates an existing Location Group and synchronizes destination memberships.
 * Replicates frontend\controllers\LocationGroupController::actionUpdate in Yii 3.
 */
final class LocationGroupUpdateAction
{
    public function __construct(
        private LocationGroupRepositoryInterface $groupRepo,
        private AccountRepositoryInterface $accountRepo,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private SessionInterface $session,
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private ?CsrfTokenInterface $csrfToken = null,
        private ?\App\Security\Authorization\RbacInterface $rbac = null,
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

        if (!$currentUser->isAdmin() && !$currentUser->isAccountAdmin() && !($this->rbac?->can($currentUser, '/location-group/*') ?? false)) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: You are not authorized to manage location groups.');
            return $response;
        }

        $queryParams = $request->getQueryParams();
        $id = (int) ($queryParams['id'] ?? 0);
        if ($id <= 0) {
            return $this->redirect(\App\Environment::url('/location-group/index'));
        }

        $group = $this->groupRepo->findById($id, $context);
        if ($group === null) {
            $response = $this->responseFactory->createResponse(404);
            $response->getBody()->write('Not Found: The requested location group does not exist or you do not have permission to edit it.');
            return $response;
        }

        $flash = new Flash($this->session);
        $selectedAccountId = $group->getAccountId();

        // Account options for display
        $accountOptions = [];
        if ($context->isUnrestricted()) {
            $accounts = $this->accountRepo->findAllActive(200, 0, $context);
            foreach ($accounts as $acc) {
                $accountOptions[$acc->getId()] = $acc->getBusinessName();
            }
        } else {
            $accessibleIds = $context->getAccessibleAccountIds();
            if (!empty($accessibleIds)) {
                $accounts = $this->accountRepo->findAccessibleByAccountIds($accessibleIds, $context);
                foreach ($accounts as $acc) {
                    $accountOptions[$acc->getId()] = $acc->getBusinessName();
                }
            }
        }

        $destinationsByPlatform = $this->groupRepo->findAvailableDestinationsForAccount($selectedAccountId);
        $selectedKeys = $this->groupRepo->findGroupDestinationKeys($id);

        $errors = [];
        $formData = [
            'id' => $group->getId(),
            'name' => $group->getName(),
            'description' => $group->getDescription() ?? '',
            'status' => $group->getStatus(),
            'account_id' => $selectedAccountId,
        ];

        if ($request->getMethod() === 'POST') {
            $body = (array) $request->getParsedBody();
            $formData['name'] = trim((string) ($body['name'] ?? ''));
            $formData['description'] = trim((string) ($body['description'] ?? ''));
            $formData['status'] = isset($body['status']) ? (int) $body['status'] : LocationGroup::STATUS_ACTIVE;

            $rawKeys = $body['destination_keys'] ?? [];
            if (!is_array($rawKeys)) {
                $rawKeys = [];
            }
            $selectedKeys = array_values(array_filter(array_map('strval', $rawKeys)));

            // Validation
            if ($formData['name'] === '') {
                $errors['name'] = 'Group name is required.';
            } elseif (mb_strlen($formData['name']) > 255) {
                $errors['name'] = 'Group name cannot exceed 255 characters.';
            }

            // Verify submitted keys against available destinations for this account
            $availableKeyMap = [];
            foreach ($destinationsByPlatform as $platform => $items) {
                foreach ($items as $item) {
                    $availableKeyMap[$item->getKey()] = true;
                }
            }

            $validatedKeys = [];
            foreach ($selectedKeys as $keyStr) {
                if (isset($availableKeyMap[$keyStr])) {
                    $validatedKeys[] = $keyStr;
                } else {
                    $errors['destination_keys'] = 'One or more selected destinations do not belong to the account.';
                    break;
                }
            }

            if (empty($errors)) {
                $updatedGroup = new LocationGroup(
                    id: $id,
                    accountId: $selectedAccountId,
                    name: $formData['name'],
                    description: $formData['description'] !== '' ? $formData['description'] : null,
                    status: $formData['status'],
                    createdAt: $group->getCreatedAt(),
                    updatedAt: time(),
                );

                try {
                    $this->groupRepo->save($updatedGroup, $validatedKeys);
                    $flash->add('success', sprintf('Location group "%s" updated successfully.', $formData['name']));
                    return $this->redirect(\App\Environment::url('/location-group/view?id=') . $id);
                } catch (\Throwable $e) {
                    $flash->add('error', 'Database error: ' . $e->getMessage());
                }
            }
        }

        return $this->viewRenderer
            ->withViewPath('@src/Web/LocationGroup/views')
            ->render('update', [
                'model' => $group,
                'formData' => $formData,
                'accountOptions' => $accountOptions,
                'destinationsByPlatform' => $destinationsByPlatform,
                'selectedKeys' => $selectedKeys,
                'errors' => $errors,
                'currentUser' => $currentUser,
                'context' => $context,
                'flash' => $flash,
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
