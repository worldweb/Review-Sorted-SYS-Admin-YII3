<?php

declare(strict_types=1);

namespace App\Web\Notification\Action;

use App\Domain\Notification\NotificationRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Marks single notification as read with ownership validation.
 */
final class NotificationMarkReadAction
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepo,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() !== 'POST') {
            $response = $this->responseFactory->createResponse(405)
                ->withHeader('Allow', 'POST')
                ->withHeader('Content-Type', 'application/json');
            $response->getBody()->write((string) json_encode([
                'success' => false,
                'error' => 'Method Not Allowed. POST is required.',
            ]));
            return $response;
        }

        $context = $this->tenantContextProvider->get();
        $response = $this->responseFactory->createResponse(200)->withHeader('Content-Type', 'application/json');

        if ($context->isGuest()) {
            $response->getBody()->write((string) json_encode(['success' => false]));
            return $response;
        }

        $userId = $context->getUserId();
        $currentUser = $this->userRepo->findById($userId, $context);
        if ($currentUser === null) {
            $response->getBody()->write((string) json_encode(['success' => false]));
            return $response;
        }

        $params = (array) ($request->getParsedBody() ?? []);
        $queryParams = $request->getQueryParams();
        $id = (int) ($params['id'] ?? $queryParams['id'] ?? 0);

        try {
            $marked = false;
            if ($id > 0) {
                $marked = $this->notificationRepo->markAsRead($id, $userId);
            }

            $unreadCount = $this->notificationRepo->countUnreadForUser($currentUser);

            $response->getBody()->write((string) json_encode([
                'success' => $marked,
                'unread_count' => $unreadCount,
            ]));
        } catch (\Throwable $e) {
            $response->getBody()->write((string) json_encode([
                'success' => false,
                'unread_count' => 0,
            ]));
        }

        return $response;
    }
}
