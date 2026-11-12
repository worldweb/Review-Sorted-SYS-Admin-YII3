<?php

declare(strict_types=1);

namespace App\Web\QrCode\Action;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\QrCode\QrCodeRepositoryInterface;
use App\Domain\QrCode\Service\QrCodeServiceInterface;
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
 * QrUpdateAction.
 * Prepopulates and renders the 3-step Studio wizard for editing an existing QR code.
 * Replicates frontend\controllers\QrcodeController::actionUpdate in Yii 3.
 */
final class QrUpdateAction
{
    public function __construct(
        private QrCodeRepositoryInterface $qrCodeRepo,
        private AccountRepositoryInterface $accountRepo,
        private UserRepositoryInterface $userRepo,
        private QrCodeServiceInterface $qrCodeService,
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

        $flash = new Flash($this->session);

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
            $parsedBody = (array) ($request->getParsedBody() ?? []);
            $id = isset($parsedBody['id']) ? (int) $parsedBody['id'] : 0;
        }

        if ($id <= 0) {
            $flash->set('error', 'Invalid QR Code identifier.');
            return $this->redirect(\App\Environment::url('/qrcode/index'));
        }

        $qrCode = $this->qrCodeRepo->findById($id, $context);
        if ($qrCode === null) {
            $flash->set('error', 'The requested QR Code does not exist or you are not authorized to access it.');
            return $this->redirect(\App\Environment::url('/qrcode/index'));
        }

        if (strtoupper($request->getMethod()) === 'POST') {
            $parsedBody = (array) ($request->getParsedBody() ?? []);
            $url = trim((string) ($parsedBody['url'] ?? ''));
            $name = trim((string) ($parsedBody['name'] ?? ''));
            $templateName = trim((string) ($parsedBody['template_name'] ?? $parsedBody['template'] ?? 'default'));
            $logoType = trim((string) ($parsedBody['logo_type'] ?? 'default'));
            $defaultLogo = trim((string) ($parsedBody['default_logo_name'] ?? ''));
            $frameName = trim((string) ($parsedBody['frame_name'] ?? ''));
            $status = trim((string) ($parsedBody['status'] ?? 'active'));
            if ($status === '') {
                $status = 'active';
            }

            if ($url === '') {
                $flash->set('error', 'Please enter a valid destination URL.');
            } else {
                try {
                    $cleanName = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
                    if ($cleanName === '') {
                        $cleanName = 'qrcode-' . time();
                    }

                    $baseUrl = rtrim(\App\Environment::appUrl(), '/');
                    $encodedId = base64_encode((string) $id);
                    $encodedAhid = base64_encode((string) $currentUserId);
                    $trackingScanUrl = $baseUrl . '/qrcode/scan?url=' . urlencode($url) . '&qr=' . $encodedId . '&ahid=' . $encodedAhid;

                    $selectedLogo = ($logoType === 'default') ? $defaultLogo : null;
                    $generationResult = $this->qrCodeService->generate(
                        url: $trackingScanUrl,
                        name: $cleanName,
                        logo: $selectedLogo,
                        templateName: $templateName,
                        frameName: $frameName
                    );

                    $updateData = [
                        'url' => $url,
                        'name' => $cleanName,
                        'template_name' => $templateName,
                        'default_logo_name' => ($logoType === 'default' && $defaultLogo !== '') ? $defaultLogo : null,
                        'frame_name' => $frameName !== '' ? $frameName : null,
                        'logo_type' => $logoType,
                        'status' => $status,
                        'qrcode' => $generationResult['path'],
                    ];
                    $this->qrCodeRepo->save($updateData, $id, $context);

                    $flash->set('success', 'QR Code updated successfully.');
                    return $this->redirect(\App\Environment::url('/qrcode/view?id=') . $id);
                } catch (\Throwable $e) {
                    $flash->set('error', 'Failed to update QR code: ' . $e->getMessage());
                }
            }
        }

        return $this->viewRenderer
            ->render(__DIR__ . '/../views/create', [
                'model' => $qrCode,
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
