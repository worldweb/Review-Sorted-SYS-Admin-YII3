<?php

declare(strict_types=1);

namespace App\Web\SocialConnection\Action;

use App\Domain\Notification\NotificationService;
use App\Domain\SocialConnection\SocialConnectionRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;

final class GoogleRevokeAction
{
    public function __construct(
        private SocialConnectionRepositoryInterface $connectionRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private SessionInterface $session,
        private ResponseFactoryInterface $responseFactory,
        private ?NotificationService $notificationService = null,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $this->tenantContextProvider->get();
        if ($context->isGuest()) {
            return $this->redirect(\App\Environment::url('/login'));
        }

        $id = (int) ($request->getQueryParams()['id'] ?? 0);
        $flash = new Flash($this->session);

        // Pre-fetch connection to determine account and location name before unlinking
        $config = $id > 0 ? $this->connectionRepo->findById($id, $context) : null;
        $accountId = $config?->getAccountId() ?? ($context->getAccountId() ?? 0);
        $displayName = $config?->getDisplayName() ?? '';

        $this->connectionRepo->unlinkConfig($id, $context);
        $flash->set('info', 'Google location connection has been unlinked.');

        if ($this->notificationService !== null && $accountId > 0) {
            $identifier = $displayName !== ''
                ? "{$displayName} (#{$id})"
                : ($id > 0 ? "ID: {$id}" : '');
            $title = $identifier !== ''
                ? "Google Connection Disconnected ({$identifier})"
                : 'Google Connection Disconnected';
            $message = $identifier !== ''
                ? "Google location \"{$identifier}\" has been unlinked."
                : 'Your Google Business Profile connection has been unlinked.';

            $this->notificationService->notifyAccountAdmins(
                $accountId,
                'connection',
                $title,
                $message,
                \App\Environment::url('/settings/google'),
                120
            );
        }

        return $this->redirect(\App\Environment::url('/settings/google'));
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
