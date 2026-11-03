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
 * Marks single notification as read.
 */
final class NotificationMarkReadAction
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
            $response->getBody()->write((string) json_encode(['success' => false]));
            return $response;
        }

        $userId = $context->getUserId();
        $params = (array) ($request->getParsedBody() ?? []);
        $queryParams = $request->getQueryParams();
        $id = (int) ($params['id'] ?? $queryParams['id'] ?? 0);

        try {
            if ($id > 0) {
                $this->db->createCommand()
                    ->update('{{%user_notification}}', ['is_read' => 1, 'read_at' => time()], ['id' => $id, 'user_id' => $userId])
                    ->execute();
            }

            $unreadCount = (int) (new Query($this->db))
                ->from('{{%user_notification}}')
                ->where(['user_id' => $userId, 'is_read' => 0])
                ->count();

            $response->getBody()->write((string) json_encode([
                'success' => true,
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
