<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var array<string, ?string> $settings
 */
$appName = $settings['branding_app_name'] ?? $settings['general.platform_name'] ?? 'Reviews Sorted';
$supportEmail = $settings['branding_support_email'] ?? $settings['general.support_email'] ?? 'support@sortedreviews.com';
$fromEmail = $settings['email_system_from_email'] ?? 'feedback@reviewssorted.com';
?>

<div class="rs-form-group rs-mb-4">
    <label for="branding_app_name" class="rs-label">Platform Application Name *</label>
    <input type="text" class="rs-input" id="branding_app_name" name="settings[branding_app_name]" value="<?= Html::encode($appName) ?>" required maxlength="64">
    <div class="rs-text-sm rs-text-muted rs-mt-1">Display branding name shown across platform headers, emails, and customer-facing interfaces.</div>
</div>

<div class="rs-form-group rs-mb-4">
    <label for="branding_support_email" class="rs-label">System Support Email *</label>
    <input type="email" class="rs-input" id="branding_support_email" name="settings[branding_support_email]" value="<?= Html::encode($supportEmail) ?>" required>
    <div class="rs-text-sm rs-text-muted rs-mt-1">Primary email address displayed for user help and customer inquiries.</div>
</div>

<div class="rs-form-group rs-mb-4">
    <label for="email_system_from_email" class="rs-label">System From Email *</label>
    <input type="email" class="rs-input" id="email_system_from_email" name="settings[email_system_from_email]" value="<?= Html::encode($fromEmail) ?>" required>
    <div class="rs-text-sm rs-text-muted rs-mt-1">Default sender address used for automated transactional emails and notifications.</div>
</div>
