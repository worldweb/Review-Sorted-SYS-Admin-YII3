<?php

declare(strict_types=1);

namespace App\Web\Chat\Action;

use App\Domain\Chat\ChatRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class ChatIndexAction
{
    public function __construct(
        private ChatRepositoryInterface $chatRepo,
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
            return $this->redirect('/sys-yii3/login');
        }

        $currentUser = $this->userRepo->findById($context->getUserId(), $context);
        if ($currentUser === null) {
            return $this->redirect('/sys-yii3/login');
        }

        $queryParams = $request->getQueryParams();
        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $pageSize = 20;
        $offset = ($page - 1) * $pageSize;

        $totalCount = $this->chatRepo->countChats($context);
        $chats = $this->chatRepo->findAllChats($context, $pageSize, $offset);
        $pageCount = max(1, (int) ceil($totalCount / $pageSize));

        $flash = new Flash($this->session);

        return $this->viewRenderer
            ->withViewPath('@src/Web/Chat/views')
            ->render('index', [
                'chats' => $chats,
                'totalCount' => $totalCount,
                'page' => $page,
                'pageCount' => $pageCount,
                'pageSize' => $pageSize,
                'currentUser' => $currentUser,
                'flash' => $flash,
                'csrfToken' => $this->csrfToken?->getValue() ?? '',
            ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}
