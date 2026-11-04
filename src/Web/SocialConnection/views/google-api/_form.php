<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var App\Domain\SocialConnection\Api|null $api
 * @var array<string, string> $errors
 * @var string $csrfToken
 * @var string $actionUrl
 */

$isUpdate = $api !== null;
$labelVal = $api?->getLabel() ?? '';
$clientJsonVal = $api?->getClientJson() ?? '';
?>

<div class="rs-card rs-form-card">
    <div class="rs-card__header">
        <h3 class="rs-card__title">Google Cloud Project Credentials</h3>
    </div>
    <div class="rs-card__body">
        <form id="google-project-form" class="rs-form" method="post" action="<?= Html::encode($actionUrl) ?>">
            <?php if (!empty($csrfToken)): ?>
                <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
            <?php endif; ?>

            <!-- Project Name / Label -->
            <div class="rs-form-group rs-mb-5">
                <div class="rs-form-field-wrapper">
                    <label class="rs-label rs-font-semibold rs-mb-2" for="api-label">Google Project Label</label>
                    <input type="text" id="api-label" name="label" class="rs-input rs-w-100" placeholder="Enter Google project label" value="<?= Html::encode($labelVal) ?>" required maxlength="255">
                    <div class="rs-text-muted rs-mt-1" style="font-size: 0.8rem;">Specify the connection name visible inside the dashboard.</div>
                    <?php if (!empty($errors['label'])): ?>
                        <div class="rs-text-danger rs-mt-1" style="font-size: 0.8rem;"><?= Html::encode($errors['label']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Client JSON Textarea -->
            <div class="rs-form-group rs-mb-6">
                <div class="rs-form-field-wrapper">
                    <label class="rs-label rs-font-semibold rs-mb-2" for="api-client-json">Client Credentials JSON</label>
                    <textarea id="api-client-json" name="client_json" class="rs-input rs-textarea-input rs-w-100" placeholder="Paste client_secret_xxx.json contents here" rows="8" required style="font-family: monospace; font-size: 0.85rem;"><?= Html::encode($clientJsonVal) ?></textarea>
                    <div class="rs-text-muted rs-mt-1" style="font-size: 0.8rem;">Paste the full JSON content downloaded from the Google Developer Console.</div>
                    <?php if (!empty($errors['client_json'])): ?>
                        <div class="rs-text-danger rs-mt-1" style="font-size: 0.8rem;"><?= Html::encode($errors['client_json']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Row -->
            <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mt-6">
                <a href="<?= $isUpdate ? \App\Environment::url('/settings/google-api/view?id=') . $api->getId() : \App\Environment::url('/settings/google-api') ?>" class="rs-btn rs-btn--outline">Cancel</a>
                <button type="submit" class="rs-btn rs-btn--primary rs-px-6"><?= $isUpdate ? 'Save Changes' : 'Create Project' ?></button>
            </div>
        </form>
    </div>
</div>
