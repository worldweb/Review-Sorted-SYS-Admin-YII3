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
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * WhatsAppTemplateIndexAction.
 * Lists available system and custom WhatsApp templates for the tenant account.
 * Replicates frontend\controllers\WhatsappController::actionIndex in Yii 3.
 */
final class WhatsAppTemplateIndexAction
{
    public function __construct(
        private WhatsAppRepositoryInterface $whatsappRepo,
        private AccountRepositoryInterface $accountRepo,
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

        $currentUser = $this->userRepo->findById($context->getUserId(), $context);
        if ($currentUser === null || (!$currentUser->isAdmin() && !$currentUser->isAccountAdmin())) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: You are not authorized to manage WhatsApp templates.');
            return $response;
        }

        $flash = new Flash($this->session);
        $queryParams = $request->getQueryParams();

        $accountId = $context->getScopedAccountId() ?? $currentUser->getAccountId();
        if ($context->isUnrestricted() && isset($queryParams['account_id']) && (int) $queryParams['account_id'] > 0) {
            $accountId = (int) $queryParams['account_id'];
        }

        $templates = $this->whatsappRepo->findTemplatesByAccount($context, $accountId);

        $search = trim((string) ($queryParams['q'] ?? ''));
        if ($search !== '') {
            $templates = array_filter(
                $templates,
                static fn ($t): bool => stripos($t->getName(), $search) !== false || stripos($t->getSlug(), $search) !== false
            );
        }

        return $this->viewRenderer
            ->withViewPath('@src/Web/WhatsApp/views')
            ->render('template/index', [
                'templates' => $templates,
                'search' => $search,
                'accountId' => $accountId,
                'currentUser' => $currentUser,
                'context' => $context,
                'flash' => $flash,
            ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
