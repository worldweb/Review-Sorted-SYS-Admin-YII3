<?php

declare(strict_types=1);

namespace App\Web\SocialPost\Action;

use App\Domain\SocialPost\SocialPostRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class SocialPostViewAction
{
    public function __construct(
        private SocialPostRepositoryInterface $postRepo,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private SessionInterface $session,
        private WebViewRenderer $viewRenderer,
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
        $post = $this->postRepo->findById($id, $context);
        if ($post === null) {
            (new Flash($this->session))->set('error', 'Post not found.');
            return $this->redirect('/sys-yii3/social-post');
        }

        $flash = new Flash($this->session);

        return $this->viewRenderer
            ->withViewPath('@src/Web/SocialPost/views')
            ->render('view', [
                'model' => $post,
                'flash' => $flash,
            ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
