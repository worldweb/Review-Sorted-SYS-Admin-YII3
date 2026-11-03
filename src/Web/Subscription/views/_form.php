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
$actionUrl = $isNew ? '/sys-yii3/subscription/create' : '/sys-yii3/subscription/update?id=' . $model->getId();
?>

<div class="rs-card rs-form-card">
    <div class="rs-card__header">
        <h3 class="rs-card__title">Subscription Plan Properties</h3>
    </div>
    
    <div class="rs-card__body rs-p-6">
        <form method="post" action="<?= $actionUrl ?>" class="rs-form">
            <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">

            <div class="rs-grid rs-grid--cols-1 rs-grid--cols-md-2 rs-gap-4 rs-mb-4">
                <div class="rs-form-group">
                    <label for="plan_name" class="rs-label rs-font-semibold rs-mb-2">Plan Name <span class="rs-text-danger">*</span></label>
                    <input type="text" class="rs-input rs-w-100" id="plan_name" name="plan_name" value="<?= Html::encode($model?->getPlanName() ?? '') ?>" placeholder="e.g. Enterprise Pro" required maxlength="255">
                </div>
                <div class="rs-form-group">
                    <label for="plan_price" class="rs-label rs-font-semibold rs-mb-2">Plan Price ($) <span class="rs-text-danger">*</span></label>
                    <input type="text" class="rs-input rs-w-100" id="plan_price" name="plan_price" value="<?= Html::encode($model?->getPlanPrice() ?? '0.00') ?>" placeholder="e.g. 99.00" required maxlength="255">
                </div>
            </div>

            <div class="rs-grid rs-grid--cols-1 rs-grid--cols-md-2 rs-gap-4 rs-mb-4">
                <div class="rs-form-group">
                    <label for="plan_limit" class="rs-label rs-font-semibold rs-mb-2">Billing Cycle / Limit <span class="rs-text-danger">*</span></label>
                    <input type="text" class="rs-input rs-w-100" id="plan_limit" name="plan_limit" value="<?= Html::encode($model?->getPlanLimit() ?? 'month') ?>" placeholder="e.g. month, year" required maxlength="255">
                </div>
                <div class="rs-form-group">
                    <label for="status" class="rs-label rs-font-semibold rs-mb-2">Status</label>
                    <select class="rs-input rs-w-100" id="status" name="status">
                        <option value="1" <?= ($model?->getStatus() ?? '1') === '1' ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= ($model?->getStatus() ?? '1') === '0' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="rs-form-group rs-mb-4">
                <label for="plan_features" class="rs-label rs-font-semibold rs-mb-2">Plan Features</label>
                <textarea class="rs-input rs-textarea-input rs-w-100" id="plan_features" name="plan_features" rows="5" placeholder="Feature 1, Feature 2, Feature 3..."><?= Html::encode($model?->getPlanFeatures() ?? '') ?></textarea>
                <div class="rs-text-muted rs-mt-1" style="font-size: 0.85rem;">
                    Enter features as a comma-separated list. (e.g. "Unlimited Reviews, 24/7 Support, API Access")
                </div>
            </div>

            <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mt-6 rs-pt-4 rs-border-top" style="border-top: 1px solid var(--rs-border, #E2E8F0);">
                <a href="/sys-yii3/subscription" class="rs-btn rs-btn--secondary">Cancel</a>
                <button type="submit" class="rs-btn rs-btn--primary rs-px-6">Save Plan</button>
            </div>
        </form>
    </div>
</div>
