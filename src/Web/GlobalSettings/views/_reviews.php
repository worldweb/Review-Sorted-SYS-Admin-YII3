<?php

declare(strict_types=1);

/**
 * @var array<string, ?string> $settings
 */
?>

<div class="mb-3">
    <label for="negative_threshold" class="form-label fw-bold">Negative Review Alert Rating Threshold</label>
    <select class="form-select" id="negative_threshold" name="settings[reviews.negative_threshold]">
        <option value="1" <?= ($settings['reviews.negative_threshold'] ?? '3') === '1' ? 'selected' : '' ?>>1 Star Only</option>
        <option value="2" <?= ($settings['reviews.negative_threshold'] ?? '3') === '2' ? 'selected' : '' ?>>2 Stars &amp; Below</option>
        <option value="3" <?= ($settings['reviews.negative_threshold'] ?? '3') === '3' ? 'selected' : '' ?>>3 Stars &amp; Below (Default)</option>
        <option value="4" <?= ($settings['reviews.negative_threshold'] ?? '3') === '4' ? 'selected' : '' ?>>4 Stars &amp; Below</option>
    </select>
    <div class="form-text">Reviews at or below this rating trigger instant email alerts to the location's designated manager.</div>
</div>

<div class="form-check form-switch mb-3">
    <input class="form-check-input" type="checkbox" id="auto_sync_reviews" name="settings[reviews.auto_sync]" value="1" <?= ($settings['reviews.auto_sync'] ?? '1') === '1' ? 'checked' : '' ?>>
    <label class="form-check-label fw-bold" for="auto_sync_reviews">Enable Periodic Review Sync Background Worker</label>
</div>

<div class="mb-3">
    <label for="sync_interval" class="form-label fw-bold">Review Sync Interval (Minutes)</label>
    <input type="number" class="form-control" id="sync_interval" name="settings[reviews.sync_interval]" value="<?= htmlspecialchars($settings['reviews.sync_interval'] ?? '60') ?>">
</div>
