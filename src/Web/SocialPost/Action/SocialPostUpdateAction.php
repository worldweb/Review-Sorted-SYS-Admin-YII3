<?php

declare(strict_types=1);

namespace App\Web\SocialPost\Action;

use App\Domain\SocialPost\SocialPostRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class SocialPostUpdateAction
{
    public function __construct(
        private SocialPostRepositoryInterface $postRepo,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private SessionInterface $session,
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

        $id = (int) ($request->getQueryParams()['id'] ?? 0);
        $post = $this->postRepo->findById($id, $context);
        if ($post === null) {
            (new Flash($this->session))->set('error', 'Post not found.');
            return $this->redirect(\App\Environment::url('/social-post'));
        }

        $flash = new Flash($this->session);

        if ($request->getMethod() === 'POST') {
            $parsedBody = (array) $request->getParsedBody();
            $content = trim((string) ($parsedBody['content'] ?? ''));
            $scheduledAt = trim((string) ($parsedBody['scheduled_at'] ?? ''));

            $updated = $this->postRepo->updatePost($id, [
                'content' => $content,
                'scheduled_at' => !empty($scheduledAt) ? $scheduledAt : null,
            ], $context);

            if ($updated) {
                $flash->set('success', 'Post updated successfully.');
                return $this->redirect(\App\Environment::url('/social-post/view?id=') . $id);
            }

            $flash->set('error', 'Unable to update post (database is currently read-only).');
        }

        return $this->viewRenderer
            ->withViewPath('@src/Web/SocialPost/views')
            ->render('update', [
                'model' => $post,
                'flash' => $flash,
                'csrfToken' => $this->csrfToken?->getValue() ?? '',
            ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
