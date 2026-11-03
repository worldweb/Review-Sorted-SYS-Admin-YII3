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
 * LocationGroupCreateAction.
 * Handles the creation of a new Location Group and its initial social destination memberships.
 * Replicates frontend\controllers\LocationGroupController::actionCreate in Yii 3.
 */
final class LocationGroupCreateAction
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
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $this->tenantContextProvider->get();
        if ($context->isGuest()) {
            return $this->redirect('/sys-yii3/login');
        }

        $currentUser = $this->userRepo->findById($context->getUserId(), $context);
        if ($currentUser === null) {
            return $this->redirect('/sys-yii3/login');
        }

        if (!$currentUser->isAdmin() && !$currentUser->isAccountAdmin()) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: You are not authorized to manage location groups.');
            return $response;
        }

        $flash = new Flash($this->session);

        // Resolve accessible accounts for dropdown
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

        // Determine pre-selected account ID
        $selectedAccountId = null;
        if (count($accountOptions) === 1) {
            $selectedAccountId = (int) key($accountOptions);
        } elseif ($currentUser->getAccountId() !== null && isset($accountOptions[$currentUser->getAccountId()])) {
            $selectedAccountId = $currentUser->getAccountId();
        }

        $destinationsByPlatform = $selectedAccountId !== null
            ? $this->groupRepo->findAvailableDestinationsForAccount($selectedAccountId)
            : ['google' => [], 'facebook' => [], 'instagram' => []];

        $selectedKeys = [];
        $errors = [];
        $formData = [
            'name' => '',
            'description' => '',
            'status' => LocationGroup::STATUS_ACTIVE,
            'account_id' => $selectedAccountId ?? 0,
        ];

        if ($request->getMethod() === 'POST') {
            $body = (array) $request->getParsedBody();
            $formData['name'] = trim((string) ($body['name'] ?? ''));
            $formData['description'] = trim((string) ($body['description'] ?? ''));
            $formData['status'] = isset($body['status']) ? (int) $body['status'] : LocationGroup::STATUS_ACTIVE;
            $submittedAccountId = (int) ($body['account_id'] ?? 0);
            $formData['account_id'] = $submittedAccountId;

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

            if ($submittedAccountId <= 0 || !isset($accountOptions[$submittedAccountId])) {
                $errors['account_id'] = 'You are not authorized to create location groups for this account.';
            } else {
                $destinationsByPlatform = $this->groupRepo->findAvailableDestinationsForAccount($submittedAccountId);
            }

            // Verify submitted destination keys belong to this account's available destinations
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
                    $errors['destination_keys'] = 'One or more selected destinations do not belong to the selected account.';
                    break;
                }
            }

            if (empty($errors)) {
                $newGroup = new LocationGroup(
                    id: 0,
                    accountId: $submittedAccountId,
                    name: $formData['name'],
                    description: $formData['description'] !== '' ? $formData['description'] : null,
                    status: $formData['status'],
                    createdAt: time(),
                    updatedAt: time(),
                );

                try {
                    $newId = $this->groupRepo->save($newGroup, $validatedKeys);
                    $flash->add('success', sprintf('Location group "%s" created successfully.', $formData['name']));
                    return $this->redirect('/sys-yii3/location-group/view?id=' . $newId);
                } catch (\Throwable $e) {
                    $flash->add('error', 'Database write error (SELECT-only connection active): ' . $e->getMessage());
                }
            }
        }

        return $this->viewRenderer
            ->withViewPath('@src/Web/LocationGroup/views')
            ->render('create', [
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
