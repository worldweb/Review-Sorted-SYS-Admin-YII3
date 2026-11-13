<?php

declare(strict_types=1);

namespace App\Web\LocationGroup\Action;

use App\Domain\LocationGroup\LocationGroupRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * LocationGroupDestinationsAction.
 * AJAX endpoint that returns connected social destinations for a selected account.
 * Replicates frontend\controllers\LocationGroupController::actionGetDestinations in Yii 3.
 */
final class LocationGroupDestinationsAction
{
    public function __construct(
        private LocationGroupRepositoryInterface $groupRepo,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private ResponseFactoryInterface $responseFactory,
        private ?\App\Security\Authorization\RbacInterface $rbac = null,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $this->tenantContextProvider->get();
        if ($context->isGuest()) {
            return $this->jsonResponse(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $currentUser = $this->userRepo->findById($context->getUserId(), $context);
        if ($currentUser === null || (!$currentUser->isAdmin() && !$currentUser->isAccountAdmin() && !($this->rbac?->can($currentUser, '/location-group/*') ?? false))) {
            return $this->jsonResponse(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $queryParams = $request->getQueryParams();
        $accountId = (int) ($queryParams['accountId'] ?? ($queryParams['account_id'] ?? 0));

        if ($accountId <= 0) {
            return $this->jsonResponse([
                'success' => true,
                'accountId' => 0,
                'destinations' => [
                    'google' => [],
                    'facebook' => [],
                    'instagram' => [],
                ],
            ]);
        }

        // Enforce tenant boundary: verify user has access to this account
        if (!$context->isUnrestricted()) {
            $accessibleIds = $context->getAccessibleAccountIds();
            if (!in_array($accountId, $accessibleIds, true)) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Unauthorized account access.',
                    'destinations' => [
                        'google' => [],
                        'facebook' => [],
                        'instagram' => [],
                    ],
                ], 403);
            }
        }

        $destinationsByPlatform = $this->groupRepo->findAvailableDestinationsForAccount($accountId);

        $result = [
            'google' => [],
            'facebook' => [],
            'instagram' => [],
        ];

        foreach ($destinationsByPlatform as $platform => $items) {
            foreach ($items as $item) {
                $result[$platform][] = $item->toArray();
            }
        }

        return $this->jsonResponse([
            'success' => true,
            'accountId' => $accountId,
            'destinations' => $result,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function jsonResponse(array $data, int $status = 200): ResponseInterface
    {
        $response = $this->responseFactory
            ->createResponse($status)
            ->withHeader('Content-Type', 'application/json');

        $response->getBody()->write((string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        return $response;
    }
}
