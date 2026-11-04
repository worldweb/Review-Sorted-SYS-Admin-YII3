<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var array<string, ?string> $settings
 */
$graceDays = (int) ($settings['subscription_grace_period_days'] ?? '3');
$maxRetryAttempts = (int) ($settings['social_post_max_retry_attempts'] ?? '3');
?>

<div class="rs-form-group rs-mb-4">
    <label for="subscription_grace_period_days" class="rs-label">Subscription Grace Period (Days)</label>
    <input type="number" min="0" max="30" class="rs-input" id="subscription_grace_period_days" name="settings[subscription_grace_period_days]" value="<?= $graceDays ?>" required>
    <div class="rs-text-sm rs-text-muted rs-mt-1">Number of grace days allowed after subscription renewal payment failure before account features are restricted.</div>
</div>

<div class="rs-form-group rs-mb-4">
    <label for="social_post_max_retry_attempts" class="rs-label">Social Publishing Max Auto-Retry Attempts</label>
    <select class="rs-input rs-select" id="social_post_max_retry_attempts" name="settings[social_post_max_retry_attempts]">
        <option value="1" <?= $maxRetryAttempts === 1 ? 'selected' : '' ?>>1 Attempt (No retries)</option>
        <option value="3" <?= $maxRetryAttempts === 3 ? 'selected' : '' ?>>3 Attempts (Default)</option>
        <option value="5" <?= $maxRetryAttempts === 5 ? 'selected' : '' ?>>5 Attempts</option>
        <option value="10" <?= $maxRetryAttempts === 10 ? 'selected' : '' ?>>10 Attempts</option>
    </select>
    <div class="rs-text-sm rs-text-muted rs-mt-1">Maximum times the worker will automatically retry publishing a failed social post destination before marking it permanently failed.</div>
</div>
