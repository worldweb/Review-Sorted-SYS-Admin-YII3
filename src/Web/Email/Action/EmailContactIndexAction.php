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
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * EmailContactIndexAction.
 * Lists account email contacts with pagination, search, and integrated CSV upload.
 * Replicates frontend\controllers\EmailImporterController::actionIndex in Yii 3.
 */
final class EmailContactIndexAction
{
    public function __construct(
        private EmailRepositoryInterface $emailRepo,
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
            return $this->redirect('/sys-yii3/login');
        }

        $currentUser = $this->userRepo->findById($context->getUserId(), $context);
        if ($currentUser === null) {
            return $this->redirect('/sys-yii3/login');
        }

        // Role authorization: Platform Admin and Account Admin only; Worker forbidden
        if (!$currentUser->isAdmin() && !$currentUser->isAccountAdmin()) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: You are not authorized to manage email contacts.');
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

        $contacts = $this->emailRepo->findContactsByAccount($context, $accountId, $page, $pageSize);
        $total = $this->emailRepo->countContactsByAccount($context, $accountId);

        return $this->viewRenderer
            ->render(__DIR__ . '/../views/importer', [
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
