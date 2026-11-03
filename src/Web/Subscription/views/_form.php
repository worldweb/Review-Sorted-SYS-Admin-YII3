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

<form method="post" action="<?= $actionUrl ?>" class="rs-form">
    <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 20px;">
        <div class="rs-form-group">
            <label for="plan_name" class="rs-label">Plan Name <span class="rs-text-danger">*</span></label>
            <input type="text" class="rs-input" id="plan_name" name="plan_name" value="<?= Html::encode($model?->getPlanName() ?? '') ?>" placeholder="e.g. Starter, Pro, Enterprise" required>
        </div>
        <div class="rs-form-group">
            <label for="plan_price" class="rs-label">Monthly Price ($) <span class="rs-text-danger">*</span></label>
            <div class="rs-input-group" style="display: flex;">
                <span style="display: inline-flex; align-items: center; padding: 0 14px; background: #f1f5f9; border: 1px solid var(--rs-border, #CBD5E1); border-right: none; border-radius: 6px 0 0 6px; font-weight: 600; color: #64748b;">$</span>
                <input type="text" class="rs-input" style="border-radius: 0 6px 6px 0;" id="plan_price" name="plan_price" value="<?= Html::encode($model?->getPlanPrice() ?? '0.00') ?>" required>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 20px;">
        <div class="rs-form-group">
            <label for="plan_limit" class="rs-label">Location / Account Limit <span class="rs-text-danger">*</span></label>
            <input type="text" class="rs-input" id="plan_limit" name="plan_limit" value="<?= Html::encode($model?->getPlanLimit() ?? '1') ?>" placeholder="e.g. 1, 5, 10 or Unlimited" required>
        </div>
        <div class="rs-form-group">
            <label for="status" class="rs-label">Status</label>
            <select class="rs-input" id="status" name="status">
                <option value="1" <?= ($model?->getStatus() ?? '1') === '1' ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= ($model?->getStatus() ?? '1') === '0' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
    </div>

    <div class="rs-form-group rs-mb-6">
        <label for="plan_features" class="rs-label">Plan Features (one per line) <span class="rs-text-danger">*</span></label>
        <textarea class="rs-input" id="plan_features" name="plan_features" rows="5" style="height: auto; padding: 12px;" placeholder="Review monitoring&#10;Social publishing&#10;WhatsApp and Email campaigns&#10;Multi-location management" required><?= Html::encode($model?->getPlanFeatures() ?? '') ?></textarea>
        <div class="rs-text-sm rs-text-muted rs-mt-1">List each feature or capability on its own line.</div>
    </div>

    <div class="rs-d-flex rs-justify-content-end rs-gap-3 rs-pt-4 rs-border-top" style="border-top: 1px solid var(--rs-border, #E2E8F0);">
        <a href="/sys-yii3/subscription" class="rs-btn rs-btn--ghost">Cancel</a>
        <button type="submit" class="rs-btn rs-btn--primary">
            <?= $isNew ? 'Create Plan' : 'Save Changes' ?>
        </button>
    </div>
</form>
