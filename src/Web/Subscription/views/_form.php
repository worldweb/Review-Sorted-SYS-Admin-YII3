<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Subscription Plan Form Partial (Yii 3 Modern Theme)
 *
 * @var App\Domain\Subscription\Plan|null $model
 * @var string $csrfToken
 */

$isNew = $model === null;
$actionUrl = $isNew ? \App\Environment::url('/subscription/create') : \App\Environment::url('/subscription/update?id=' . $model->getId());
?>

<div class="rs-card rs-form-card" style="max-width: 900px;">
    <div class="rs-card__header">
        <h3 class="rs-card__title">Subscription Plan Properties</h3>
    </div>
    
    <div class="rs-card__body rs-p-6" style="padding: 24px;">
        <form method="post" action="<?= $actionUrl ?>" class="rs-form">
            <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">

            <div class="rs-grid rs-grid--cols-1 rs-grid--cols-md-2 rs-gap-4 rs-mb-4" style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--rs-space-4, 16px); margin-bottom: var(--rs-space-4, 16px);">
                <div class="rs-form-group">
                    <label for="plan_name" class="rs-label rs-font-semibold rs-mb-2" style="font-weight: 600; display: block; margin-bottom: 6px;">Plan Name <span class="rs-text-danger">*</span></label>
                    <input type="text" class="rs-input rs-w-100" id="plan_name" name="plan_name" value="<?= Html::encode($model?->getPlanName() ?? '') ?>" placeholder="e.g. Enterprise Pro" required maxlength="255">
                </div>
                <div class="rs-form-group">
                    <label for="plan_price" class="rs-label rs-font-semibold rs-mb-2" style="font-weight: 600; display: block; margin-bottom: 6px;">Plan Price ($) <span class="rs-text-danger">*</span></label>
                    <input type="text" class="rs-input rs-w-100" id="plan_price" name="plan_price" value="<?= Html::encode($model?->getPlanPrice() ?? '0.00') ?>" placeholder="e.g. 99.00" required maxlength="255">
                </div>
            </div>

            <div class="rs-grid rs-grid--cols-1 rs-grid--cols-md-2 rs-gap-4 rs-mb-4" style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--rs-space-4, 16px); margin-bottom: var(--rs-space-4, 16px);">
                <div class="rs-form-group">
                    <label for="plan_limit" class="rs-label rs-font-semibold rs-mb-2" style="font-weight: 600; display: block; margin-bottom: 6px;">Billing Cycle / Limit <span class="rs-text-danger">*</span></label>
                    <input type="text" class="rs-input rs-w-100" id="plan_limit" name="plan_limit" value="<?= Html::encode($model?->getPlanLimit() ?? 'month') ?>" placeholder="e.g. month, year" required maxlength="255">
                </div>
                <div class="rs-form-group">
                    <label for="status" class="rs-label rs-font-semibold rs-mb-2" style="font-weight: 600; display: block; margin-bottom: 6px;">Status</label>
                    <select class="rs-input rs-w-100" id="status" name="status">
                        <option value="1" <?= ($model?->getStatus() ?? '1') === '1' ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= ($model?->getStatus() ?? '1') === '0' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="rs-form-group rs-mb-4" style="margin-bottom: var(--rs-space-4, 16px);">
                <label for="plan_features" class="rs-label rs-font-semibold rs-mb-2" style="font-weight: 600; display: block; margin-bottom: 6px;">Plan Features</label>
                <textarea class="rs-input rs-textarea-input rs-w-100" id="plan_features" name="plan_features" rows="5" placeholder="Feature 1, Feature 2, Feature 3..." style="width: 100%; box-sizing: border-box;"><?= Html::encode($model?->getPlanFeatures() ?? '') ?></textarea>
                <div class="rs-text-muted rs-mt-1" style="font-size: 0.85rem; color: var(--rs-text-muted, #64748b); margin-top: 4px;">
                    Enter features as a comma-separated list. (e.g. "Unlimited Reviews, 24/7 Support, API Access")
                </div>
            </div>

            <div class="mod-card-footer" style="margin-top: var(--rs-space-5, 20px); display: flex; gap: var(--rs-space-3, 12px);">
                <button type="submit" class="rs-btn rs-btn--primary">Save Plan</button>
                <a href="<?= \App\Environment::url('/subscription') ?>" class="rs-btn rs-btn--secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
