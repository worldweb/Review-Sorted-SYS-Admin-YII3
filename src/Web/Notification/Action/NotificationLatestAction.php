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
 * Returns latest notifications and unread count for current tenant user or platform administrator.
 */
final class NotificationLatestAction
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
        $context = $this->tenantContextProvider->get();
        $response = $this->responseFactory->createResponse(200)->withHeader('Content-Type', 'application/json');

        if ($context->isGuest()) {
            $response->getBody()->write((string) json_encode([
                'success' => false,
                'unread_count' => 0,
                'items' => [],
            ]));
            return $response;
        }

        $userId = $context->getUserId();
        $currentUser = $this->userRepo->findById($userId, $context);
        if ($currentUser === null) {
            $response->getBody()->write((string) json_encode([
                'success' => false,
                'unread_count' => 0,
                'items' => [],
            ]));
            return $response;
        }

        try {
            $unreadCount = $this->notificationRepo->countUnreadForUser($currentUser);
            $notifications = $this->notificationRepo->findLatestForUser($currentUser, 10);

            $items = [];
            foreach ($notifications as $notification) {
                $timeAgo = $this->formatTimeAgo($notification->getCreatedAt());
                $items[] = [
                    'id' => $notification->getId(),
                    'account_id' => $notification->getAccountId(),
                    'account_name' => $notification->getAccountName(),
                    'type' => $notification->getType(),
                    'title' => $notification->getTitle(),
                    'message' => $notification->getMessage(),
                    'action_url' => $notification->getActionUrl(),
                    'is_read' => $notification->isRead(),
                    'created_at' => $notification->getCreatedAt(),
                    'time_ago' => $timeAgo,
                ];
            }

            $response->getBody()->write((string) json_encode([
                'success' => true,
                'unread_count' => $unreadCount,
                'items' => $items,
            ]));
        } catch (\Throwable $e) {
            $response->getBody()->write((string) json_encode([
                'success' => true,
                'unread_count' => 0,
                'items' => [],
            ]));
        }

        return $response;
    }

    private function formatTimeAgo(int $timestamp): string
    {
        $diff = time() - $timestamp;
        if ($diff < 60) {
            return 'just now';
        }
        if ($diff < 3600) {
            $mins = (int) floor($diff / 60);
            return $mins . 'm ago';
        }
        if ($diff < 86400) {
            $hours = (int) floor($diff / 3600);
            return $hours . 'h ago';
        }
        $days = (int) floor($diff / 86400);
        return $days . 'd ago';
    }
}
