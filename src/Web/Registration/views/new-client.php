<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Client Registration View (Yii 3 Modern Theme)
 * Rendered within Guest Layout.
 *
 * @var string $appName
 * @var array $data
 * @var array $errors
 * @var string|null $infoMessage
 * @var string|null $csrfToken
 * @var Yiisoft\View\WebView $this
 */

$this->setTitle('Create Your Workspace');
?>
<div class="rs-auth-layout">
    <div class="rs-auth-card" style="max-width: 520px;">
        <div class="rs-auth-header">
            <h1 class="rs-auth-title"><?= Html::encode($appName) ?></h1>
            <p class="rs-auth-subtitle">Create your workspace and start collecting verified reviews.</p>
        </div>

        <?php if (!empty($infoMessage)): ?>
            <div class="rs-alert rs-alert--info rs-mb-4" role="alert">
                <svg class="rs-alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                <div class="rs-alert__content">
                    <?= Html::encode($infoMessage) ?>
                </div>
            </div>
        <?php endif; ?>

        <form method="post" action="/sys-yii3/new-client" class="rs-form">
            <?php if (!empty($csrfToken)): ?>
                <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
            <?php endif; ?>

            <div class="rs-form-group rs-mb-4">
                <label for="business_name" class="rs-label">Business Name <span class="rs-text-danger">*</span></label>
                <input type="text" id="business_name" name="business_name" class="rs-input <?= isset($errors['business_name']) ? 'rs-input--invalid' : '' ?>" value="<?= Html::encode($data['business_name'] ?? '') ?>" placeholder="e.g. Acme Dental Clinic" required>
                <?php if (isset($errors['business_name'])): ?>
                    <div class="rs-form-error rs-text-danger rs-font-size-xs rs-mt-1"><?= Html::encode($errors['business_name']) ?></div>
                <?php endif; ?>
            </div>

            <div class="rs-form-group rs-mb-4">
                <label for="first_name" class="rs-label">Your Name <span class="rs-text-danger">*</span></label>
                <input type="text" id="first_name" name="first_name" class="rs-input <?= isset($errors['first_name']) ? 'rs-input--invalid' : '' ?>" value="<?= Html::encode($data['first_name'] ?? '') ?>" placeholder="e.g. John Doe" required>
                <?php if (isset($errors['first_name'])): ?>
                    <div class="rs-form-error rs-text-danger rs-font-size-xs rs-mt-1"><?= Html::encode($errors['first_name']) ?></div>
                <?php endif; ?>
            </div>

            <div class="rs-form-group rs-mb-4">
                <label for="email" class="rs-label">Work Email <span class="rs-text-danger">*</span></label>
                <input type="email" id="email" name="email" class="rs-input <?= isset($errors['email']) ? 'rs-input--invalid' : '' ?>" value="<?= Html::encode($data['email'] ?? '') ?>" placeholder="john@company.com" required>
                <?php if (isset($errors['email'])): ?>
                    <div class="rs-form-error rs-text-danger rs-font-size-xs rs-mt-1"><?= Html::encode($errors['email']) ?></div>
                <?php endif; ?>
            </div>

            <div class="rs-form-group rs-mb-4">
                <label for="password" class="rs-label">Password <span class="rs-text-danger">*</span></label>
                <input type="password" id="password" name="password" class="rs-input <?= isset($errors['password']) ? 'rs-input--invalid' : '' ?>" placeholder="At least 6 characters" required>
                <?php if (isset($errors['password'])): ?>
                    <div class="rs-form-error rs-text-danger rs-font-size-xs rs-mt-1"><?= Html::encode($errors['password']) ?></div>
                <?php endif; ?>
            </div>

            <div class="rs-form-group rs-mb-6">
                <label for="password_conf" class="rs-label">Confirm Password <span class="rs-text-danger">*</span></label>
                <input type="password" id="password_conf" name="password_conf" class="rs-input <?= isset($errors['password_conf']) ? 'rs-input--invalid' : '' ?>" placeholder="Repeat your password" required>
                <?php if (isset($errors['password_conf'])): ?>
                    <div class="rs-form-error rs-text-danger rs-font-size-xs rs-mt-1"><?= Html::encode($errors['password_conf']) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="rs-btn rs-btn--primary rs-btn--block rs-btn--lg rs-mb-4">
                Create Workspace
            </button>

            <div class="rs-text-center rs-font-size-sm">
                <span class="rs-text-secondary">Already have an account?</span>
                <a href="/sys-yii3/login" class="rs-auth-link rs-ml-1">Sign in</a>
            </div>
        </form>
    </div>
</div>
