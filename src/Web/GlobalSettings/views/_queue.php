<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var array<string, ?string> $settings
 */
$queueEnabled = (int) ($settings['social_post_queue_enabled'] ?? '1');
$scheduleTz = $settings['social_post_schedule_timezone'] ?? 'Australia/Perth';
$maxScheduleDays = (int) ($settings['social_post_max_schedule_days'] ?? '7');
$minLeadMinutes = (int) ($settings['social_post_min_lead_time_minutes'] ?? '0');
$workerBatchSize = (int) ($settings['social_post_worker_batch_size'] ?? '50');
$stuckTimeoutMinutes = (int) ($settings['social_post_stuck_job_timeout_minutes'] ?? '15');
$autoRetryEnabled = (int) ($settings['social_post_auto_retry_enabled'] ?? '0');
$retryDelayMinutes = (int) ($settings['social_post_retry_delay_minutes'] ?? '5');
$retentionDays = (int) ($settings['social_post_failed_job_retention_days'] ?? '30');

$commonTimezones = [
    'Australia/Perth' => 'Australia/Perth (AWST, UTC+8)',
    'Australia/Sydney' => 'Australia/Sydney (AEST/AEDT, UTC+10/+11)',
    'Australia/Melbourne' => 'Australia/Melbourne (AEST/AEDT, UTC+10/+11)',
    'Australia/Brisbane' => 'Australia/Brisbane (AEST, UTC+10)',
    'Australia/Adelaide' => 'Australia/Adelaide (ACST/ACDT, UTC+9:30/+10:30)',
    'Australia/Darwin' => 'Australia/Darwin (ACST, UTC+9:30)',
    'UTC' => 'UTC (Coordinated Universal Time)',
    'America/New_York' => 'America/New_York (EST/EDT, UTC-5/-4)',
    'America/Los_Angeles' => 'America/Los_Angeles (PST/PDT, UTC-8/-7)',
    'Europe/London' => 'Europe/London (GMT/BST, UTC+0/+1)',
    'Asia/Singapore' => 'Asia/Singapore (SGT, UTC+8)',
];
?>

<div class="rs-form-group rs-mb-4">
    <label for="social_post_queue_enabled" class="rs-label">Social Publishing Queue</label>
    <select class="rs-input rs-select" id="social_post_queue_enabled" name="settings[social_post_queue_enabled]">
        <option value="1" <?= $queueEnabled === 1 ? 'selected' : '' ?>>Enabled</option>
        <option value="0" <?= $queueEnabled === 0 ? 'selected' : '' ?>>Disabled</option>
    </select>
    <div class="rs-text-sm rs-text-muted rs-mt-1">When disabled, automated background worker will pause processing pending publishing jobs.</div>
</div>

<div class="rs-form-group rs-mb-4">
    <label for="social_post_schedule_timezone" class="rs-label">Default Scheduling Timezone</label>
    <select class="rs-input rs-select" id="social_post_schedule_timezone" name="settings[social_post_schedule_timezone]">
        <?php foreach ($commonTimezones as $tzVal => $tzLabel): ?>
            <option value="<?= Html::encode($tzVal) ?>" <?= $scheduleTz === $tzVal ? 'selected' : '' ?>>
                <?= Html::encode($tzLabel) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <div class="rs-text-sm rs-text-muted rs-mt-1">Timezone used when users select date and time for scheduled social publishing.</div>
</div>

<div class="rs-form-group rs-mb-4">
    <label for="social_post_max_schedule_days" class="rs-label">Maximum Advance Scheduling Window (Days)</label>
    <input type="number" min="1" max="365" class="rs-input" id="social_post_max_schedule_days" name="settings[social_post_max_schedule_days]" value="<?= $maxScheduleDays ?>" required>
    <div class="rs-text-sm rs-text-muted rs-mt-1">Maximum number of days in advance a user is allowed to schedule a post (default: 7 days).</div>
</div>

<div class="rs-form-group rs-mb-4">
    <label for="social_post_min_lead_time_minutes" class="rs-label">Minimum Scheduling Lead Time (Minutes)</label>
    <input type="number" min="0" max="1440" class="rs-input" id="social_post_min_lead_time_minutes" name="settings[social_post_min_lead_time_minutes]" value="<?= $minLeadMinutes ?>" required>
    <div class="rs-text-sm rs-text-muted rs-mt-1">Minimum advance notice in minutes required when scheduling a post (0 = allow immediate future).</div>
</div>

<div class="rs-form-group rs-mb-4">
    <label for="social_post_worker_batch_size" class="rs-label">Worker Batch Size (Jobs Per Run)</label>
    <input type="number" min="1" max="200" class="rs-input" id="social_post_worker_batch_size" name="settings[social_post_worker_batch_size]" value="<?= $workerBatchSize ?>" required>
    <div class="rs-text-sm rs-text-muted rs-mt-1">Number of queued publishing destination jobs processed per worker invocation (default: 50).</div>
</div>

<div class="rs-form-group rs-mb-4">
    <label for="social_post_stuck_job_timeout_minutes" class="rs-label">Stuck Job Timeout (Minutes)</label>
    <input type="number" min="5" max="120" class="rs-input" id="social_post_stuck_job_timeout_minutes" name="settings[social_post_stuck_job_timeout_minutes]" value="<?= $stuckTimeoutMinutes ?>" required>
    <div class="rs-text-sm rs-text-muted rs-mt-1">Minutes after which an in-progress job is considered stuck and recovered (default: 15).</div>
</div>

<div class="rs-form-group rs-mb-4">
    <label for="social_post_auto_retry_enabled" class="rs-label">Automatic Retries on Network / API Failure</label>
    <select class="rs-input rs-select" id="social_post_auto_retry_enabled" name="settings[social_post_auto_retry_enabled]">
        <option value="1" <?= $autoRetryEnabled === 1 ? 'selected' : '' ?>>Enabled</option>
        <option value="0" <?= $autoRetryEnabled === 0 ? 'selected' : '' ?>>Disabled</option>
    </select>
    <div class="rs-text-sm rs-text-muted rs-mt-1">Automatically re-queue transient API network failures after the configured delay.</div>
</div>

<div class="rs-form-group rs-mb-4">
    <label for="social_post_retry_delay_minutes" class="rs-label">Auto-Retry Delay (Minutes)</label>
    <input type="number" min="1" max="1440" class="rs-input" id="social_post_retry_delay_minutes" name="settings[social_post_retry_delay_minutes]" value="<?= $retryDelayMinutes ?>" required>
    <div class="rs-text-sm rs-text-muted rs-mt-1">Minutes to wait before attempting to retry a failed destination job.</div>
</div>

<div class="rs-form-group rs-mb-4">
    <label for="social_post_failed_job_retention_days" class="rs-label">Failed Job History Retention (Days)</label>
    <input type="number" min="1" max="365" class="rs-input" id="social_post_failed_job_retention_days" name="settings[social_post_failed_job_retention_days]" value="<?= $retentionDays ?>" required>
    <div class="rs-text-sm rs-text-muted rs-mt-1">Days to retain error messages and failure history for audited destinations.</div>
</div>
