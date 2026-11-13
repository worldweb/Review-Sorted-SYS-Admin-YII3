<?php

declare(strict_types=1);

namespace App\Domain\QrCode\Service;

use RuntimeException;

/**
 * GD Image Processor for QR Code Studio.
 * Handles exact pixel positioning, alpha transparency preservation,
 * PNG-to-JPEG conversion, and logo integrity validation.
 */
final class QrImageProcessor implements QrImageProcessorInterface
{
    public function mergeFrame(
        string $qrCodeImagePath,
        ?string $frameBackgroundPath,
        string $outputPath,
        string $frameName = ''
    ): string {
        if (!file_exists($qrCodeImagePath) || !is_readable($qrCodeImagePath)) {
            throw new RuntimeException('QR code source image not found or unreadable: ' . $qrCodeImagePath);
        }

        $qrCode = @imagecreatefrompng($qrCodeImagePath);
        if (!$qrCode) {
            $data = @file_get_contents($qrCodeImagePath);
            if ($data !== false) {
                $qrCode = @imagecreatefromstring($data);
            }
        }

        if (!$qrCode) {
            throw new RuntimeException('Failed to load QR code image into GD canvas.');
        }

        // Ensure target directory exists
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        // If no background provided or background file does not exist, save raw QR code directly
        if ($frameBackgroundPath === null || $frameBackgroundPath === '' || !file_exists($frameBackgroundPath)) {
            imagepng($qrCode, $outputPath);
            return $outputPath;
        }

        $background = @imagecreatefrompng($frameBackgroundPath);
        if (!$background) {
            $bgData = @file_get_contents($frameBackgroundPath);
            if ($bgData !== false) {
                $background = @imagecreatefromstring($bgData);
            }
        }

        if (!$background) {
            // Fallback: save QR code directly if background image failed to load
            imagepng($qrCode, $outputPath);
            return $outputPath;
        }

        $qrCodeWidth = imagesx($qrCode);
        $qrCodeHeight = imagesy($qrCode);
        $backgroundWidth = imagesx($background);
        $backgroundHeight = imagesy($background);

        // Create blank true-color canvas matching frame dimensions
        $merged = imagecreatetruecolor($backgroundWidth, $backgroundHeight);
        imagesavealpha($merged, true);
        $transparent = imagecolorallocatealpha($merged, 0, 0, 0, 127);
        imagefill($merged, 0, 0, $transparent);

        // Copy background onto canvas
        imagecopy($merged, $background, 0, 0, 0, 0, $backgroundWidth, $backgroundHeight);

        // Proportionally scale QR code if background frame is smaller than QR code
        $renderQr = $qrCode;
        if ($backgroundWidth < $qrCodeWidth || $backgroundHeight < $qrCodeHeight) {
            $targetDim = (int) round(min($backgroundWidth, $backgroundHeight) * 0.72);
            if ($targetDim > 50) {
                $scaledQr = imagecreatetruecolor($targetDim, $targetDim);
                imagesavealpha($scaledQr, true);
                $transScaled = imagecolorallocatealpha($scaledQr, 0, 0, 0, 127);
                imagefill($scaledQr, 0, 0, $transScaled);
                imagecopyresampled($scaledQr, $qrCode, 0, 0, 0, 0, $targetDim, $targetDim, $qrCodeWidth, $qrCodeHeight);
                $renderQr = $scaledQr;
                $qrCodeWidth = $targetDim;
                $qrCodeHeight = $targetDim;
            }
        }

        // Calculate center position
        $x = ($backgroundWidth - $qrCodeWidth) / 2.0;
        $y = ($backgroundHeight - $qrCodeHeight) / 2.0;

        // Apply exact vertical offset defined for this frame (scaled if non-standard frame width)
        $rawOffset = QrTemplateDefinition::getFrameVerticalOffset($frameName);
        $offsetScale = $backgroundWidth / 1447.0;
        $y += $rawOffset * $offsetScale;

        $finalX = (int) round($x);
        $finalY = (int) round($y);

        // Blend QR code onto the framed canvas
        imagecopy($merged, $renderQr, $finalX, $finalY, 0, 0, $qrCodeWidth, $qrCodeHeight);

        imagepng($merged, $outputPath);

        return $outputPath;
    }

    public function convertToJpg(
        string $pngPath,
        ?string $outputPath = null,
        int $quality = 90
    ): string {
        if (!file_exists($pngPath) || !is_readable($pngPath)) {
            throw new RuntimeException('Source PNG image not found or unreadable: ' . $pngPath);
        }

        $image = @imagecreatefrompng($pngPath);
        if (!$image) {
            $data = @file_get_contents($pngPath);
            if ($data !== false) {
                $image = @imagecreatefromstring($data);
            }
        }

        if (!$image) {
            throw new RuntimeException('Failed to load source PNG image for JPEG conversion.');
        }

        $width = imagesx($image);
        $height = imagesy($image);

        // Create solid white background canvas
        $bg = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($bg, 255, 255, 255);
        imagefill($bg, 0, 0, $white);

        // Copy PNG over white background to avoid black transparency artifacts
        imagecopy($bg, $image, 0, 0, 0, 0, $width, $height);

        $targetPath = $outputPath;
        if ($targetPath === null || $targetPath === '') {
            $tempFile = tempnam(sys_get_temp_dir(), 'qr_jpg_');
            if ($tempFile === false) {
                throw new RuntimeException('Failed to create temporary file for JPEG output.');
            }
            $targetPath = $tempFile;
        }

        $dir = dirname($targetPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $clampedQuality = max(10, min(100, $quality));
        imagejpeg($bg, $targetPath, $clampedQuality);

        return $targetPath;
    }

    public function validateLogo(string $filePath): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return ['valid' => false, 'error' => 'Logo file does not exist or is unreadable.'];
        }

        $fileSize = filesize($filePath);
        if ($fileSize === false || $fileSize === 0) {
            return ['valid' => false, 'error' => 'Logo file is empty.'];
        }

        if ($fileSize > QrTemplateDefinition::LOGO_MAX_BYTES) {
            return ['valid' => false, 'error' => 'Maximum file size allowed is 500 KB.'];
        }

        // Verify MIME type via finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $filePath) : null;
        if ($finfo) {
            finfo_close($finfo);
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg'];
        if ($mime === null || !in_array(strtolower($mime), $allowedMimes, true)) {
            return ['valid' => false, 'error' => 'Only JPG and PNG images are allowed.'];
        }

        // Verify dimensions via getimagesize
        $imageInfo = @getimagesize($filePath);
        if (!$imageInfo || empty($imageInfo[0]) || empty($imageInfo[1])) {
            return ['valid' => false, 'error' => 'Uploaded file is not a valid image format.'];
        }

        $width = (int) $imageInfo[0];
        $height = (int) $imageInfo[1];

        if ($width < QrTemplateDefinition::LOGO_MIN_WIDTH || $height < QrTemplateDefinition::LOGO_MIN_HEIGHT) {
            return ['valid' => false, 'error' => 'Minimum resolution required is 250x250 pixels.'];
        }

        if ($width > QrTemplateDefinition::LOGO_MAX_WIDTH || $height > QrTemplateDefinition::LOGO_MAX_HEIGHT) {
            return ['valid' => false, 'error' => 'Maximum resolution allowed is 1080x1080 pixels.'];
        }

        return ['valid' => true, 'error' => null];
    }

    public function convertToPdf(string $imagePath): string
    {
        if (!file_exists($imagePath) || !is_readable($imagePath)) {
            throw new RuntimeException('Source image not found or unreadable for PDF generation: ' . $imagePath);
        }

        $image = @imagecreatefrompng($imagePath);
        if (!$image) {
            $image = @imagecreatefromjpeg($imagePath);
        }
        if (!$image) {
            $data = @file_get_contents($imagePath);
            if ($data !== false) {
                $image = @imagecreatefromstring($data);
            }
        }

        if (!$image) {
            throw new RuntimeException('Failed to decode source image for PDF generation.');
        }

        $width = imagesx($image);
        $height = imagesy($image);

        // Flatten transparent PNG onto clean white background
        $canvas = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);

        ob_start();
        imagejpeg($canvas, null, 95);
        $jpegData = (string) ob_get_clean();

        // Standard ISO A4 dimensions in PostScript points (72 points / inch, 210mm x 297mm)
        $pageWidth = 595.28;
        $pageHeight = 841.89;
        $margin = 36.0; // 0.5 inch margins
        $availWidth = $pageWidth - (2 * $margin);
        $availHeight = $pageHeight - (2 * $margin);

        $scale = min($availWidth / $width, $availHeight / $height);
        $renderWidth = round($width * $scale, 2);
        $renderHeight = round($height * $scale, 2);
        $posX = round(($pageWidth - $renderWidth) / 2, 2);
        $posY = round(($pageHeight - $renderHeight) / 2, 2);

        $contentStream = sprintf('q %.2f 0 0 %.2f %.2f %.2f cm /Im1 Do Q', $renderWidth, $renderHeight, $posX, $posY);
        $contentLength = strlen($contentStream);
        $jpegLength = strlen($jpegData);

        // Build valid, standards-compliant PDF-1.4 binary structure
        $pdf = "%PDF-1.4\n";
        $offsets = [];

        $offsets[1] = strlen($pdf);
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        $offsets[2] = strlen($pdf);
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        $offsets[3] = strlen($pdf);
        $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 " . $pageWidth . " " . $pageHeight . "] /Contents 4 0 R /Resources << /ProcSet [/PDF /ImageC] /XObject << /Im1 5 0 R >> >> >>\nendobj\n";

        $offsets[4] = strlen($pdf);
        $pdf .= "4 0 obj\n<< /Length " . $contentLength . " >>\nstream\n" . $contentStream . "\nendstream\nendobj\n";

        $offsets[5] = strlen($pdf);
        $pdf .= "5 0 obj\n<< /Type /XObject /Subtype /Image /Width " . $width . " /Height " . $height . " /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . $jpegLength . " >>\nstream\n" . $jpegData . "\nendstream\nendobj\n";

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        for ($i = 1; $i <= 5; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF\n";

        return $pdf;
    }
}
