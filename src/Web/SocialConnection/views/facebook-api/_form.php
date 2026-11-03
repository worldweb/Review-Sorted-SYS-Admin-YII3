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
$clientIdVal = $api?->getClientId() ?? '';
$apiKeyVal = $api?->getApiKey() ?? '';
?>

<div class="rs-card rs-form-card">
    <div class="rs-card__header">
        <h3 class="rs-card__title">Application Credentials</h3>
    </div>
    <div class="rs-card__body">
        <form id="facebook-app-form" class="rs-form" method="post" action="<?= Html::encode($actionUrl) ?>">
            <?php if (!empty($csrfToken)): ?>
                <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
            <?php endif; ?>

            <!-- Application Label -->
            <div class="rs-form-group rs-mb-5">
                <div class="rs-form-field-wrapper">
                    <label class="rs-label rs-font-semibold rs-mb-2" for="api-label">Application Name</label>
                    <input type="text" id="api-label" name="label" class="rs-input rs-w-100" placeholder="Enter application name" value="<?= Html::encode($labelVal) ?>" required maxlength="255">
                    <div class="rs-text-muted rs-mt-1" style="font-size: 0.8rem;">Specify the connection name visible inside the dashboard.</div>
                    <?php if (!empty($errors['label'])): ?>
                        <div class="rs-text-danger rs-mt-1" style="font-size: 0.8rem;"><?= Html::encode($errors['label']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!$isUpdate || !$api->isFacebookAuthenticated()): ?>
                <!-- Facebook App ID -->
                <div class="rs-form-group rs-mb-5">
                    <div class="rs-form-field-wrapper">
                        <label class="rs-label rs-font-semibold rs-mb-2" for="api-client-id">Facebook App ID</label>
                        <input type="text" id="api-client-id" name="client_id" class="rs-input rs-w-100" placeholder="Enter Facebook App ID" value="<?= Html::encode($clientIdVal) ?>" required maxlength="255">
                        <div class="rs-text-muted rs-mt-1" style="font-size: 0.8rem;">Obtained from your Facebook Developer Dashboard.</div>
                        <?php if (!empty($errors['client_id'])): ?>
                            <div class="rs-text-danger rs-mt-1" style="font-size: 0.8rem;"><?= Html::encode($errors['client_id']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Facebook App Secret -->
                <div class="rs-form-group rs-mb-6">
                    <div class="rs-form-field-wrapper">
                        <label class="rs-label rs-font-semibold rs-mb-2" for="api-key">Facebook App Secret</label>
                        <input type="text" id="api-key" name="api_key" class="rs-input rs-w-100" placeholder="Enter Facebook App Secret" value="<?= Html::encode($apiKeyVal) ?>" required maxlength="255">
                        <div class="rs-text-muted rs-mt-1" style="font-size: 0.8rem;">Never share this credential publicly.</div>
                        <?php if (!empty($errors['api_key'])): ?>
                            <div class="rs-text-danger rs-mt-1" style="font-size: 0.8rem;"><?= Html::encode($errors['api_key']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Action Row -->
            <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mt-6">
                <a href="<?= $isUpdate ? '/sys-yii3/settings/facebook-api/view?id=' . $api->getId() : '/sys-yii3/settings/facebook-api' ?>" class="rs-btn rs-btn--outline">Cancel</a>
                <button type="submit" class="rs-btn rs-btn--primary rs-px-6"><?= $isUpdate ? 'Save Changes' : 'Create Application' ?></button>
            </div>
        </form>
    </div>
</div>
