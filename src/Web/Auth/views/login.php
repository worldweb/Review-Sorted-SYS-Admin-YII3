<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Login View (Yii 3 Modern Theme)
 * Rendered within Guest Layout.
 *
 * @var string $appName
 * @var string $title
 * @var string $email
 * @var bool $rememberMe
 * @var string|null $error
 * @var bool $allowRegistration
 * @var string|null $csrfToken
 * @var Yiisoft\View\WebView $this
 */

$this->setTitle($title);

$eyeIcon = '<svg id="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
$eyeOffIcon = '<svg id="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';
?>
<div class="rs-auth-layout">
    <div class="rs-auth-card">
        <?php if (!empty($error)): ?>
            <div class="rs-alert rs-alert--danger" role="alert">
                <?= Html::encode($error) ?>
            </div>
        <?php endif; ?>

        <div class="rs-auth-header">
            <h1 class="rs-auth-title"><?= Html::encode($appName) ?></h1>
            <p class="rs-auth-subtitle"><?= Html::encode($title) ?></p>
        </div>

        <form id="login-form" method="post" action="/sys-yii3/login" class="rs-form">
            <?php if (!empty($csrfToken)): ?>
                <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
            <?php endif; ?>

            <div class="rs-form-group rs-mb-4">
                <label for="loginform-email" class="rs-label">Email</label>
                <input type="email" id="loginform-email" name="email" class="rs-input" value="<?= Html::encode($email) ?>" autofocus required placeholder="name@company.com">
            </div>

            <div class="rs-form-group rs-mb-4">
                <label for="loginform-password" class="rs-label">Password</label>
                <div class="rs-input-wrapper" style="position: relative;">
                    <input type="password" id="loginform-password" name="password" class="rs-input" required placeholder="••••••••" style="padding-right: 40px;">
                    <button type="button" id="toggle-password" class="rs-btn rs-btn--icon rs-btn--ghost" aria-label="Show password" aria-pressed="false" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:var(--rs-text-secondary, #7B8A8B); z-index:10; padding:4px;">
                        <?= $eyeIcon ?>
                        <?= $eyeOffIcon ?>
                    </button>
                </div>
            </div>

            <div class="rs-auth-actions rs-d-flex rs-align-items-center rs-justify-content-between rs-mb-4">
                <label class="rs-checkbox-wrap rs-d-flex rs-align-items-center rs-gap-2">
                    <input type="checkbox" name="rememberMe" value="1" class="rs-checkbox" <?= $rememberMe ? 'checked' : '' ?>>
                    <span class="rs-checkbox-label">Remember me</span>
                </label>
                <a href="/sys-yii3/forgot-password" class="rs-auth-link">Forgot password?</a>
            </div>

            <div class="rs-form-group rs-mb-4">
                <button type="submit" name="login-button" class="rs-btn rs-btn--primary rs-btn--block rs-btn--lg">Log In</button>
            </div>

            <?php if ($allowRegistration): ?>
                <div class="rs-text-center rs-font-size-sm">
                    <span class="rs-text-secondary">Don't have an account?</span>
                    <a href="/sys-yii3/new-client" class="rs-auth-link rs-ml-1">Create an account</a>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
    document.getElementById('toggle-password').addEventListener('click', function() {
        var passwordInput = document.getElementById('loginform-password');
        var iconEye = document.getElementById('icon-eye');
        var iconEyeOff = document.getElementById('icon-eye-off');
        var isPassword = passwordInput.type === 'password';
        
        passwordInput.type = isPassword ? 'text' : 'password';
        iconEye.style.display = isPassword ? 'none' : 'block';
        iconEyeOff.style.display = isPassword ? 'block' : 'none';
        this.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        this.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
    });
</script>
