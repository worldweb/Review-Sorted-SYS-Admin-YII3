<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var App\Domain\SocialConnection\ServiceConfig $model
 * @var string $csrfToken
 * @var Yiisoft\Session\Flash\Flash $flash
 */

$this->setTitle('Edit Facebook Connection: ' . $model->getDisplayName());
?>

<div class="rs-container">
    <?= $this->render('../partials/_header', [
        'title' => 'Edit Settings: ' . $model->getDisplayName(),
        'subtitle' => 'Update internal labels and alert notification settings.'
    ]) ?>

    <?php if ($flash->has('error')): ?>
        <div class="rs-alert rs-alert--danger rs-mb-4" role="alert">
            <?= Html::encode((string) $flash->get('error')) ?>
        </div>
    <?php endif; ?>

    <div class="rs-card rs-card--shadow" style="background:#fff; border-radius:8px; border:1px solid #e2e8f0; max-width: 700px;">
        <?= $this->render('../partials/_card_header', [
            'title' => 'Facebook Connection Configuration',
            'subtitle' => 'Configure alerts and display preferences'
        ]) ?>
        <div class="rs-card__body rs-p-4" style="padding: 1.5rem;">
            <form method="post" action="/sys-yii3/settings/facebook/update?id=<?= $model->getId() ?>">
                <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">

                <div class="rs-form-group rs-mb-4" style="margin-bottom: 1.25rem;">
                    <label for="label" class="rs-label" style="display:block; font-weight:600; font-size:0.875rem; margin-bottom:0.35rem; color:#1e293b;">
                        Internal Label <span style="color:#ef4444;">*</span>
                    </label>
                    <input type="text" class="rs-input" id="label" name="label" value="<?= Html::encode($model->getLabel()) ?>" required style="width:100%; padding:0.6rem 0.85rem; border:1px solid #cbd5e1; border-radius:6px; font-size:0.95rem;">
                    <div style="font-size:0.75rem; color:#64748b; margin-top:0.3rem;">Custom name to identify this page connection internally.</div>
                </div>

                <div class="rs-form-group rs-mb-4" style="margin-bottom: 1.25rem;">
                    <label for="email_low_review" class="rs-label" style="display:block; font-weight:600; font-size:0.875rem; margin-bottom:0.35rem; color:#1e293b;">
                        Low Rating Notification Email
                    </label>
                    <input type="email" class="rs-input" id="email_low_review" name="email_low_review" value="<?= Html::encode($model->getEmailLowReview() ?? '') ?>" placeholder="manager@example.com" style="width:100%; padding:0.6rem 0.85rem; border:1px solid #cbd5e1; border-radius:6px; font-size:0.95rem;">
                    <div style="font-size:0.75rem; color:#64748b; margin-top:0.3rem;">An email address that receives instant notifications when low reviews are received.</div>
                </div>

                <div class="rs-form-group rs-mb-4" style="margin-bottom: 1.25rem;">
                    <label class="rs-label" style="display:block; font-weight:600; font-size:0.875rem; margin-bottom:0.35rem; color:#64748b;">
                        Remote Facebook Page Name
                    </label>
                    <input type="text" class="rs-input" value="<?= Html::encode($model->getPageName() ?? 'N/A') ?>" readonly disabled style="width:100%; padding:0.6rem 0.85rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; font-size:0.95rem; color:#64748b;">
                </div>

                <div class="rs-form-group rs-mb-4" style="margin-bottom: 1.5rem;">
                    <label class="rs-label" style="display:block; font-weight:600; font-size:0.875rem; margin-bottom:0.35rem; color:#64748b;">
                        Remote Page ID
                    </label>
                    <input type="text" class="rs-input" value="<?= Html::encode($model->getPageId() ?? 'N/A') ?>" readonly disabled style="width:100%; padding:0.6rem 0.85rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; font-family:monospace; font-size:0.95rem; color:#64748b;">
                </div>

                <div style="display:flex; justify-content:flex-end; gap:0.5rem; padding-top:1rem; border-top:1px solid #e2e8f0;">
                    <a href="/sys-yii3/settings/facebook" class="rs-btn rs-btn--secondary">Cancel</a>
                    <button type="submit" class="rs-btn rs-btn--primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
