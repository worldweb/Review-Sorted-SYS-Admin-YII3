<?php

declare(strict_types=1);

namespace App\Domain\QrCode\Service;

/**
 * Interface for QRCodeMonkey Remote API Client.
 * Governs remote customized QR generation and logo uploads.
 */
interface QrCodeMonkeyClientInterface
{
    /**
     * Generates a styled QR code via QRCodeMonkey API and saves the image to disk.
     *
     * @param string $payloadUrl Destination URL encoded inside the QR matrix.
     * @param array<string, mixed> $config Template configuration parameters.
     * @param int $size Dimension in pixels (width and height).
     * @param string|null $localLogoPath Local disk path to an uploaded logo, if any.
     * @param string $outputPath Absolute destination disk path for the downloaded PNG.
     * @return string The written output file path.
     */
    public function generateCustomQr(
        string $payloadUrl,
        array $config,
        int $size,
        ?string $localLogoPath,
        string $outputPath
    ): string;

    /**
     * Uploads a local logo file to QRCodeMonkey API using multipart/form-data.
     *
     * @param string $localLogoPath Absolute disk path to the logo file.
     * @return string|null Uploaded file ID hash or null on failure.
     */
    public function uploadLogo(string $localLogoPath): ?string;
}
