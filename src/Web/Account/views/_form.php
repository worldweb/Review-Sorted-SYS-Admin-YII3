<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Account / Workspace Form Partial (Yii 3 Modern Theme)
 * Perfectly aligned with Yii 2 themes/modern/views/accounts/_form.php
 *
 * @var array $data
 * @var array $errors
 * @var string|null $csrfToken
 * @var \App\Domain\User\User|null $currentUser
 */

$isAdmin = isset($currentUser) && $currentUser->isAdmin();
?>

<div class="rs-card" style="max-width: 1000px;">
    <div class="rs-card__header">
        <h3 class="rs-card__title">Workspace Configuration</h3>
    </div>
    
    <form method="post" enctype="multipart/form-data" class="rs-form">
        <?php if (!empty($csrfToken)): ?>
            <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
        <?php endif; ?>

        <div class="rs-card__body rs-p-5" style="padding: 24px;">
            
            <h4 class="rs-text-md rs-mb-4" style="font-size: 16px; font-weight: 600; margin-top: 0; margin-bottom: 16px; color: #1e293b;">General Information</h4>
            
            <div class="rs-row rs-gap-5 rs-mb-3" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 16px;">
                <div class="rs-col rs-col-6">
                    <div class="rs-form-group">
                        <label for="acc-business-name" class="rs-label" style="font-weight: 600; display: block; margin-bottom: 6px;">Business Name <span class="rs-text-danger">*</span></label>
                        <input type="text" id="acc-business-name" name="business_name" class="rs-input <?= isset($errors['business_name']) ? 'rs-input--error' : '' ?>" value="<?= Html::encode((string) ($data['business_name'] ?? '')) ?>" required placeholder="e.g. Acme Corp" style="width: 100%;">
                        <?php if (isset($errors['business_name'])): ?>
                            <div class="rs-help-block rs-text-danger" style="font-size: 12px; color: #dc2626; margin-top: 4px;"><?= Html::encode($errors['business_name']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="rs-col rs-col-6">
                    <div class="rs-form-group">
                        <label for="acc-website" class="rs-label" style="font-weight: 600; display: block; margin-bottom: 6px;">Website</label>
                        <input type="url" id="acc-website" name="website" class="rs-input" value="<?= Html::encode((string) ($data['website'] ?? '')) ?>" placeholder="e.g. https://acme.com" style="width: 100%;">
                    </div>
                </div>
            </div>

            <div class="rs-row rs-gap-5 rs-mb-3" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 16px;">
                <div class="rs-col rs-col-6">
                    <div class="rs-form-group">
                        <label for="acc-first-name" class="rs-label" style="font-weight: 600; display: block; margin-bottom: 6px;">First Name</label>
                        <input type="text" id="acc-first-name" name="first_name" class="rs-input" value="<?= Html::encode((string) ($data['first_name'] ?? '')) ?>" placeholder="John" style="width: 100%;">
                    </div>
                </div>
                <div class="rs-col rs-col-6">
                    <div class="rs-form-group">
                        <label for="acc-last-name" class="rs-label" style="font-weight: 600; display: block; margin-bottom: 6px;">Last Name</label>
                        <input type="text" id="acc-last-name" name="last_name" class="rs-input" value="<?= Html::encode((string) ($data['last_name'] ?? '')) ?>" placeholder="Doe" style="width: 100%;">
                    </div>
                </div>
            </div>

            <div class="rs-row rs-gap-5 rs-mb-3" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 16px;">
                <div class="rs-col rs-col-6">
                    <div class="rs-form-group">
                        <label for="acc-phone" class="rs-label" style="font-weight: 600; display: block; margin-bottom: 6px;">Contact Number</label>
                        <input type="text" id="acc-phone" name="contact_number" class="rs-input" value="<?= Html::encode((string) ($data['contact_number'] ?? '')) ?>" placeholder="+1 555-0123" style="width: 100%;">
                    </div>
                </div>
                <div class="rs-col rs-col-6">
                    <div class="rs-form-group">
                        <label for="acc-email" class="rs-label" style="font-weight: 600; display: block; margin-bottom: 6px;">Email</label>
                        <input type="email" id="acc-email" name="email" class="rs-input" value="<?= Html::encode((string) ($data['email'] ?? '')) ?>" placeholder="contact@acme.com" style="width: 100%;">
                    </div>
                </div>
            </div>

            <div class="rs-row rs-gap-5 rs-mb-3" style="margin-bottom: 16px;">
                <div class="rs-col rs-col-12">
                    <div class="rs-form-group">
                        <label for="acc-biography" class="rs-label" style="font-weight: 600; display: block; margin-bottom: 6px;">Biography</label>
                        <textarea id="acc-biography" name="biography" class="rs-textarea rs-input" rows="6" style="width: 100%; box-sizing: border-box;"><?= Html::encode((string) ($data['biography'] ?? '')) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="rs-row rs-gap-5 rs-mb-3" style="margin-bottom: 16px;">
                <div class="rs-col rs-col-12">
                    <div class="rs-form-group">
                        <label for="acc-logo" class="rs-label" style="font-weight: 600; display: block; margin-bottom: 6px;">Logo</label>
                        <input type="file" id="acc-logo" name="logo" class="rs-input" style="width: 100%; box-sizing: border-box;">
                    </div>
                </div>
            </div>
            
            <div class="rs-divider rs-my-5" style="border-top: 1px solid var(--rs-border, #e2e8f0); margin: 24px 0;"></div>
            
            <h4 class="rs-text-md rs-mb-4" style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: #1e293b;">Resource Allocation &amp; Features</h4>
            
            <div class="rs-row rs-gap-5 rs-mb-3" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 16px;">
                <div class="rs-col rs-col-4">
                    <div class="rs-form-group">
                        <label for="acc-max-users" class="rs-label" style="font-weight: 600; display: block; margin-bottom: 6px;">Max Users</label>
                        <input type="number" id="acc-max-users" name="max_users" class="rs-input" min="1" max="1000" value="<?= Html::encode((string) ($data['max_users'] ?? 5)) ?>" style="width: 100%;">
                    </div>
                </div>
                
                <div class="rs-col rs-col-4">
                    <div class="rs-form-group">
                        <label for="acc-review-follow-up" class="rs-label" style="font-weight: 600; display: block; margin-bottom: 6px;">Review Follow Up</label>
                        <select id="acc-review-follow-up" name="review_follow_up" class="rs-input rs-select" style="width: 100%;">
                            <option value="0" <?= ((int) ($data['review_follow_up'] ?? 0)) === 0 ? 'selected' : '' ?>>No</option>
                            <option value="1" <?= ((int) ($data['review_follow_up'] ?? 0)) === 1 ? 'selected' : '' ?>>Yes</option>
                        </select>
                    </div>
                </div>
                
                <div class="rs-col rs-col-4">
                    <div class="rs-form-group">
                        <label for="acc-status" class="rs-label" style="font-weight: 600; display: block; margin-bottom: 6px;">Status</label>
                        <select id="acc-status" name="status" class="rs-input rs-select" style="width: 100%;">
                            <option value="1" <?= ((int) ($data['status'] ?? 1)) === 1 ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= ((int) ($data['status'] ?? 1)) === 0 ? 'selected' : '' ?>>Disabled</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="rs-row rs-gap-5 rs-mb-3" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 16px;">
                <div class="rs-col rs-col-4">
                    <div class="rs-form-group">
                        <label for="acc-social-media" class="rs-label" style="font-weight: 600; display: block; margin-bottom: 6px;">Social Media</label>
                        <select id="acc-social-media" name="social_media" class="rs-input rs-select" style="width: 100%;">
                            <option value="0" <?= ((int) ($data['social_media'] ?? 0)) === 0 ? 'selected' : '' ?>>No</option>
                            <option value="1" <?= ((int) ($data['social_media'] ?? 0)) === 1 ? 'selected' : '' ?>>Yes</option>
                        </select>
                    </div>
                </div>
                <div class="rs-col rs-col-4">
                    <div class="rs-form-group">
                        <label for="acc-email-sender" class="rs-label" style="font-weight: 600; display: block; margin-bottom: 6px;">Email Sender</label>
                        <select id="acc-email-sender" name="email_sender" class="rs-input rs-select" style="width: 100%;">
                            <option value="0" <?= ((int) ($data['email_sender'] ?? 0)) === 0 ? 'selected' : '' ?>>No</option>
                            <option value="1" <?= ((int) ($data['email_sender'] ?? 0)) === 1 ? 'selected' : '' ?>>Yes</option>
                        </select>
                    </div>
                </div>
                <div class="rs-col rs-col-4">
                    <div class="rs-form-group">
                        <label for="acc-qr-code" class="rs-label" style="font-weight: 600; display: block; margin-bottom: 6px;">QR Code</label>
                        <select id="acc-qr-code" name="qr_code" class="rs-input rs-select" style="width: 100%;">
                            <option value="0" <?= ((int) ($data['qr_code'] ?? 0)) === 0 ? 'selected' : '' ?>>No</option>
                            <option value="1" <?= ((int) ($data['qr_code'] ?? 0)) === 1 ? 'selected' : '' ?>>Yes</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="rs-row rs-gap-5 rs-mb-3" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 16px;">
                <div class="rs-col rs-col-4">
                    <div class="rs-form-group">
                        <label for="acc-social-posting" class="rs-label" style="font-weight: 600; display: block; margin-bottom: 6px;">Social Posting</label>
                        <select id="acc-social-posting" name="social_posting" class="rs-input rs-select" style="width: 100%;">
                            <option value="0" <?= ((int) ($data['social_posting'] ?? 0)) === 0 ? 'selected' : '' ?>>No</option>
                            <option value="1" <?= ((int) ($data['social_posting'] ?? 0)) === 1 ? 'selected' : '' ?>>Yes</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="rs-row rs-gap-5 rs-mb-3" style="margin-bottom: 16px;">
                <div class="rs-col rs-col-12">
                    <div class="rs-form-group">
                        <label for="acc-activation-key" class="rs-label" style="font-weight: 600; display: block; margin-bottom: 6px;">Activation Key</label>
                        <input type="text" id="acc-activation-key" name="activation_key" class="rs-input" value="<?= Html::encode((string) ($data['activation_key'] ?? '')) ?>" style="width: 100%;">
                    </div>
                </div>
            </div>

            <div class="rs-divider rs-my-5" style="border-top: 1px solid var(--rs-border, #e2e8f0); margin: 24px 0;"></div>
            
            <h4 class="rs-text-md rs-mb-4" style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: #1e293b;">API Configurations</h4>
            
            <div class="rs-row rs-gap-5 rs-mb-3" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 16px;">
                <div class="rs-col rs-col-6">
                    <div class="rs-form-group">
                        <label for="acc-facebook-api" class="rs-label" style="font-weight: 600; display: block; margin-bottom: 6px;">Facebook API</label>
                        <select id="acc-facebook-api" name="facebook_api" class="rs-input rs-select" style="width: 100%;">
                            <option value="">Default Application</option>
                        </select>
                    </div>
                </div>
                <div class="rs-col rs-col-6">
                    <div class="rs-form-group">
                        <label for="acc-google-api" class="rs-label" style="font-weight: 600; display: block; margin-bottom: 6px;">Google API</label>
                        <select id="acc-google-api" name="google_api" class="rs-input rs-select" style="width: 100%;">
                            <option value="">Default Application</option>
                        </select>
                    </div>
                </div>
            </div>

        </div>

        <div class="rs-card__footer rs-d-flex rs-justify-content-end rs-gap-3" style="padding: 16px 24px; border-top: 1px solid var(--rs-border, #e2e8f0); display: flex; justify-content: flex-end; gap: 12px;">
            <a href="<?= \App\Environment::url('/accounts') ?>" class="rs-btn rs-btn--outline" style="text-decoration: none;">Cancel</a>
            <button type="submit" class="rs-btn rs-btn--primary">Save</button>
        </div>
    </form>
</div>
