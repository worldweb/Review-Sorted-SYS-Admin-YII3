<?php

declare(strict_types=1);

namespace App\Web\SocialConnection\Action;

use App\Domain\SocialConnection\SocialConnectionRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class FacebookIndexAction
{
    public function __construct(
        private SocialConnectionRepositoryInterface $connectionRepo,
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

        $currentUser = $this->userRepo->findById($context->getUserId(), $context);
        if ($currentUser === null) {
            return $this->redirect(\App\Environment::url('/login'));
        }

        $queryParams = $request->getQueryParams();
        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $pageSize = 20;
        $offset = ($page - 1) * $pageSize;

        $totalCount = $this->connectionRepo->countFacebookConfigs($context);
        $configs = $this->connectionRepo->findFacebookConfigs($context, $pageSize, $offset);
        $pageCount = max(1, (int) ceil($totalCount / $pageSize));

        $apis = $this->connectionRepo->findApisByService(\App\Domain\SocialConnection\Api::SERVICE_FACEBOOK);
        $api = $apis[0] ?? null;

        $flash = new Flash($this->session);

        return $this->viewRenderer
            ->withViewPath('@src/Web/SocialConnection/views')
            ->render('facebook/index', [
                'configs' => $configs,
                'totalCount' => $totalCount,
                'page' => $page,
                'pageCount' => $pageCount,
                'pageSize' => $pageSize,
                'currentUser' => $currentUser,
                'api' => $api,
                'flash' => $flash,
                'csrfToken' => $this->csrfToken?->getValue() ?? '',
            ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
