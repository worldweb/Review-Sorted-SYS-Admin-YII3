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

$this->setTitle('Create your ' . $appName . ' account');

$eyeIcon = '<svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
$eyeOffIcon = '<svg class="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';
?>

<style>
    .new-client-page {
        min-height: 100vh;
        background-color: var(--rs-bg-app, #f9fafb);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        padding: 48px 20px 64px 20px;
        font-family: var(--rs-font-family, inherit);
        box-sizing: border-box;
    }
    .new-client-container {
        max-width: 768px;
        width: 100%;
        margin: 0 auto;
    }
    .new-client-brand {
        text-align: center;
        margin-bottom: var(--rs-space-8, 32px);
    }
    .new-client-brand-title {
        font-size: var(--rs-font-size-h3, 24px);
        font-weight: 800;
        color: var(--rs-text-main, #111827);
        letter-spacing: -0.025em;
        margin: 0;
    }
    .new-client-header {
        text-align: center;
        margin-bottom: var(--rs-space-8, 32px);
    }
    .new-client-title {
        font-size: var(--rs-font-size-h2, 30px);
        font-weight: 700;
        color: var(--rs-text-main, #111827);
        margin-bottom: var(--rs-space-2, 8px);
        line-height: 1.2;
    }
    .new-client-subtitle {
        font-size: var(--rs-font-size-body, 16px);
        color: var(--rs-text-muted, #6b7280);
    }
    
    .new-client-card {
        background: var(--rs-surface, #ffffff);
        border-radius: var(--rs-radius-lg, 12px);
        box-shadow: var(--rs-shadow-md, 0 4px 6px -1px rgba(0, 0, 0, 0.1));
        border: 1px solid var(--rs-border, #e5e7eb);
        overflow: hidden;
    }
    .new-client-card-inner {
        padding: var(--rs-space-12, 48px);
    }
    
    .new-client-section {
        margin-bottom: var(--rs-space-10, 40px);
        padding-bottom: var(--rs-space-10, 40px);
        border-bottom: 1px solid var(--rs-border, #e5e7eb);
    }
    .new-client-section:last-of-type {
        border-bottom: none;
        margin-bottom: var(--rs-space-8, 32px);
        padding-bottom: 0;
    }
    .new-client-section-header {
        margin-bottom: var(--rs-space-6, 24px);
    }
    .new-client-section-title {
        font-size: var(--rs-font-size-body-lg, 18px);
        font-weight: 600;
        color: var(--rs-text-main, #111827);
        display: flex;
        align-items: center;
    }
    .new-client-section-title::after {
        content: "";
        flex: 1;
        height: 1px;
        background: var(--rs-border, #e5e7eb);
        margin-left: var(--rs-space-4, 16px);
    }
    .new-client-section-desc {
        font-size: var(--rs-font-size-sm, 14px);
        color: var(--rs-text-muted, #6b7280);
        margin-top: var(--rs-space-1, 4px);
    }
    
    .new-client-upload {
        border: 2px dashed var(--rs-border-muted, #d1d5db);
        border-radius: var(--rs-radius-md, 8px);
        padding: var(--rs-space-8, 32px) var(--rs-space-5, 20px);
        text-align: center;
        background-color: var(--rs-bg-app, #f9fafb);
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
    }
    .new-client-upload:hover, .new-client-upload:focus-within {
        border-color: var(--rs-primary, #234137);
        background-color: var(--rs-surface, #ffffff);
    }
    .new-client-upload-icon {
        display: block;
        width: 48px;
        height: 48px;
        margin: 0 auto 16px;
        color: var(--rs-text-muted, #9ca3af);
    }
    .new-client-upload-text {
        font-size: 14px;
        font-weight: 600;
        color: var(--rs-text-main, #111827);
        margin-bottom: 4px;
        display: block;
    }
    .new-client-upload-subtext {
        font-size: 12px;
        color: var(--rs-text-muted, #6b7280);
        display: block;
    }
    .new-client-upload input[type="file"] {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }

    .new-client-footer {
        text-align: center;
        margin-top: 24px;
        font-size: 14px;
        color: var(--rs-text-muted, #6b7280);
    }
    .new-client-footer a {
        font-weight: 600;
        text-decoration: none;
        color: var(--rs-primary, #234137);
    }
    .new-client-footer a:hover {
        text-decoration: underline;
    }
</style>

<div class="new-client-page">
    <div class="new-client-container">
        
        <div class="new-client-brand">
            <h2 class="new-client-brand-title"><?= Html::encode($appName) ?></h2>
        </div>

        <div class="new-client-header">
            <h1 class="new-client-title"><?= Html::encode($this->getTitle()) ?></h1>
            <p class="new-client-subtitle">Get started by creating your business account and administrator profile.</p>
        </div>

        <?php if (!empty($infoMessage)): ?>
            <div class="rs-alert rs-alert--info rs-mb-4" role="alert">
                <?= Html::encode($infoMessage) ?>
            </div>
        <?php endif; ?>

        <div class="new-client-card">
            <div class="new-client-card-inner">
                <form method="post" action="<?= \App\Environment::url('/new-client') ?>" class="rs-form" enctype="multipart/form-data" id="form-signup">
                    <?php if (!empty($csrfToken)): ?>
                        <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
                    <?php endif; ?>

                    <!-- BUSINESS INFORMATION SECTION -->
                    <div class="new-client-section">
                        <div class="new-client-section-header">
                            <div class="new-client-section-title">Business Information</div>
                            <div class="new-client-section-desc">Tell us about your business.</div>
                        </div>
                        
                        <div class="rs-grid rs-grid--cols-1 rs-grid--cols-md-2 rs-gap-4">
                            <div style="grid-column: 1 / -1;" class="rs-form-group">
                                <label class="rs-label" for="business_name">Business Name <span class="rs-text-danger">*</span></label>
                                <input type="text" id="business_name" name="business_name" class="rs-input rs-w-100 <?= isset($errors['business_name']) ? 'rs-input--invalid' : '' ?>" value="<?= Html::encode($data['business_name'] ?? '') ?>" placeholder="Enter your business name" required autofocus>
                                <?php if (isset($errors['business_name'])): ?>
                                    <div class="rs-text-danger rs-font-size-xs rs-mt-1"><?= Html::encode($errors['business_name']) ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="rs-form-group">
                                <label class="rs-label" for="website">Website</label>
                                <input type="url" id="website" name="website" class="rs-input rs-w-100" value="<?= Html::encode($data['website'] ?? '') ?>" placeholder="https://example.com">
                            </div>
                            
                            <div class="rs-form-group">
                                <label class="rs-label" for="contact_number">Contact Number</label>
                                <input type="tel" id="contact_number" name="contact_number" class="rs-input rs-w-100" value="<?= Html::encode($data['contact_number'] ?? '') ?>" placeholder="Enter your contact number">
                            </div>
                        </div>
                    </div>

                    <!-- ACCOUNT ADMINISTRATOR SECTION -->
                    <div class="new-client-section">
                        <div class="new-client-section-header">
                            <div class="new-client-section-title">Account Administrator</div>
                            <div class="new-client-section-desc">Set up the administrator who will manage your <?= Html::encode($appName) ?> account.</div>
                        </div>
                        
                        <div class="rs-grid rs-grid--cols-1 rs-grid--cols-md-2 rs-gap-4">
                            <div class="rs-form-group">
                                <label class="rs-label" for="first_name">First Name <span class="rs-text-danger">*</span></label>
                                <input type="text" id="first_name" name="first_name" class="rs-input rs-w-100 <?= isset($errors['first_name']) ? 'rs-input--invalid' : '' ?>" value="<?= Html::encode($data['first_name'] ?? '') ?>" placeholder="Enter your first name" required>
                                <?php if (isset($errors['first_name'])): ?>
                                    <div class="rs-text-danger rs-font-size-xs rs-mt-1"><?= Html::encode($errors['first_name']) ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="rs-form-group">
                                <label class="rs-label" for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" class="rs-input rs-w-100" value="<?= Html::encode($data['last_name'] ?? '') ?>" placeholder="Enter your last name">
                            </div>
                            
                            <div style="grid-column: 1 / -1;" class="rs-form-group">
                                <label class="rs-label" for="email">Work Email <span class="rs-text-danger">*</span></label>
                                <input type="email" id="email" name="email" class="rs-input rs-w-100 <?= isset($errors['email']) ? 'rs-input--invalid' : '' ?>" value="<?= Html::encode($data['email'] ?? '') ?>" placeholder="Enter your email address" required>
                                <div class="rs-text-muted rs-mt-1" style="font-size: 12px;">Used to sign in to your account.</div>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="rs-text-danger rs-font-size-xs rs-mt-1"><?= Html::encode($errors['email']) ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="rs-form-group">
                                <label class="rs-label" for="clientregistrationform-password">Password <span class="rs-text-danger">*</span></label>
                                <div style="position: relative;">
                                    <input type="password" id="clientregistrationform-password" name="password" class="rs-input rs-w-100 <?= isset($errors['password']) ? 'rs-input--invalid' : '' ?>" placeholder="Create a password" required style="padding-right: 40px;">
                                    <button type="button" class="rs-btn rs-btn--icon rs-btn--ghost toggle-password" data-target="clientregistrationform-password" aria-label="Show password" aria-pressed="false" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); color: var(--rs-text-muted); background: transparent; border: none; cursor: pointer;">
                                        <?= $eyeIcon ?><?= $eyeOffIcon ?>
                                    </button>
                                </div>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="rs-text-danger rs-font-size-xs rs-mt-1"><?= Html::encode($errors['password']) ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="rs-form-group">
                                <label class="rs-label" for="clientregistrationform-password_conf">Confirm Password <span class="rs-text-danger">*</span></label>
                                <div style="position: relative;">
                                    <input type="password" id="clientregistrationform-password_conf" name="password_conf" class="rs-input rs-w-100 <?= isset($errors['password_conf']) ? 'rs-input--invalid' : '' ?>" placeholder="Confirm your password" required style="padding-right: 40px;">
                                    <button type="button" class="rs-btn rs-btn--icon rs-btn--ghost toggle-password" data-target="clientregistrationform-password_conf" aria-label="Show password" aria-pressed="false" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); color: var(--rs-text-muted); background: transparent; border: none; cursor: pointer;">
                                        <?= $eyeIcon ?><?= $eyeOffIcon ?>
                                    </button>
                                </div>
                                <?php if (isset($errors['password_conf'])): ?>
                                    <div class="rs-text-danger rs-font-size-xs rs-mt-1"><?= Html::encode($errors['password_conf']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- ABOUT YOUR BUSINESS SECTION -->
                    <div class="new-client-section">
                        <div class="new-client-section-header">
                            <div class="new-client-section-title">About Your Business</div>
                            <div class="new-client-section-desc">Add optional information to help complete your business profile.</div>
                        </div>
                        
                        <div class="rs-form-group rs-mb-4">
                            <label class="rs-label" for="biography">Business Description</label>
                            <textarea id="biography" name="biography" class="rs-input rs-textarea-input rs-w-100" rows="4" placeholder="Tell us a little about your business..."><?= Html::encode($data['biography'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="rs-form-group rs-mb-0">
                            <label class="rs-label">Business Logo</label>
                            <div class="new-client-upload">
                                <svg class="new-client-upload-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                </svg>
                                <span class="new-client-upload-text">Upload Logo</span>
                                <span class="new-client-upload-subtext">Drag &amp; drop your logo here or click to browse</span>
                                <span class="new-client-upload-subtext" style="margin-top: 8px;">PNG, JPG or GIF</span>
                                
                                <input type="file" name="logo" class="new-client-upload-input" accept="image/*" onchange="document.getElementById('upload-filename').textContent = this.files[0] ? this.files[0].name : '';">
                            </div>
                            <div id="upload-filename" style="margin-top: 8px; font-size: 12px; color: #111827; text-align: center; font-weight: 500;"></div>
                        </div>
                    </div>

                    <div style="margin-top: 24px;">
                        <button type="submit" class="rs-btn rs-btn--primary rs-btn--block rs-btn--lg" style="width: 100%; padding: 14px; font-size: 16px; font-weight: 600;">
                            Create Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="new-client-footer">
            Already have an account? 
            <a href="<?= \App\Environment::url('/login') ?>" class="rs-auth-link">Sign in</a>
        </div>
        
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var toggleButtons = document.querySelectorAll('.toggle-password');
    toggleButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            var passwordInput = document.getElementById(targetId);
            var iconEye = this.querySelector('.icon-eye');
            var iconEyeOff = this.querySelector('.icon-eye-off');
            var isPassword = passwordInput.type === 'password';
            
            passwordInput.type = isPassword ? 'text' : 'password';
            iconEye.style.display = isPassword ? 'none' : 'block';
            iconEyeOff.style.display = isPassword ? 'block' : 'none';
            this.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            this.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
        });
    });
});
</script>
