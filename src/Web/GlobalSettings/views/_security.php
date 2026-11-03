<?php

declare(strict_types=1);

/**
 * @var array<string, ?string> $settings
 */
?>

<div class="mb-3">
    <label for="session_timeout" class="form-label fw-bold">Session Inactivity Timeout (minutes)</label>
    <input type="number" class="form-control" id="session_timeout" name="settings[security.session_timeout]" value="<?= htmlspecialchars($settings['security.session_timeout'] ?? '120') ?>">
</div>

<div class="mb-3">
    <label for="max_login_attempts" class="form-label fw-bold">Maximum Consecutive Login Failures Before Lockout</label>
    <input type="number" class="form-control" id="max_login_attempts" name="settings[security.max_login_attempts]" value="<?= htmlspecialchars($settings['security.max_login_attempts'] ?? '5') ?>">
</div>

<div class="form-check form-switch mb-3">
    <input class="form-check-input" type="checkbox" id="require_strong_passwords" name="settings[security.require_strong_passwords]" value="1" <?= ($settings['security.require_strong_passwords'] ?? '1') === '1' ? 'checked' : '' ?>>
    <label class="form-check-label fw-bold" for="require_strong_passwords">Enforce Strong Password Policy (min 8 chars, uppercase, digits)</label>
</div>

<div class="form-check form-switch mb-3">
    <input class="form-check-input" type="checkbox" id="enable_csrf_protection" name="settings[security.csrf_strict]" value="1" <?= ($settings['security.csrf_strict'] ?? '1') === '1' ? 'checked' : '' ?>>
    <label class="form-check-label fw-bold" for="enable_csrf_protection">Strict CSRF Validation on All State Mutation Endpoints</label>
</div>
