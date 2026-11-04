<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var array<string, ?string> $settings
 */
$allowRegistration = (int) ($settings['app_allow_client_self_registration'] ?? '1');
$passwordMinLength = (int) ($settings['security_password_min_length'] ?? '6');
$resetExpireHours = (int) ($settings['security_password_reset_token_expire_hours'] ?? '1');
?>

<div class="rs-form-group rs-mb-4">
    <label for="app_allow_client_self_registration" class="rs-label">Client Self-Registration</label>
    <select class="rs-input rs-select" id="app_allow_client_self_registration" name="settings[app_allow_client_self_registration]">
        <option value="1" <?= $allowRegistration === 1 ? 'selected' : '' ?>>Enabled</option>
        <option value="0" <?= $allowRegistration === 0 ? 'selected' : '' ?>>Disabled</option>
    </select>
    <div class="rs-text-sm rs-text-muted rs-mt-1">Allow new business clients to register accounts via the public sign-up form.</div>
</div>

<div class="rs-form-group rs-mb-4">
    <label for="security_password_min_length" class="rs-label">Minimum Password Length (characters)</label>
    <input type="number" min="6" max="32" class="rs-input" id="security_password_min_length" name="settings[security_password_min_length]" value="<?= $passwordMinLength ?>" required>
    <div class="rs-text-sm rs-text-muted rs-mt-1">Minimum character length required for user account passwords (between 6 and 32).</div>
</div>

<div class="rs-form-group rs-mb-4">
    <label for="security_password_reset_token_expire_hours" class="rs-label">Password Reset Token Expiration (hours)</label>
    <input type="number" min="1" max="72" class="rs-input" id="security_password_reset_token_expire_hours" name="settings[security_password_reset_token_expire_hours]" value="<?= $resetExpireHours ?>" required>
    <div class="rs-text-sm rs-text-muted rs-mt-1">Hours before a password reset link automatically expires (between 1 and 72).</div>
</div>
