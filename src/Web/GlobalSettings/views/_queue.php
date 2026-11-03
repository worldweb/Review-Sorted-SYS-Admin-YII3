<?php

declare(strict_types=1);

/**
 * @var array<string, ?string> $settings
 */
?>

<div class="mb-3">
    <label for="email_batch_size" class="form-label fw-bold">Email Queue Worker Batch Size</label>
    <input type="number" class="form-control" id="email_batch_size" name="settings[queue.email_batch_size]" value="<?= htmlspecialchars($settings['queue.email_batch_size'] ?? '50') ?>">
</div>

<div class="mb-3">
    <label for="whatsapp_batch_size" class="form-label fw-bold">WhatsApp Queue Worker Batch Size</label>
    <input type="number" class="form-control" id="whatsapp_batch_size" name="settings[queue.whatsapp_batch_size]" value="<?= htmlspecialchars($settings['queue.whatsapp_batch_size'] ?? '50') ?>">
</div>

<div class="mb-3">
    <label for="social_post_batch_size" class="form-label fw-bold">Social Post Queue Worker Batch Size</label>
    <input type="number" class="form-control" id="social_post_batch_size" name="settings[queue.social_post_batch_size]" value="<?= htmlspecialchars($settings['queue.social_post_batch_size'] ?? '25') ?>">
</div>

<div class="mb-3">
    <label for="worker_max_retries" class="form-label fw-bold">Maximum Worker Auto-Retries on Failure</label>
    <input type="number" class="form-control" id="worker_max_retries" name="settings[queue.max_retries]" value="<?= htmlspecialchars($settings['queue.max_retries'] ?? '3') ?>">
</div>
