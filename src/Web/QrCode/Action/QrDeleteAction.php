<?php

declare(strict_types=1);

namespace App\Web\QrCode\Action;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\QrCode\QrCodeRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;

/**
 * QrDeleteAction.
 * Handles QR Code deletion requests.
 * Replicates frontend\controllers\QrcodeController::actionDelete in Yii 3.
 * Strictly checks POST method and ownership; defers physical database deletion in read-only mode.
 */
final class QrDeleteAction
{
    public function __construct(
        private QrCodeRepositoryInterface $qrCodeRepo,
        private AccountRepositoryInterface $accountRepo,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private SessionInterface $session,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $this->tenantContextProvider->get();
        if ($context->isGuest()) {
            return $this->redirect(\App\Environment::url('/login'));
        }

        $flash = new Flash($this->session);

        if (strtoupper($request->getMethod()) !== 'POST') {
            $flash->set('error', 'Method not allowed for QR Code deletion.');
            return $this->redirect(\App\Environment::url('/qrcode/index'));
        }

        $currentUserId = $context->getUserId();
        $currentUser = $this->userRepo->findById($currentUserId, $context);
        if ($currentUser === null) {
            return $this->redirect(\App\Environment::url('/login'));
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
        }

        $queryParams = $request->getQueryParams();
        $id = isset($queryParams['id']) ? (int) $queryParams['id'] : 0;
        if ($id <= 0) {
            $parsedBody = (array) ($request->getParsedBody() ?? []);
            $id = isset($parsedBody['id']) ? (int) $parsedBody['id'] : 0;
        }

        if ($id <= 0) {
            $flash->set('error', 'Invalid QR Code identifier.');
            return $this->redirect(\App\Environment::url('/qrcode/index'));
        }

        $qrCode = $this->qrCodeRepo->findById($id, $context);
        if ($qrCode === null) {
            $flash->set('error', 'The requested QR Code does not exist or you are not authorized to delete it.');
            return $this->redirect(\App\Environment::url('/qrcode/index'));
        }

        $deleted = $this->qrCodeRepo->delete($id, $context);
        if ($deleted) {
            $flash->set('success', 'QR Code deleted successfully.');
        } else {
            $flash->set('error', 'Failed to delete the QR Code. Please try again.');
        }

        return $this->redirect(\App\Environment::url('/qrcode/index'));
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
