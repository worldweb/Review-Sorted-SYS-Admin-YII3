<?php

declare(strict_types=1);

/**
 * @var array<string, ?string> $settings
 */
?>

<div class="mb-3">
    <label for="platform_name" class="form-label fw-bold">Platform Application Name</label>
    <input type="text" class="form-control" id="platform_name" name="settings[general.platform_name]" value="<?= htmlspecialchars($settings['general.platform_name'] ?? 'Reviews Sorted') ?>">
    <div class="form-text">Display name shown across platform headers and customer-facing interfaces.</div>
</div>

<div class="mb-3">
    <label for="support_email" class="form-label fw-bold">System Support Email</label>
    <input type="email" class="form-control" id="support_email" name="settings[general.support_email]" value="<?= htmlspecialchars($settings['general.support_email'] ?? 'support@sortedreviews.com') ?>">
</div>

<div class="mb-3">
    <label for="default_timezone" class="form-label fw-bold">Default System Timezone</label>
    <input type="text" class="form-control" id="default_timezone" name="settings[general.timezone]" value="<?= htmlspecialchars($settings['general.timezone'] ?? 'UTC') ?>">
</div>

<div class="mb-3">
    <label for="date_format" class="form-label fw-bold">Default Date Format</label>
    <input type="text" class="form-control" id="date_format" name="settings[general.date_format]" value="<?= htmlspecialchars($settings['general.date_format'] ?? 'Y-m-d H:i:s') ?>">
</div>
