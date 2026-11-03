<?php

declare(strict_types=1);

namespace App\Web\Email\Action;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\Email\EmailRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * EmailTemplateIndexAction.
 * Lists available system and custom email templates for the tenant account.
 * Replicates frontend\controllers\EmailController::actionIndex in Yii 3.
 */
final class EmailTemplateIndexAction
{
    public function __construct(
        private EmailRepositoryInterface $emailRepo,
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
        if ($currentUser === null) {
            return $this->redirect('/sys-yii3/login');
        }

        // Workers are forbidden from accessing email management
        if (!$currentUser->isAdmin() && !$currentUser->isAccountAdmin()) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: You are not authorized to manage email templates.');
            return $response;
        }

        $flash = new Flash($this->session);
        $queryParams = $request->getQueryParams();

        $accountId = $context->getScopedAccountId() ?? ($context->getAccountId() ?? $currentUser->getAccountId());
        if ($context->isUnrestricted() && isset($queryParams['account_id']) && (int) $queryParams['account_id'] > 0) {
            $accountId = (int) $queryParams['account_id'];
        }

        $templates = $this->emailRepo->findTemplatesByAccount($context, $accountId);

        // Optional search filtering by template name or slug
        $search = trim((string) ($queryParams['q'] ?? ''));
        if ($search !== '') {
            $templates = array_filter(
                $templates,
                static fn ($t): bool => stripos($t->getName(), $search) !== false || stripos($t->getSlug(), $search) !== false
            );
        }

        return $this->viewRenderer
            ->withViewPath('@src/Web/Email/views')
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
