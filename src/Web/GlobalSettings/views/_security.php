<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var array<string, ?string> $settings
 */
?>

<div class="rs-form-group rs-mb-4">
    <label for="session_timeout" class="rs-label">Session Inactivity Timeout (minutes)</label>
    <input type="number" class="rs-input" id="session_timeout" name="settings[security.session_timeout]" value="<?= Html::encode($settings['security.session_timeout'] ?? '120') ?>">
</div>

<div class="rs-form-group rs-mb-4">
    <label for="max_login_attempts" class="rs-label">Maximum Consecutive Login Failures Before Lockout</label>
    <input type="number" class="rs-input" id="max_login_attempts" name="settings[security.max_login_attempts]" value="<?= Html::encode($settings['security.max_login_attempts'] ?? '5') ?>">
</div>

<div class="rs-form-group rs-mb-4">
    <label class="rs-checkbox-wrap rs-d-flex rs-align-items-center rs-gap-2">
        <input class="rs-checkbox" type="checkbox" id="require_strong_passwords" name="settings[security.require_strong_passwords]" value="1" <?= ($settings['security.require_strong_passwords'] ?? '1') === '1' ? 'checked' : '' ?>>
        <span class="rs-checkbox-label rs-font-semibold">Enforce Strong Password Policy (min 8 chars, uppercase, digits)</span>
    </label>
</div>

<div class="rs-form-group rs-mb-4">
    <label class="rs-checkbox-wrap rs-d-flex rs-align-items-center rs-gap-2">
        <input class="rs-checkbox" type="checkbox" id="enable_csrf_protection" name="settings[security.csrf_strict]" value="1" <?= ($settings['security.csrf_strict'] ?? '1') === '1' ? 'checked' : '' ?>>
        <span class="rs-checkbox-label rs-font-semibold">Strict CSRF Validation on All State Mutation Endpoints</span>
    </label>
</div>
