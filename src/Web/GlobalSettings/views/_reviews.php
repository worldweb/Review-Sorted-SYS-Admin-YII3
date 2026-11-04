<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var array<string, ?string> $settings
 */
$refreshInterval = (int) ($settings['review_auto_refresh_interval'] ?? '10');
$defaultPageSize = (int) ($settings['review_default_page_size'] ?? '20');
$chatPollInterval = (int) ($settings['chat_live_poll_interval'] ?? '5');
?>

<div class="rs-form-group rs-mb-4">
    <label for="review_auto_refresh_interval" class="rs-label">Reviews Auto-Refresh Interval (seconds)</label>
    <input type="number" min="5" max="120" class="rs-input" id="review_auto_refresh_interval" name="settings[review_auto_refresh_interval]" value="<?= $refreshInterval ?>" required>
    <div class="rs-text-sm rs-text-muted rs-mt-1">Frequency in seconds to poll for incoming reviews on the active reviews dashboard (between 5 and 120).</div>
</div>

<div class="rs-form-group rs-mb-4">
    <label for="review_default_page_size" class="rs-label">Default Reviews Per Page</label>
    <select class="rs-input rs-select" id="review_default_page_size" name="settings[review_default_page_size]">
        <option value="10" <?= $defaultPageSize === 10 ? 'selected' : '' ?>>10 Rows</option>
        <option value="20" <?= $defaultPageSize === 20 ? 'selected' : '' ?>>20 Rows (Default)</option>
        <option value="50" <?= $defaultPageSize === 50 ? 'selected' : '' ?>>50 Rows</option>
        <option value="100" <?= $defaultPageSize === 100 ? 'selected' : '' ?>>100 Rows</option>
    </select>
    <div class="rs-text-sm rs-text-muted rs-mt-1">Default number of review items displayed per page on the main reviews listing.</div>
</div>

<div class="rs-form-group rs-mb-4">
    <label for="chat_live_poll_interval" class="rs-label">Live Chat Poll Interval (seconds)</label>
    <input type="number" min="3" max="60" class="rs-input" id="chat_live_poll_interval" name="settings[chat_live_poll_interval]" value="<?= $chatPollInterval ?>" required>
    <div class="rs-text-sm rs-text-muted rs-mt-1">Interval in seconds for live chat and mention stream polling (between 3 and 60).</div>
</div>
