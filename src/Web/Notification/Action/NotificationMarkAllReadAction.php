<?php

declare(strict_types=1);

namespace App\Web\Notification\Action;

use App\Domain\Notification\NotificationRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Marks all notifications as read for current user or platform administrator.
 */
final class NotificationMarkAllReadAction
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepo,
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

        try {
            $this->notificationRepo->markAllAsRead($userId);

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
