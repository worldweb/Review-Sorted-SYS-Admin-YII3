<?php

declare(strict_types=1);

namespace App\Web\Notification\Action;

use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * Returns latest notifications and unread count for current tenant user.
 */
final class NotificationLatestAction
{
    public function __construct(
        private ConnectionInterface $db,
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
        $accountId = $context->getAccountId() ?? 0;

        try {
            $unreadCount = (int) (new Query($this->db))
                ->from('{{%user_notification}}')
                ->where(['user_id' => $userId, 'is_read' => 0])
                ->count();

            $rows = (new Query($this->db))
                ->from('{{%user_notification}}')
                ->where(['user_id' => $userId])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(10)
                ->all();

            $items = [];
            foreach ($rows as $row) {
                $timeAgo = $this->formatTimeAgo((int) ($row['created_at'] ?? time()));
                $items[] = [
                    'id' => (int) $row['id'],
                    'type' => (string) ($row['type'] ?? 'info'),
                    'title' => (string) ($row['title'] ?? ''),
                    'message' => $row['message'] !== null ? (string) $row['message'] : null,
                    'action_url' => $row['action_url'] !== null ? (string) $row['action_url'] : null,
                    'is_read' => (bool) ($row['is_read'] ?? 0),
                    'created_at' => (int) ($row['created_at'] ?? time()),
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
