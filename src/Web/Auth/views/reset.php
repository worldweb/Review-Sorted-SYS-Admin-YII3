<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Reset Password Form View (Yii 3 Modern Theme)
 * Rendered within Guest Layout.
 *
 * @var string $appName
 * @var string $title
 * @var string|null $error
 * @var string|null $csrfToken
 * @var string|null $token
 * @var Yiisoft\View\WebView $this
 */

$this->setTitle('Set New Password');
?>

<div class="rs-auth-layout">
    <div class="rs-auth-card">
        <?php if (!empty($error)): ?>
            <div class="rs-alert rs-alert--danger rs-mb-4" role="alert">
                <?= Html::encode($error) ?>
            </div>
        <?php endif; ?>

        <div class="rs-auth-header">
            <h1 class="rs-auth-title"><?= Html::encode($this->getTitle()) ?></h1>
            <p class="rs-auth-subtitle">Create a new secure password for your account.</p>
        </div>

        <form id="reset-password-form" method="post" action="/sys-yii3/reset-password" class="rs-form">
            <?php if (!empty($csrfToken)): ?>
                <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
            <?php endif; ?>
            <?php if (!empty($token)): ?>
                <input type="hidden" name="token" value="<?= Html::encode($token) ?>">
            <?php endif; ?>

            <div class="rs-form-group rs-mb-4">
                <label for="password" class="rs-label">New Password</label>
                <input type="password" id="password" name="password" class="rs-input" required autofocus placeholder="••••••••">
            </div>

            <div class="rs-form-group rs-mb-6">
                <label for="password_conf" class="rs-label">Confirm New Password</label>
                <input type="password" id="password_conf" name="password_conf" class="rs-input" required placeholder="••••••••">
            </div>

            <div class="rs-form-group rs-mb-4">
                <button type="submit" class="rs-btn rs-btn--primary rs-btn--block rs-btn--lg">Update Password</button>
            </div>

            <div class="rs-text-center rs-mt-4">
                <a href="/sys-yii3/login" class="rs-auth-link">Return to sign in</a>
            </div>
        </form>
    </div>
</div>
