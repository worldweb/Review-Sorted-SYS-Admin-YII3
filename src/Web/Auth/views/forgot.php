<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Forgot Password View (Yii 3 Modern Theme)
 * Rendered within Guest Layout.
 *
 * @var string $appName
 * @var string $title
 * @var string|null $error
 * @var string|null $success
 * @var string|null $csrfToken
 * @var Yiisoft\View\WebView $this
 */

$this->setTitle('Reset Password');
?>

<div class="rs-auth-layout">
    <div class="rs-auth-card">
        <?php if (!empty($error)): ?>
            <div class="rs-alert rs-alert--danger rs-mb-4" role="alert">
                <?= Html::encode($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="rs-alert rs-alert--success rs-mb-4" role="alert">
                <?= Html::encode($success) ?>
            </div>
        <?php endif; ?>

        <div class="rs-auth-header">
            <h1 class="rs-auth-title"><?= Html::encode($appName ?? 'Reviews Sorted') ?></h1>
            <p class="rs-auth-subtitle">Enter your email address and we will send you a link to reset your password.</p>
        </div>

        <form id="request-password-reset-form" method="post" action="/sys-yii3/forgot-password" class="rs-form">
            <?php if (!empty($csrfToken)): ?>
                <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
            <?php endif; ?>

            <div class="rs-form-group rs-mb-6">
                <label for="reset-email" class="rs-label">Email Address</label>
                <input type="email" id="reset-email" name="email" class="rs-input" required autofocus placeholder="name@company.com">
            </div>

            <div class="rs-form-group rs-mb-4">
                <button type="submit" class="rs-btn rs-btn--primary rs-btn--block rs-btn--lg">Send Reset Link</button>
            </div>

            <div class="rs-text-center rs-mt-4">
                <a href="/sys-yii3/login" class="rs-auth-link">Return to sign in</a>
            </div>
        </form>
    </div>
</div>
