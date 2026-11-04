<?php

declare(strict_types=1);

namespace App\Web\WhatsApp\Action;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\WhatsApp\WhatsAppRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * WhatsAppContactIndexAction.
 * Lists account WhatsApp phone contacts with pagination and integrated CSV upload.
 * Replicates frontend\controllers\WhatsappController::actionImporter in Yii 3.
 */
final class WhatsAppContactIndexAction
{
    public function __construct(
        private WhatsAppRepositoryInterface $whatsappRepo,
        private AccountRepositoryInterface $accountRepo,
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

        if (!$currentUser->isAdmin() && !$currentUser->isAccountAdmin()) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: You are not authorized to manage WhatsApp contacts.');
            return $response;
        }

        $flash = new Flash($this->session);
        $queryParams = $request->getQueryParams();

        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $pageSize = 25;

        $accountId = $context->getScopedAccountId() ?? $currentUser->getAccountId();
        if ($context->isUnrestricted() && isset($queryParams['account_id']) && (int) $queryParams['account_id'] > 0) {
            $accountId = (int) $queryParams['account_id'];
        }

        $contacts = $this->whatsappRepo->findContactsByAccount($context, $accountId, $page, $pageSize);
        $total = $this->whatsappRepo->countContactsByAccount($context, $accountId);

        return $this->viewRenderer
            ->withViewPath('@src/Web/WhatsApp/views')
            ->render('importer', [
                'contacts' => $contacts,
                'total' => $total,
                'page' => $page,
                'pageSize' => $pageSize,
                'accountId' => $accountId,
                'currentUser' => $currentUser,
                'context' => $context,
                'flash' => $flash,
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
