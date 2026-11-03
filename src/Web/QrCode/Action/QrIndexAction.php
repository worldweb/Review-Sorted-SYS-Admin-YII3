<?php

declare(strict_types=1);

namespace App\Web\QrCode\Action;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\QrCode\QrCodeRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\Authorization\AccessDecisionManager;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * QrIndexAction.
 * Renders the paginated QR Code Studio listing grid with name filtering.
 * Replicates frontend\controllers\QrcodeController::actionIndex in Yii 3.
 */
final class QrIndexAction
{
    public function __construct(
        private QrCodeRepositoryInterface $qrCodeRepo,
        private AccountRepositoryInterface $accountRepo,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private AccessDecisionManager $accessDecision,
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

        $flash = new Flash($this->session);

        $currentUserId = $context->getUserId();
        $currentUser = $this->userRepo->findById($currentUserId, $context);
        if ($currentUser === null) {
            return $this->redirect('/sys-yii3/login');
        }

        // Feature Gating: account.qr_code == 1 verification
        if (!$currentUser->isAdmin()) {
            $accountId = $context->getAccountId() ?? ($currentUser->getAccountId() ?? $currentUser->getOwnerId());
            if ($accountId === null || $accountId <= 0) {
                $response = $this->responseFactory->createResponse(403);
                $response->getBody()->write('Forbidden: No tenant account is associated with your user.');
                return $response;
            }

            $account = $this->accountRepo->findById($accountId, $context);
            if ($account === null || !$account->isQrCodeEnabled()) {
                $response = $this->responseFactory->createResponse(403);
                $response->getBody()->write('Forbidden: QR Code Studio is not enabled for your account.');
                return $response;
            }
        } elseif ($context->getScopedAccountId() !== null) {
            $scopedAccountId = $context->getScopedAccountId();
            $account = $this->accountRepo->findById($scopedAccountId, $context);
            if ($account === null || !$account->isQrCodeEnabled()) {
                $response = $this->responseFactory->createResponse(403);
                $response->getBody()->write('Forbidden: QR Code Studio is not enabled for the selected account.');
                return $response;
            }
        }

        $queryParams = $request->getQueryParams();
        $search = isset($queryParams['search']) && trim((string) $queryParams['search']) !== ''
            ? trim((string) $queryParams['search'])
            : null;

        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $pageSize = 20;

        $searchResult = $this->qrCodeRepo->search($search, $page, $pageSize, $context);

        return $this->viewRenderer
            ->render(__DIR__ . '/../views/index', [
                'items' => $searchResult['items'],
                'total' => $searchResult['total'],
                'page' => $page,
                'pageSize' => $pageSize,
                'search' => $search,
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
