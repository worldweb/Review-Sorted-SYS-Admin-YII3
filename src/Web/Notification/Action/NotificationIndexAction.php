<?php

declare(strict_types=1);

namespace App\Web\Notification\Action;

use App\Domain\Notification\NotificationRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Dedicated Standalone Notification History Action (/notifications).
 * Displays paginated notification feed with type and status filtering.
 */
final class NotificationIndexAction
{
    private const DEFAULT_PAGE_SIZE = 20;

    public function __construct(
        private NotificationRepositoryInterface $notificationRepo,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
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

        $userId = $context->getUserId();
        $currentUser = $this->userRepo->findById($userId, $context);
        if ($currentUser === null) {
            return $this->redirect(\App\Environment::url('/login'));
        }

        $queryParams = $request->getQueryParams();
        $status = isset($queryParams['status']) && is_string($queryParams['status']) ? trim($queryParams['status']) : 'all';
        $type = isset($queryParams['type']) && is_string($queryParams['type']) ? trim($queryParams['type']) : 'all';
        $page = isset($queryParams['page']) && is_numeric($queryParams['page']) ? max(1, (int) $queryParams['page']) : 1;

        // Normalise status
        if (!in_array($status, ['all', 'unread', 'read'], true)) {
            $status = 'all';
        }

        // Fetch history and counts safely
        try {
            $history = $this->notificationRepo->findHistoryForUser(
                $currentUser,
                $status,
                $type !== 'all' ? $type : null,
                $page,
                self::DEFAULT_PAGE_SIZE
            );
            $unreadCount = $this->notificationRepo->countUnreadForUser($currentUser);
        } catch (\Throwable $e) {
            $history = [
                'items' => [],
                'total' => 0,
                'page' => 1,
                'pageSize' => self::DEFAULT_PAGE_SIZE,
                'totalPages' => 1,
            ];
            $unreadCount = 0;
        }

        return $this->viewRenderer
            ->render(__DIR__ . '/../views/index', [
                'notifications' => $history['items'],
                'total' => $history['total'],
                'page' => $history['page'],
                'pageSize' => $history['pageSize'],
                'totalPages' => $history['totalPages'],
                'unreadCount' => $unreadCount,
                'currentStatus' => $status,
                'currentType' => $type,
                'currentUser' => $currentUser,
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
