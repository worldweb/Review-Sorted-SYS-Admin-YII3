<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var array<string, ?string> $settings
 */
?>

<div class="rs-form-group rs-mb-4">
    <label for="platform_name" class="rs-label">Platform Application Name</label>
    <input type="text" class="rs-input" id="platform_name" name="settings[general.platform_name]" value="<?= Html::encode($settings['general.platform_name'] ?? 'Reviews Sorted') ?>">
    <div class="rs-text-sm rs-text-muted rs-mt-1">Display name shown across platform headers and customer-facing interfaces.</div>
</div>

<div class="rs-form-group rs-mb-4">
    <label for="support_email" class="rs-label">System Support Email</label>
    <input type="email" class="rs-input" id="support_email" name="settings[general.support_email]" value="<?= Html::encode($settings['general.support_email'] ?? 'support@sortedreviews.com') ?>">
</div>

<div class="rs-form-group rs-mb-4">
    <label for="default_timezone" class="rs-label">Default System Timezone</label>
    <input type="text" class="rs-input" id="default_timezone" name="settings[general.timezone]" value="<?= Html::encode($settings['general.timezone'] ?? 'UTC') ?>">
</div>

<div class="rs-form-group rs-mb-4">
    <label for="date_format" class="rs-label">Default Date Format</label>
    <input type="text" class="rs-input" id="date_format" name="settings[general.date_format]" value="<?= Html::encode($settings['general.date_format'] ?? 'Y-m-d H:i:s') ?>">
</div>
