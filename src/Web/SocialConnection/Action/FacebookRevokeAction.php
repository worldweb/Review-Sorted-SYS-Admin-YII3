<?php

declare(strict_types=1);

namespace App\Web\SocialConnection\Action;

use App\Domain\SocialConnection\SocialConnectionRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;

final class FacebookRevokeAction
{
    public function __construct(
        private SocialConnectionRepositoryInterface $connectionRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private SessionInterface $session,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $this->tenantContextProvider->get();
        if ($context->isGuest()) {
            return $this->redirect('/sys-yii3/login');
        }

        $id = (int) ($request->getQueryParams()['id'] ?? 0);
        $flash = new Flash($this->session);

        $this->connectionRepo->unlinkConfig($id, $context);
        $flash->set('info', 'Facebook connection has been unlinked.');

        return $this->redirect('/sys-yii3/settings/facebook');
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
