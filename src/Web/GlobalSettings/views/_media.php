<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var array<string, ?string> $settings
 */
?>

<div class="rs-form-group rs-mb-4">
    <label for="max_upload_size" class="rs-label">Max Upload File Size (MB)</label>
    <input type="number" class="rs-input" id="max_upload_size" name="settings[media.max_size_mb]" value="<?= Html::encode($settings['media.max_size_mb'] ?? '20') ?>">
</div>

<div class="rs-form-group rs-mb-4">
    <label for="allowed_extensions" class="rs-label">Allowed Media File Extensions</label>
    <input type="text" class="rs-input" id="allowed_extensions" name="settings[media.allowed_exts]" value="<?= Html::encode($settings['media.allowed_exts'] ?? 'jpg, jpeg, png, gif, mp4, mov, pdf, csv') ?>">
</div>

<div class="rs-form-group rs-mb-4">
    <label for="storage_driver" class="rs-label">Default Media Storage Driver</label>
    <select class="rs-input" id="storage_driver" name="settings[media.storage_driver]">
        <option value="local" <?= ($settings['media.storage_driver'] ?? 'local') === 'local' ? 'selected' : '' ?>>Local Filesystem (/uploads)</option>
        <option value="s3" <?= ($settings['media.storage_driver'] ?? '') === 's3' ? 'selected' : '' ?>>Amazon S3 / DigitalOcean Spaces</option>
    </select>
</div>
