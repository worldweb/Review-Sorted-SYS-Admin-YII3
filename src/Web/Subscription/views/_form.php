<?php

declare(strict_types=1);

/**
 * @var App\Domain\Subscription\Plan|null $model
 * @var string $csrfToken
 */

$isNew = $model === null;
$actionUrl = $isNew ? '/sys-yii3/subscription/create' : '/sys-yii3/subscription/update?id=' . $model->getId();
?>

<form method="post" action="<?= $actionUrl ?>">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="plan_name" class="form-label fw-bold">Plan Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="plan_name" name="plan_name" value="<?= htmlspecialchars($model?->getPlanName() ?? '') ?>" placeholder="e.g. Starter, Pro, Enterprise" required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="plan_price" class="form-label fw-bold">Monthly Price ($) <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="text" class="form-control" id="plan_price" name="plan_price" value="<?= htmlspecialchars($model?->getPlanPrice() ?? '0.00') ?>" required>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="plan_limit" class="form-label fw-bold">Location / Account Limit <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="plan_limit" name="plan_limit" value="<?= htmlspecialchars($model?->getPlanLimit() ?? '1') ?>" placeholder="e.g. 1, 5, 10 or Unlimited" required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="status" class="form-label fw-bold">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="1" <?= ($model?->getStatus() ?? '1') === '1' ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= ($model?->getStatus() ?? '1') === '0' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
    </div>

    <div class="mb-4">
        <label for="plan_features" class="form-label fw-bold">Plan Features (one per line) <span class="text-danger">*</span></label>
        <textarea class="form-control" id="plan_features" name="plan_features" rows="5" placeholder="Review monitoring&#10;Social publishing&#10;WhatsApp and Email campaigns&#10;Multi-location management" required><?= htmlspecialchars($model?->getPlanFeatures() ?? '') ?></textarea>
        <div class="form-text">List each highlight or feature on its own line.</div>
    </div>

    <div class="pt-3 border-top d-flex justify-content-end">
        <a href="/sys-yii3/subscription" class="btn btn-secondary me-2">Cancel</a>
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-lg me-1"></i> <?= $isNew ? 'Create Plan' : 'Save Changes' ?>
        </button>
    </div>
</form>
