<?php

declare(strict_types=1);

namespace App\Web\QrCode\Action;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\QrCode\QrCodeRepositoryInterface;
use App\Domain\QrCode\QrScanRepositoryInterface;
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
 * QrViewAction.
 * Displays QR code configuration metadata, live preview, format download buttons,
 * and scan analytics KPIs (Total, Today, Last Scan).
 * Replicates frontend\controllers\QrcodeController::actionView in Yii 3.
 */
final class QrViewAction
{
    public function __construct(
        private QrCodeRepositoryInterface $qrCodeRepo,
        private QrScanRepositoryInterface $qrScanRepo,
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

        $flash = new Flash($this->session);

        $currentUserId = $context->getUserId();
        $currentUser = $this->userRepo->findById($currentUserId, $context);
        if ($currentUser === null) {
            return $this->redirect('/sys-yii3/login');
        }

        // Feature Gating: account.qr_code == 1
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
        $id = isset($queryParams['id']) ? (int) $queryParams['id'] : 0;
        if ($id <= 0) {
            $flash->set('error', 'Invalid QR Code identifier.');
            return $this->redirect('/sys-yii3/qrcode/index');
        }

        $qrCode = $this->qrCodeRepo->findById($id, $context);
        if ($qrCode === null) {
            $flash->set('error', 'The requested QR Code does not exist or you are not authorized to view it.');
            return $this->redirect('/sys-yii3/qrcode/index');
        }

        // Retrieve scan analytics strictly scoped through QR ownership
        $analytics = $this->qrScanRepo->getAnalyticsSummary($id, null, $context);

        return $this->viewRenderer
            ->render(__DIR__ . '/../views/view', [
                'model' => $qrCode,
                'analytics' => $analytics,
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
