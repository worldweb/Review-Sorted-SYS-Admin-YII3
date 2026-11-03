<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var array<string, ?string> $settings
 */
?>

<div class="rs-form-group rs-mb-4">
    <label for="negative_threshold" class="rs-label">Negative Review Alert Rating Threshold</label>
    <select class="rs-input" id="negative_threshold" name="settings[reviews.negative_threshold]">
        <option value="1" <?= ($settings['reviews.negative_threshold'] ?? '3') === '1' ? 'selected' : '' ?>>1 Star Only</option>
        <option value="2" <?= ($settings['reviews.negative_threshold'] ?? '3') === '2' ? 'selected' : '' ?>>2 Stars &amp; Below</option>
        <option value="3" <?= ($settings['reviews.negative_threshold'] ?? '3') === '3' ? 'selected' : '' ?>>3 Stars &amp; Below (Default)</option>
        <option value="4" <?= ($settings['reviews.negative_threshold'] ?? '3') === '4' ? 'selected' : '' ?>>4 Stars &amp; Below</option>
    </select>
    <div class="rs-text-sm rs-text-muted rs-mt-1">Reviews at or below this rating trigger instant email alerts to the location's designated manager.</div>
</div>

<div class="rs-form-group rs-mb-4">
    <label class="rs-checkbox-wrap rs-d-flex rs-align-items-center rs-gap-2">
        <input class="rs-checkbox" type="checkbox" id="auto_sync_reviews" name="settings[reviews.auto_sync]" value="1" <?= ($settings['reviews.auto_sync'] ?? '1') === '1' ? 'checked' : '' ?>>
        <span class="rs-checkbox-label rs-font-semibold">Enable Periodic Review Sync Background Worker</span>
    </label>
</div>

<div class="rs-form-group rs-mb-4">
    <label for="sync_interval" class="rs-label">Review Sync Interval (Minutes)</label>
    <input type="number" class="rs-input" id="sync_interval" name="settings[reviews.sync_interval]" value="<?= Html::encode($settings['reviews.sync_interval'] ?? '60') ?>">
</div>
