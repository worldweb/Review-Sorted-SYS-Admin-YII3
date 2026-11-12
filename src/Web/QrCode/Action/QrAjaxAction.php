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
use Psr\Http\Message\UploadedFileInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
use Yiisoft\Aliases\Aliases;

/**
 * QrAjaxAction.
 * Serves the dynamic live preview endpoint called asynchronously by the Studio wizard stepper.
 * Replicates frontend\controllers\QrcodeController::actionAjax in Yii 3.
 */
final class QrAjaxAction
{
    private LoggerInterface $logger;

    public function __construct(
        private QrCodeRepositoryInterface $qrCodeRepo,
        private AccountRepositoryInterface $accountRepo,
        private UserRepositoryInterface $userRepo,
        private QrCodeServiceInterface $qrCodeService,
        private TenantContextProviderInterface $tenantContextProvider,
        private ResponseFactoryInterface $responseFactory,
        private Aliases $aliases,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if (strtoupper($request->getMethod()) !== 'POST') {
            return $this->jsonResponse([
                'status' => 0,
                'message' => 'Invalid request method.',
                'html' => $this->errorHtml('Invalid request method. Only POST is accepted.'),
            ]);
        }

        $context = $this->tenantContextProvider->get();
        if ($context->isGuest()) {
            return $this->jsonResponse([
                'status' => 0,
                'message' => 'Authentication required.',
                'html' => $this->errorHtml('Authentication required. Please sign in.'),
            ]);
        }

        $currentUserId = $context->getUserId();
        $currentUser = $this->userRepo->findById($currentUserId, $context);
        if ($currentUser === null) {
            return $this->jsonResponse([
                'status' => 0,
                'message' => 'User not found.',
                'html' => $this->errorHtml('User profile could not be loaded.'),
            ]);
        }

        // Feature Gating: account.qr_code == 1
        if (!$currentUser->isAdmin()) {
            $accountId = $currentUser->getAccountId();
            if ($accountId === null || $accountId <= 0) {
                return $this->jsonResponse([
                    'status' => 0,
                    'message' => 'No account associated with user.',
                    'html' => $this->errorHtml('No client account is associated with your login.'),
                ]);
            }

            $account = $this->accountRepo->findById($accountId, $context);
            if ($account === null || !$account->isQrCodeEnabled()) {
                return $this->jsonResponse([
                    'status' => 0,
                    'message' => 'Feature not enabled.',
                    'html' => $this->errorHtml('QR Code Studio is not enabled for your account.'),
                ]);
            }
        }

        try {
            $parsedBody = (array) ($request->getParsedBody() ?? []);
            $queryParams = $request->getQueryParams();

            $id = isset($parsedBody['id']) && (int) $parsedBody['id'] > 0
                ? (int) $parsedBody['id']
                : (isset($queryParams['id']) ? (int) $queryParams['id'] : 0);

            // Verify existing QR code ownership if ID is provided
            if ($id > 0) {
                $existingQr = $this->qrCodeRepo->findById($id, $context);
                if ($existingQr === null) {
                    return $this->jsonResponse([
                        'status' => 0,
                        'message' => 'The requested QR Code does not exist or access is denied.',
                        'html' => $this->errorHtml('The requested QR Code does not exist or access is denied.'),
                    ]);
                }
            }

            $targetUrl = trim((string) ($parsedBody['url'] ?? ''));
            $frameName = trim((string) ($parsedBody['frame_name'] ?? ''));
            $defaultLogoName = trim((string) ($parsedBody['default_logo_name'] ?? ''));
            $templateName = trim((string) ($parsedBody['template_name'] ?? $parsedBody['template'] ?? 'default'));
            $qrName = trim((string) ($parsedBody['name'] ?? ''));
            $logoType = trim((string) ($parsedBody['logo_type'] ?? 'default'));
            $status = trim((string) ($parsedBody['status'] ?? 'active'));
            if ($status === '') {
                $status = 'active';
            }
            $isFinish = isset($parsedBody['finish']) && $parsedBody['finish'] !== '';

            // Handle custom uploaded logo file if present
            $uploadedFiles = $request->getUploadedFiles();
            $customLogoDiskPath = null;
            $savedCompanyLogoRelPath = null;

            if ($logoType === 'uploaded' && isset($uploadedFiles['upload_logo'])) {
                /** @var UploadedFileInterface $uploadedFile */
                $uploadedFile = $uploadedFiles['upload_logo'];
                if ($uploadedFile->getError() === UPLOAD_ERR_OK && $uploadedFile->getSize() > 0) {
                    $tempDir = sys_get_temp_dir();
                    $tempTarget = $tempDir . DIRECTORY_SEPARATOR . 'qr_upload_' . uniqid() . '.png';
                    $uploadedFile->moveTo($tempTarget);

                    $validation = $this->qrCodeService->validateLogo($tempTarget);
                    if (!$validation['valid']) {
                        @unlink($tempTarget);
                        return $this->jsonResponse([
                            'status' => 0,
                            'message' => $validation['error'] ?? 'Invalid logo uploaded.',
                            'html' => $this->errorHtml($validation['error'] ?? 'Uploaded logo validation failed.'),
                        ]);
                    }

                    if ($isFinish) {
                        $publicRoot = $this->aliases->get('@public');
                        $companyLogoDir = $publicRoot . '/assets/company_logo';
                        if (!is_dir($companyLogoDir)) {
                            @mkdir($companyLogoDir, 0775, true);
                        }
                        $clientFilename = preg_replace('/[^a-zA-Z0-9._-]/', '', (string) ($uploadedFile->getClientFilename() ?? 'logo.png'));
                        $finalLogoName = time() . '_' . $clientFilename;
                        $finalLogoPath = $companyLogoDir . DIRECTORY_SEPARATOR . $finalLogoName;
                        @copy($tempTarget, $finalLogoPath);
                        $savedCompanyLogoRelPath = 'assets/company_logo/' . $finalLogoName;
                    }

                    $customLogoDiskPath = $tempTarget;
                }
            }

            $selectedLogo = ($logoType === 'uploaded') ? $customLogoDiskPath : $defaultLogoName;

            $baseUrl = rtrim(\App\Environment::appUrl(), '/');
            $qrcodename = strtolower($qrName);
            $cleanName = preg_replace('/[^a-z0-9]+/', '-', $qrcodename);
            if ($cleanName === '') {
                $cleanName = 'qrcode-' . time();
            }

            if ($isFinish) {
                if ($targetUrl === '') {
                    return $this->jsonResponse([
                        'status' => 0,
                        'message' => 'Please enter a valid destination URL.',
                        'html' => $this->errorHtml('Please enter a valid destination URL before finishing.'),
                    ]);
                }

                $effectiveSaveId = $id;

                // Step A: In create mode, insert initial record to obtain primary key ID
                if ($effectiveSaveId <= 0) {
                    $insertData = [
                        'user_id' => (string) $currentUserId,
                        'name' => $cleanName,
                        'url' => $targetUrl,
                        'template_name' => $templateName,
                        'default_logo_name' => ($logoType === 'default' && $defaultLogoName !== '') ? $defaultLogoName : null,
                        'frame_name' => $frameName !== '' ? $frameName : null,
                        'logo_type' => $logoType,
                        'upload_logo' => $savedCompanyLogoRelPath,
                        'total_scan' => '0',
                        'status' => $status,
                        'qrcode' => null,
                    ];
                    $effectiveSaveId = $this->qrCodeRepo->save($insertData, null, $context);
                }

                // Step B: Build redirect tracking scan URL replicating legacy /qrcode/scan endpoint
                $encodedId = base64_encode((string) $effectiveSaveId);
                $encodedAhid = base64_encode((string) $currentUserId);
                $trackingScanUrl = $baseUrl . '/qrcode/scan?url=' . urlencode($targetUrl) . '&qr=' . $encodedId . '&ahid=' . $encodedAhid;

                // Step C: Generate QR code image encoding the tracking URL
                $generationResult = $this->qrCodeService->generate(
                    url: $trackingScanUrl,
                    name: $cleanName,
                    logo: $selectedLogo,
                    templateName: $templateName,
                    frameName: $frameName
                );

                // Step D: Update database record with final generated image path
                $saveData = [
                    'url' => $targetUrl,
                    'name' => $cleanName,
                    'template_name' => $templateName,
                    'default_logo_name' => ($logoType === 'default' && $defaultLogoName !== '') ? $defaultLogoName : null,
                    'frame_name' => $frameName !== '' ? $frameName : null,
                    'logo_type' => $logoType,
                    'status' => $status,
                    'qrcode' => $generationResult['path'],
                ];
                if ($savedCompanyLogoRelPath !== null) {
                    $saveData['upload_logo'] = $savedCompanyLogoRelPath;
                }

                $this->qrCodeRepo->save($saveData, $effectiveSaveId, $context);
                $savedId = $effectiveSaveId;

            } else {
                // Live preview generation (ephemeral, not written to database)
                $previewUrl = $targetUrl !== '' ? $targetUrl : 'https://example.com';
                $generationResult = $this->qrCodeService->generate(
                    url: $previewUrl,
                    name: $cleanName,
                    logo: $selectedLogo,
                    templateName: $templateName,
                    frameName: $frameName
                );
                $savedId = $id;
            }

            // Clean up temporary uploaded candidate logo
            if ($customLogoDiskPath !== null && file_exists($customLogoDiskPath)) {
                @unlink($customLogoDiskPath);
            }

            $webImageUrl = $baseUrl . '/' . ltrim($generationResult['path'], '/');
            $effectiveFrame = (string) ($generationResult['effectiveFrame'] ?? $frameName);

            // Render live preview HTML replicating legacy structure
            $previewHtml = '';
            if ($isFinish) {
                $indexUrl = $baseUrl . '/qrcode/index';
                $viewUrl = $baseUrl . '/qrcode/view?id=' . $savedId;
                $previewHtml .= '<div class="rs-qr-success-header">';
                $previewHtml .= '<a href="' . htmlspecialchars($indexUrl, ENT_QUOTES, 'UTF-8') . '" class="rs-btn rs-btn-secondary rs-qr-go-back-btn" id="rs-qr-go-back-btn"><i class="fa-solid fa-arrow-left"></i> Go Back</a> ';
                $previewHtml .= '<a href="' . htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8') . '" class="rs-btn rs-btn-primary" style="margin-left: 8px;"><i class="fa-solid fa-eye"></i> View QR Code</a>';
                $previewHtml .= '<h2 class="rs-qr-success-title">Congratulations ! <i class="fa-solid fa-circle-check rs-qr-success-icon"></i></h2>';
                $previewHtml .= '<p class="rs-qr-success-desc">Point your phone camera towards the QR Code to test it now.</p>';
                $previewHtml .= '</div>';
            }

            $previewHtml .= '<div class="site-gen-qr-code-design-wrap rs-qr-generated-wrapper">';
            $previewHtml .= '<div class="site-gen-qr-code-design ' . htmlspecialchars($effectiveFrame, ENT_QUOTES, 'UTF-8') . '">';
            $previewHtml .= '<img class="qr-code-image" src="' . htmlspecialchars($webImageUrl, ENT_QUOTES, 'UTF-8') . '" alt="Generated QR Code">';
            $previewHtml .= '</div>';
            $previewHtml .= '</div>';

            return $this->jsonResponse([
                'status' => 1,
                'id' => $savedId,
                'redirect' => $baseUrl . '/qrcode/index',
                'viewUrl' => $baseUrl . '/qrcode/view?id=' . $savedId,
                'html' => $previewHtml,
                'path' => $generationResult['path'],
                'fileName' => $generationResult['fileName'],
            ]);

        } catch (Throwable $e) {
            $this->logger->error('QR Code AJAX preview generation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $userMessage = 'Unable to generate the QR code preview. Please verify your inputs and try again.';
            return $this->jsonResponse([
                'status' => 0,
                'message' => $userMessage,
                'html' => $this->errorHtml($userMessage),
            ]);
        }
    }

    private function errorHtml(string $message): string
    {
        return '<div class="alert alert-danger rs-qr-error-alert" style="margin: 20px 0; padding: 15px; border-radius: 8px; background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; text-align: center;">'
            . htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
            . '</div>';
    }

    private function jsonResponse(array $data): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(200);
        $response->getBody()->write((string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json; charset=UTF-8');
    }
}
