<?php

declare(strict_types=1);

namespace App\Web\QrCode\Action;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\QrCode\QrCodeRepositoryInterface;
use App\Domain\QrCode\Service\QrCodeServiceInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use HttpSoft\Message\Stream;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Yiisoft\Aliases\Aliases;

/**
 * QrDownloadAction.
 * Serves binary download streams for generated QR codes in PNG and JPG formats.
 * Replicates frontend\controllers\QrcodeController::actionDownload in Yii 3.
 */
final class QrDownloadAction
{
    private const ALLOWED_FORMATS = ['png', 'jpg'];

    public function __construct(
        private QrCodeRepositoryInterface $qrCodeRepo,
        private AccountRepositoryInterface $accountRepo,
        private UserRepositoryInterface $userRepo,
        private QrCodeServiceInterface $qrCodeService,
        private TenantContextProviderInterface $tenantContextProvider,
        private ResponseFactoryInterface $responseFactory,
        private Aliases $aliases,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $this->tenantContextProvider->get();
        if ($context->isGuest()) {
            return $this->errorResponse(401, 'Unauthorized: Please sign in to download QR codes.');
        }

        $currentUserId = $context->getUserId();
        $currentUser = $this->userRepo->findById($currentUserId, $context);
        if ($currentUser === null) {
            return $this->errorResponse(401, 'Unauthorized: User not found.');
        }

        // Feature Gating: account.qr_code == 1
        if (!$currentUser->isAdmin()) {
            $accountId = $currentUser->getAccountId();
            if ($accountId === null || $accountId <= 0) {
                return $this->errorResponse(403, 'Forbidden: No account associated with user.');
            }

            $account = $this->accountRepo->findById($accountId, $context);
            if ($account === null || !$account->isQrCodeEnabled()) {
                return $this->errorResponse(403, 'Forbidden: QR Code Studio is not enabled for your account.');
            }
        }

        $queryParams = $request->getQueryParams();
        $id = isset($queryParams['id']) ? (int) $queryParams['id'] : 0;
        $format = strtolower(trim((string) ($queryParams['format'] ?? 'png')));

        if ($id <= 0) {
            return $this->errorResponse(400, 'Bad Request: Invalid QR Code identifier.');
        }

        if (!in_array($format, self::ALLOWED_FORMATS, true)) {
            if ($format === 'pdf') {
                return $this->errorResponse(400, 'Bad Request: PDF generation is currently deferred.');
            }
            return $this->errorResponse(400, 'Bad Request: Unsupported download format requested. Supported formats: PNG, JPG.');
        }

        $qrCode = $this->qrCodeRepo->findById($id, $context);
        if ($qrCode === null) {
            return $this->errorResponse(404, 'Not Found: The requested QR Code does not exist or access is denied.');
        }

        $storedRelativePath = $qrCode->getQrcode();
        if ($storedRelativePath === null || trim($storedRelativePath) === '') {
            return $this->errorResponse(404, 'Not Found: No generated image file path recorded for this QR Code.');
        }

        // Resolve absolute file path safely to prevent path traversal
        $publicRoot = $this->aliases->get('@public');
        $resolvedDiskPath = $this->resolveSafeDiskPath($storedRelativePath, $publicRoot);

        if ($resolvedDiskPath === null || !file_exists($resolvedDiskPath) || !is_readable($resolvedDiskPath)) {
            return $this->errorResponse(404, 'Not Found: The QR Code image file does not exist on disk.');
        }

        $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $qrCode->getName() ?: 'qrcode');
        if (empty($cleanName)) {
            $cleanName = 'qrcode_' . $qrCode->getId();
        }

        try {
            if ($format === 'png') {
                $handle = fopen($resolvedDiskPath, 'rb');
                if ($handle === false) {
                    return $this->errorResponse(500, 'Internal Server Error: Unable to open image stream.');
                }

                $stream = new Stream($handle);
                $filename = $cleanName . '.png';

                return $this->responseFactory->createResponse(200)
                    ->withHeader('Content-Type', 'image/png')
                    ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                    ->withHeader('Content-Length', (string) filesize($resolvedDiskPath))
                    ->withHeader('Pragma', 'no-cache')
                    ->withHeader('Expires', '0')
                    ->withBody($stream);
            }

            if ($format === 'jpg') {
                // Convert to JPEG with white background using image processor
                $tempJpg = $this->qrCodeService->convertToJpg($resolvedDiskPath);
                $jpgBytes = file_get_contents($tempJpg);
                @unlink($tempJpg);

                if ($jpgBytes === false) {
                    return $this->errorResponse(500, 'Internal Server Error: Failed to process JPEG conversion.');
                }

                $stream = new Stream(fopen('php://temp', 'r+'));
                $stream->write($jpgBytes);
                $stream->rewind();

                $filename = $cleanName . '.jpg';

                return $this->responseFactory->createResponse(200)
                    ->withHeader('Content-Type', 'image/jpeg')
                    ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                    ->withHeader('Content-Length', (string) strlen($jpgBytes))
                    ->withHeader('Pragma', 'no-cache')
                    ->withHeader('Expires', '0')
                    ->withBody($stream);
            }

        } catch (Throwable $e) {
            return $this->errorResponse(500, 'Internal Server Error: Failed to generate file download.');
        }

        return $this->errorResponse(400, 'Bad Request: Unable to fulfill download request.');
    }

    private function resolveSafeDiskPath(string $storedPath, string $baseDir): ?string
    {
        $candidate = $baseDir . DIRECTORY_SEPARATOR . ltrim($storedPath, '/\\');
        $realPath = realpath($candidate);

        if ($realPath === false) {
            return null;
        }

        return $realPath;
    }

    private function errorResponse(int $statusCode, string $message): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($statusCode);
        $response->getBody()->write($message);
        return $response->withHeader('Content-Type', 'text/plain; charset=UTF-8');
    }
}
