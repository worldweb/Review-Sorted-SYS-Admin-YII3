<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var array<string, ?string> $settings
 */
?>

<div class="rs-form-group rs-mb-4">
    <label for="email_batch_size" class="rs-label">Email Queue Worker Batch Size</label>
    <input type="number" class="rs-input" id="email_batch_size" name="settings[queue.email_batch_size]" value="<?= Html::encode($settings['queue.email_batch_size'] ?? '50') ?>">
</div>

<div class="rs-form-group rs-mb-4">
    <label for="whatsapp_batch_size" class="rs-label">WhatsApp Queue Worker Batch Size</label>
    <input type="number" class="rs-input" id="whatsapp_batch_size" name="settings[queue.whatsapp_batch_size]" value="<?= Html::encode($settings['queue.whatsapp_batch_size'] ?? '50') ?>">
</div>

<div class="rs-form-group rs-mb-4">
    <label for="social_post_batch_size" class="rs-label">Social Post Queue Worker Batch Size</label>
    <input type="number" class="rs-input" id="social_post_batch_size" name="settings[queue.social_post_batch_size]" value="<?= Html::encode($settings['queue.social_post_batch_size'] ?? '25') ?>">
</div>

<div class="rs-form-group rs-mb-4">
    <label for="worker_max_retries" class="rs-label">Maximum Worker Auto-Retries on Failure</label>
    <input type="number" class="rs-input" id="worker_max_retries" name="settings[queue.max_retries]" value="<?= Html::encode($settings['queue.max_retries'] ?? '3') ?>">
</div>
