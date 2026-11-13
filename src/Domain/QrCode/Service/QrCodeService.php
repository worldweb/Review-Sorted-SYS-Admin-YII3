<?php

declare(strict_types=1);

namespace App\Domain\QrCode\Service;

use App\Domain\Setting\SettingRepositoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Yiisoft\Aliases\Aliases;

/**
 * QR Code Studio Service.
 * Coordinates QRCodeMonkey remote generation, template resolution, GD frame merging,
 * URL sanitization, and output format conversions.
 */
final class QrCodeService implements QrCodeServiceInterface
{
    private LoggerInterface $logger;

    public function __construct(
        private QrCodeMonkeyClientInterface $apiClient,
        private QrImageProcessorInterface $imageProcessor,
        private SettingRepositoryInterface $settingRepo,
        private Aliases $aliases,
        ?LoggerInterface $logger = null,
        private ?string $qrStorageDir = null,
        private ?string $logoStorageDir = null,
        private ?string $frameAssetsDir = null,
        private ?string $galleryLogoAssetsDir = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function generate(
        string $url,
        string $name = '',
        ?string $logo = null,
        ?string $templateName = null,
        ?string $frameName = null
    ): array {
        $sanitizedUrl = $this->sanitizeUrl($url);
        if ($sanitizedUrl === '') {
            throw new RuntimeException('Cannot generate QR code: target URL is invalid or empty.');
        }

        // Retrieve configured download size and error correction
        $qrSize = $this->settingRepo->getInt('qrcode_default_download_size', QrTemplateDefinition::DEFAULT_DOWNLOAD_SIZE);
        if ($qrSize < 200 || $qrSize > 4000) {
            $qrSize = QrTemplateDefinition::DEFAULT_DOWNLOAD_SIZE;
        }

        $errorCorrection = $this->settingRepo->get('qrcode_default_error_correction', QrTemplateDefinition::DEFAULT_ERROR_CORRECTION);
        if (!in_array(strtoupper((string) $errorCorrection), ['L', 'M', 'Q', 'H'], true)) {
            $errorCorrection = QrTemplateDefinition::DEFAULT_ERROR_CORRECTION;
        }

        // Resolve design template configuration
        $selectedTemplate = !empty($templateName) ? (string) $templateName : 'default';
        $templateConfig = QrTemplateDefinition::getTemplate($selectedTemplate);

        // Ensure error correction is set in config
        $templateConfig['errorCorrection'] = strtoupper((string) $errorCorrection);

        // Build file naming based on sanitized slug and timestamp
        $timestamp = time();
        $slug = !empty($name) ? (string) preg_replace('/[^a-z0-9]+/', '-', strtolower($name)) : '';
        $rawFileName = $slug !== '' ? "qrcode-{$slug}-{$timestamp}.png" : "qrcode-{$timestamp}.png";
        $mergedFileName = $slug !== '' ? "merged-qrcode-{$slug}-{$timestamp}.png" : "merged-qrcode-{$timestamp}.png";

        $storageDir = $this->resolveQrStorageDir();
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0775, true);
        }

        $rawDiskPath = $storageDir . DIRECTORY_SEPARATOR . $rawFileName;
        $mergedDiskPath = $storageDir . DIRECTORY_SEPARATOR . $mergedFileName;

        // Resolve local logo disk path (supports template default logo fallback)
        if ($logo === 'none' || $logo === 'clear') {
            $effectiveLogo = null;
        } elseif (!empty($logo)) {
            $effectiveLogo = $logo;
        } else {
            $effectiveLogo = $templateConfig['logo'] ?? null;
        }
        $resolvedLogoPath = $this->resolveLogoDiskPath($effectiveLogo);

        // Execute remote QR generation via QRCodeMonkey API
        $this->apiClient->generateCustomQr(
            payloadUrl: $sanitizedUrl,
            config: $templateConfig,
            size: $qrSize,
            localLogoPath: $resolvedLogoPath,
            outputPath: $rawDiskPath
        );

        // Resolve frame overlay background image (supports template default frame fallback)
        if ($frameName === 'none' || $frameName === 'clear' || $frameName === 'default' || $frameName === 'default-7') {
            $effectiveFrame = '';
        } elseif (!empty($frameName)) {
            $effectiveFrame = (string) $frameName;
        } else {
            $effectiveFrame = (string) ($templateConfig['frame'] ?? '');
        }

        $frameBackgroundPath = $this->resolveFrameImagePath($effectiveFrame, $selectedTemplate);
        if ($effectiveFrame !== '' && $frameBackgroundPath === null) {
            $this->logger->warning("Frame overlay '{$effectiveFrame}' image not found in assets directory.");
            throw new RuntimeException("Frame overlay template '{$effectiveFrame}' could not be located in assets.");
        }

        $webRelativeRaw = 'assets/qrcode_generated/' . $rawFileName;
        $webRelativeMerged = 'assets/qrcode_generated/' . $mergedFileName;

        if ($effectiveFrame !== '' && $frameBackgroundPath !== null && file_exists($frameBackgroundPath)) {
            // Blend frame overlay with generated QR PNG
            $this->imageProcessor->mergeFrame(
                qrCodeImagePath: $rawDiskPath,
                frameBackgroundPath: $frameBackgroundPath,
                outputPath: $mergedDiskPath,
                frameName: $effectiveFrame
            );
            $finalPath = $webRelativeMerged;
            $finalDiskPath = $mergedDiskPath;
        } else {
            $finalPath = $webRelativeRaw;
            $finalDiskPath = $rawDiskPath;
        }

        return [
            'path' => $finalPath,
            'diskPath' => $finalDiskPath,
            'fileName' => $webRelativeRaw,
            'rawDiskPath' => $rawDiskPath,
            'size' => $qrSize,
            'effectiveFrame' => $effectiveFrame,
        ];
    }

    public function sanitizeUrl(string $url): string
    {
        $trimmed = trim($url);
        if ($trimmed === '') {
            return '';
        }

        // Check if explicit QR generation base URL override is configured in environment
        $qrGenBaseUrl = \App\Environment::qrCodeGenerationBaseUrl();
        if (!empty($qrGenBaseUrl)) {
            $appBaseUrl = rtrim(\App\Environment::appUrl(), '/');
            if (!empty($appBaseUrl) && str_starts_with($trimmed, $appBaseUrl)) {
                return rtrim($qrGenBaseUrl, '/') . substr($trimmed, strlen($appBaseUrl));
            }
        }

        $parsed = parse_url($trimmed);
        if ($parsed === false || !isset($parsed['host'])) {
            return $trimmed;
        }

        $host = strtolower($parsed['host']);
        if ($host === 'localhost' || $host === '127.0.0.1') {
            $safeHost = \App\Environment::localQrSafeHost();
            $scheme = $parsed['scheme'] ?? 'http';
            $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
            $pathAndQuery = ($parsed['path'] ?? '')
                . (isset($parsed['query']) ? '?' . $parsed['query'] : '')
                . (isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '');

            return $scheme . '://' . $safeHost . $port . $pathAndQuery;
        }

        return $trimmed;
    }

    public function convertToJpg(string $pngPath, ?string $outputPath = null, int $quality = 90): string
    {
        return $this->imageProcessor->convertToJpg($pngPath, $outputPath, $quality);
    }

    public function convertToPdf(string $imagePath): string
    {
        return $this->imageProcessor->convertToPdf($imagePath);
    }

    public function validateLogo(string $filePath): array
    {
        return $this->imageProcessor->validateLogo($filePath);
    }

    /**
     * Resolves the disk path to an uploaded logo or gallery icon.
     */
    private function resolveLogoDiskPath(?string $logo): ?string
    {
        if ($logo === null || trim($logo) === '') {
            return null;
        }

        $trimmed = trim($logo);

        // Direct existing absolute file path
        if (file_exists($trimmed) && is_readable($trimmed)) {
            return $trimmed;
        }

        // Relative path inside public web root (e.g. assets/company_logo/...)
        $publicRoot = $this->aliases->get('@public');
        $candidatePublic = $publicRoot . DIRECTORY_SEPARATOR . ltrim($trimmed, '/\\');
        if (file_exists($candidatePublic) && is_readable($candidatePublic)) {
            return $candidatePublic;
        }

        // Gallery logo name (e.g. 'ratings', 'google-bussiness')
        $safeName = basename($trimmed, '.png');
        $galleryDirs = [
            $this->galleryLogoAssetsDir,
            $publicRoot . '/assets/qr/logos',
        ];

        foreach ($galleryDirs as $dir) {
            if ($dir !== null && is_dir($dir)) {
                $candidate = $dir . DIRECTORY_SEPARATOR . $safeName . '.png';
                if (file_exists($candidate) && is_readable($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    /**
     * Resolves the disk path to a frame overlay template image.
     */
    private function resolveFrameImagePath(string $frameName, string $templateName): ?string
    {
        $publicRoot = $this->aliases->get('@public');
        $searchDirs = [
            $this->frameAssetsDir,
            $publicRoot . '/assets/qr/frames',
        ];

        $effective = $frameName;
        if ($effective === '' && $templateName === 'classic-frame') {
            $effective = 'banner-bottom';
        }

        $candidates = [];
        if ($effective === '' || $effective === 'default' || $effective === 'default-8' || $effective === 'default-7') {
            $candidates[] = 'qrcode.png';
            $candidates[] = 'default.png';
            $candidates[] = 'desktop-view/default.png';
        } else {
            $candidates[] = $effective . '.png';
            $candidates[] = 'desktop-view/' . $effective . '.png';
        }

        foreach ($searchDirs as $dir) {
            if ($dir === null || !is_dir($dir)) {
                continue;
            }
            foreach ($candidates as $subPath) {
                $candidatePath = $dir . DIRECTORY_SEPARATOR . ltrim($subPath, '/\\');
                if (file_exists($candidatePath) && is_readable($candidatePath)) {
                    return $candidatePath;
                }
            }
        }

        return null;
    }

    private function resolveQrStorageDir(): string
    {
        if ($this->qrStorageDir !== null) {
            return $this->qrStorageDir;
        }

        return $this->aliases->get('@public/assets/qrcode_generated');
    }
}
