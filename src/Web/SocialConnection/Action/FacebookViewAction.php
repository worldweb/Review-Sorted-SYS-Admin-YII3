<?php

declare(strict_types=1);

namespace App\Web\SocialConnection\Action;

use App\Domain\SocialConnection\SocialConnectionRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class FacebookViewAction
{
    public function __construct(
        private SocialConnectionRepositoryInterface $connectionRepo,
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
        $config = $this->connectionRepo->findById($id, $context);
        if ($config === null || !$config->isFacebook()) {
            (new Flash($this->session))->set('error', 'Facebook connection not found.');
            return $this->redirect('/sys-yii3/settings/facebook');
        }

        return $this->viewRenderer
            ->withViewPath('@src/Web/SocialConnection/views')
            ->render('facebook/view', [
                'model' => $config,
            ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
