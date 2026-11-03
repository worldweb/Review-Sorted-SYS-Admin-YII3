<?php

declare(strict_types=1);

namespace App\Domain\QrCode\Service;

/**
 * Interface for GD-based QR Code Image Processing.
 * Governs frame overlay blending, JPG conversion, and custom logo validation.
 */
interface QrImageProcessorInterface
{
    /**
     * Blends a generated QR code image with an optional frame background overlay.
     * Applies exact legacy vertical positioning offsets and preserves alpha transparency.
     * Falls back to raw QR code if frame background is unavailable.
     *
     * @param string $qrCodeImagePath Absolute disk path to generated QR PNG.
     * @param string|null $frameBackgroundPath Absolute disk path to frame template image.
     * @param string $outputPath Destination disk path for the merged PNG.
     * @param string $frameName Identifier of frame layout (e.g. 'balloon-top', 'focus').
     * @return string Path to the written merged image.
     */
    public function mergeFrame(
        string $qrCodeImagePath,
        ?string $frameBackgroundPath,
        string $outputPath,
        string $frameName = ''
    ): string;

    /**
     * Converts a PNG QR code image to a high-quality JPEG rendered on a solid white background.
     *
     * @param string $pngPath Absolute path to source PNG image.
     * @param string|null $outputPath Optional target output path; generates temporary file if omitted.
     * @param int $quality JPEG compression quality (default 90).
     * @return string Path to generated JPEG file.
     */
    public function convertToJpg(
        string $pngPath,
        ?string $outputPath = null,
        int $quality = 90
    ): string;

    /**
     * Validates an uploaded logo file against dimension, format, and size constraints.
     *
     * @param string $filePath Disk path to uploaded candidate logo.
     * @return array{valid: bool, error: ?string}
     */
    public function validateLogo(string $filePath): array;
}
