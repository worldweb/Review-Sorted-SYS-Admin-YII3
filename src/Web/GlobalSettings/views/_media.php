<?php

declare(strict_types=1);

/**
 * @var array<string, ?string> $settings
 */
?>

<div class="mb-3">
    <label for="max_upload_size" class="form-label fw-bold">Max Upload File Size (MB)</label>
    <input type="number" class="form-control" id="max_upload_size" name="settings[media.max_size_mb]" value="<?= htmlspecialchars($settings['media.max_size_mb'] ?? '20') ?>">
</div>

<div class="mb-3">
    <label for="allowed_extensions" class="form-label fw-bold">Allowed Media File Extensions</label>
    <input type="text" class="form-control" id="allowed_extensions" name="settings[media.allowed_exts]" value="<?= htmlspecialchars($settings['media.allowed_exts'] ?? 'jpg, jpeg, png, gif, mp4, mov, pdf, csv') ?>">
</div>

<div class="mb-3">
    <label for="storage_driver" class="form-label fw-bold">Default Media Storage Driver</label>
    <select class="form-select" id="storage_driver" name="settings[media.storage_driver]">
        <option value="local" <?= ($settings['media.storage_driver'] ?? 'local') === 'local' ? 'selected' : '' ?>>Local Filesystem (/uploads)</option>
        <option value="s3" <?= ($settings['media.storage_driver'] ?? '') === 's3' ? 'selected' : '' ?>>Amazon S3 / DigitalOcean Spaces</option>
    </select>
</div>
