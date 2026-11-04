<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var array<string, ?string> $settings
 */
$errorCorrection = $settings['qrcode_default_error_correction'] ?? 'H';
$downloadSize = (int) ($settings['qrcode_default_download_size'] ?? '1000');
$maxLogoSizeKb = (int) ($settings['media_max_logo_filesize_kb'] ?? '2048');
?>

<div class="rs-form-group rs-mb-4">
    <label for="qrcode_default_error_correction" class="rs-label">QR Code Default Error Correction</label>
    <select class="rs-input rs-select" id="qrcode_default_error_correction" name="settings[qrcode_default_error_correction]">
        <option value="L" <?= $errorCorrection === 'L' ? 'selected' : '' ?>>Low (7% recovery)</option>
        <option value="M" <?= $errorCorrection === 'M' ? 'selected' : '' ?>>Medium (15% recovery)</option>
        <option value="Q" <?= $errorCorrection === 'Q' ? 'selected' : '' ?>>Quartile (25% recovery)</option>
        <option value="H" <?= $errorCorrection === 'H' ? 'selected' : '' ?>>High (30% recovery - Recommended)</option>
    </select>
    <div class="rs-text-sm rs-text-muted rs-mt-1">Redundancy level used when generating new customer feedback QR codes. Higher values allow scanning even if partially obscured by logos.</div>
</div>

<div class="rs-form-group rs-mb-4">
    <label for="qrcode_default_download_size" class="rs-label">QR Code Default Download Resolution</label>
    <select class="rs-input rs-select" id="qrcode_default_download_size" name="settings[qrcode_default_download_size]">
        <option value="300" <?= $downloadSize === 300 ? 'selected' : '' ?>>300px (Digital display)</option>
        <option value="500" <?= $downloadSize === 500 ? 'selected' : '' ?>>500px (Standard)</option>
        <option value="1000" <?= $downloadSize === 1000 ? 'selected' : '' ?>>1000px (Print / High-DPI - Default)</option>
        <option value="2000" <?= $downloadSize === 2000 ? 'selected' : '' ?>>2000px (Large poster / Billboard)</option>
    </select>
    <div class="rs-text-sm rs-text-muted rs-mt-1">Resolution of exported PNG images downloaded from the QR code management console.</div>
</div>

<div class="rs-form-group rs-mb-4">
    <label for="media_max_logo_filesize_kb" class="rs-label">Maximum Account Logo File Size (KB)</label>
    <input type="number" min="100" max="10240" class="rs-input" id="media_max_logo_filesize_kb" name="settings[media_max_logo_filesize_kb]" value="<?= $maxLogoSizeKb ?>" required>
    <div class="rs-text-sm rs-text-muted rs-mt-1">Maximum allowed file size in kilobytes for account and store logo uploads (default: 2048 KB / 2 MB).</div>
</div>
