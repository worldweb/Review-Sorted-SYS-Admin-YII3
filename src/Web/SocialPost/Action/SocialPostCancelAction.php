<?php

declare(strict_types=1);

namespace App\Web\SocialPost\Action;

use App\Domain\SocialPost\SocialPostRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;

final class SocialPostCancelAction
{
    public function __construct(
        private SocialPostRepositoryInterface $postRepo,
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

        $this->postRepo->cancelPost($id, $context);
        $flash->set('info', 'Scheduled post cancelled.');

        return $this->redirect('/sys-yii3/social-post');
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
