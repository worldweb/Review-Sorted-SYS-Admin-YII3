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

final class ChatViewAction
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

        $id = (int) ($request->getQueryParams()['id'] ?? 0);
        $chat = $this->chatRepo->findById($id, $context);
        if ($chat === null) {
            (new Flash($this->session))->set('error', 'Conversation thread not found.');
            return $this->redirect('/sys-yii3/chats');
        }

        if (!$chat->isRead()) {
            $this->chatRepo->markAsRead($id);
        }

        $flash = new Flash($this->session);

        if ($request->getMethod() === 'POST') {
            $body = (array) $request->getParsedBody();
            $replyText = trim((string) ($body['message'] ?? ''));

            if (!empty($replyText)) {
                $sent = $this->chatRepo->sendReply($id, $currentUser->getId(), $replyText, $context);
                if ($sent) {
                    $flash->set('success', 'Reply message sent.');
                    return $this->redirect('/sys-yii3/chats/view?id=' . $id);
                }
                $flash->set('error', 'Notice: Database is in read-only mode (rs_readonly). Reply was not written to disk.');
            }
        }

        // Re-fetch to get new messages if any
        $chat = $this->chatRepo->findById($id, $context);

        return $this->viewRenderer
            ->withViewPath('@src/Web/Chat/views')
            ->render('view', [
                'model' => $chat,
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
