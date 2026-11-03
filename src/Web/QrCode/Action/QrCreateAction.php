<?php

declare(strict_types=1);

namespace App\Web\QrCode\Action;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\QrCode\Service\QrCodeServiceInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * QrCreateAction.
 * Prepares and renders the 3-step interactive QR Code Studio wizard.
 * Replicates frontend\controllers\QrcodeController::actionCreate in Yii 3.
 */
final class QrCreateAction
{
    public function __construct(
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

        if (strtoupper($request->getMethod()) === 'POST') {
            $parsedBody = (array) ($request->getParsedBody() ?? []);
            $url = trim((string) ($parsedBody['url'] ?? ''));
            $name = trim((string) ($parsedBody['name'] ?? ''));
            $templateName = trim((string) ($parsedBody['template_name'] ?? $parsedBody['template'] ?? 'default'));
            $logoType = trim((string) ($parsedBody['logo_type'] ?? 'default'));
            $defaultLogo = trim((string) ($parsedBody['default_logo_name'] ?? ''));
            $frameName = trim((string) ($parsedBody['frame_name'] ?? ''));

            if ($url === '') {
                $flash->set('error', 'Please enter a valid destination URL.');
            } else {
                try {
                    $selectedLogo = ($logoType === 'default') ? $defaultLogo : null;
                    $this->qrCodeService->generate(
                        url: $url,
                        name: $name,
                        logo: $selectedLogo,
                        templateName: $templateName,
                        frameName: $frameName
                    );

                    // Notice of read-only database state: write operations require write-capable connection
                    $flash->set('warning', 'QR Code preview generated. Saving new records requires write permissions, which are deferred in read-only mode.');
                    return $this->redirect('/sys-yii3/qrcode/index');
                } catch (Throwable $e) {
                    $flash->set('error', 'Failed to generate QR code: ' . $e->getMessage());
                }
            }
        }

        return $this->viewRenderer
            ->render(__DIR__ . '/../views/create', [
                'model' => null,
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
