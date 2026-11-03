<?php

declare(strict_types=1);

namespace App\Web\QrCode\Action;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\QrCode\QrCodeRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContext;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * QrScanAction.
 * Public redirect and scan tracking endpoint accessed by physical QR code scanners.
 * Replicates frontend\controllers\QrcodeController::actionScan in Yii 3.
 *
 * Route: /qrcode/scan?url=...&qr=...&ahid=...
 */
final class QrScanAction
{
    private LoggerInterface $logger;

    public function __construct(
        private QrCodeRepositoryInterface $qrCodeRepo,
        private AccountRepositoryInterface $accountRepo,
        private UserRepositoryInterface $userRepo,
        private ResponseFactoryInterface $responseFactory,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        $rawQr = (string) ($queryParams['qr'] ?? '');
        $rawAhid = (string) ($queryParams['ahid'] ?? '');

        $decodedQr = base64_decode($rawQr, true);
        $decodedAhid = base64_decode($rawAhid, true);

        $qrId = (is_string($decodedQr) && is_numeric($decodedQr)) ? (int) $decodedQr : 0;
        $accHolderId = (is_string($decodedAhid) && is_numeric($decodedAhid)) ? (int) $decodedAhid : 0;

        if ($qrId <= 0 || $accHolderId <= 0) {
            return $this->textResponse('The QR code account holder are not verified. Please contact our website administrator.');
        }

        // Validate account holder user entity
        $user = $this->userRepo->findById($accHolderId, TenantContext::unrestricted());
        if ($user === null) {
            return $this->textResponse('The QR code account holder are not verified. Please contact our website administrator.');
        }

        $userAccountId = $user->getAccountId();
        if ($userAccountId === null || $userAccountId <= 0) {
            return $this->textResponse('The QR code account holder are not verified. Please contact our website administrator.');
        }

        // Validate account entity and qr_code feature entitlement flag
        $account = $this->accountRepo->findById($userAccountId, TenantContext::unrestricted());
        if ($account === null || !$account->isQrCodeEnabled()) {
            return $this->textResponse('The QR code account holder are not verified. Please contact our website administrator.');
        }

        // Validate QR Code record exists
        $qrCode = $this->qrCodeRepo->findById($qrId, TenantContext::unrestricted());
        if ($qrCode === null) {
            return $this->textResponse('The QR code is invalid. Please contact our website administrator.');
        }

        // Validate that QR Code belongs to the account holder or the same tenant account
        $qrAccountId = $qrCode->getAccountId();
        if ($qrCode->getUserId() !== $user->getId() && ($qrAccountId === null || $qrAccountId !== $userAccountId)) {
            return $this->textResponse('The QR code account holder are not verified. Please contact our website administrator.');
        }

        // Validate QR Code status
        if (!$qrCode->isActive()) {
            return $this->textResponse('The QR code is expired. Please contact our website administrator.');
        }

        // Validate destination URL recorded on the QR model (never trust raw request param alone)
        $destinationUrl = trim((string) $qrCode->getUrl());
        if ($destinationUrl === '') {
            return $this->textResponse('The QR code destination is invalid. Please contact our website administrator.');
        }

        // Basic URL protocol safety validation (must begin with http:// or https://)
        if (!preg_match('~^https?://~i', $destinationUrl)) {
            return $this->textResponse('The QR code destination is invalid. Please contact our website administrator.');
        }

        // Extract client IP address for diagnostic audit
        $serverParams = $request->getServerParams();
        $userIp = $serverParams['HTTP_X_FORWARDED_FOR']
            ?? $serverParams['HTTP_CLIENT_IP']
            ?? $serverParams['REMOTE_ADDR']
            ?? '127.0.0.1';

        // Scan tracking boundary: database connection is read-only (rs_readonly)
        // Log the scan event safely; physical database write is deferred pending write-capable permissions
        $this->logger->info(sprintf(
            'QR Code scan recorded: QR #%d by IP %s. Target URL: %s (DB write deferred in read-only mode).',
            $qrId,
            $userIp,
            $destinationUrl
        ));

        // Execute legacy 302 redirect to stored destination URL
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $destinationUrl);
    }

    private function textResponse(string $message): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(200);
        $response->getBody()->write($message);
        return $response->withHeader('Content-Type', 'text/plain; charset=UTF-8');
    }
}
