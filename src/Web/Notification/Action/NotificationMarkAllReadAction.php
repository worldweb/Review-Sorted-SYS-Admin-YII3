<?php

declare(strict_types=1);

namespace App\Web\Notification\Action;

use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * Marks all notifications as read for current user.
 */
final class NotificationMarkAllReadAction
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

        try {
            $this->db->createCommand()
                ->update('{{%user_notification}}', ['is_read' => 1, 'read_at' => time()], ['user_id' => $userId, 'is_read' => 0])
                ->execute();

            $response->getBody()->write((string) json_encode([
                'success' => true,
                'unread_count' => 0,
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
