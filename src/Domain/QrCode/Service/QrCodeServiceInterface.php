<?php

declare(strict_types=1);

namespace App\Domain\QrCode\Service;

/**
 * Interface for QR Code Studio Service.
 * High-level orchestration for QR generation, design templating, frame blending,
 * URL sanitization, and format export conversions.
 */
interface QrCodeServiceInterface
{
    /**
     * Generates a fully styled and framed QR code.
     *
     * @param string $url Target destination URL.
     * @param string $name Label or title for file naming.
     * @param string|null $logo Gallery logo key or uploaded file disk path.
     * @param string|null $templateName Template key (from QrTemplateDefinition).
     * @param string|null $frameName Frame key (from QrTemplateDefinition).
     * @return array{path: string, diskPath: string, fileName: string, rawDiskPath: string, size: int}
     */
    public function generate(
        string $url,
        string $name = '',
        ?string $logo = null,
        ?string $templateName = null,
        ?string $frameName = null
    ): array;

    /**
     * Sanitizes a destination URL for external API generation.
     * Rewrites local hostnames (localhost, 127.0.0.1) to RS_LOCAL_QR_SAFE_HOST ('sys.test')
     * to prevent upstream firewall/WAF blocks.
     */
    public function sanitizeUrl(string $url): string;

    /**
     * Converts a generated PNG to a JPEG image with white canvas background.
     *
     * @param string $pngPath Absolute path to PNG file.
     * @param string|null $outputPath Optional destination path.
     * @param int $quality Compression quality (default 90).
     * @return string Path to the written JPEG file.
     */
    public function convertToJpg(string $pngPath, ?string $outputPath = null, int $quality = 90): string;

    /**
     * Validates an uploaded custom logo against dimension and format constraints.
     *
     * @param string $filePath Disk path to candidate logo file.
     * @return array{valid: bool, error: ?string}
     */
    public function validateLogo(string $filePath): array;
}
