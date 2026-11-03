<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var App\Domain\SocialConnection\Api $api
 * @var App\Domain\User\User $currentUser
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 */

$this->setTitle($api->getLabel());

$statusHtml = !empty($api->getClientJson())
    ? '<span class="rs-badge rs-badge--success">Configured</span>'
    : '<span class="rs-badge rs-badge--danger">Not Configured</span>';

// Format client json
$jsonPretty = 'Credentials Not Configured';
if (!empty($api->getClientJson())) {
    $decoded = json_decode($api->getClientJson());
    if ($decoded !== null) {
        $jsonPretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        $jsonPretty = $api->getClientJson();
    }
}
?>

<div class="rs-container">
    <div class="rs-page-header rs-d-flex rs-align-items-center rs-justify-content-between rs-mb-6">
        <div>
            <h1 class="rs-page-title" style="font-size: 1.75rem; font-weight: 700; color: var(--rs-text-main, #0f172a); margin: 0;"><?= Html::encode($api->getLabel()) ?></h1>
            <div class="rs-text-muted rs-mt-1" style="font-size: 0.875rem; color: #64748b;">Google Cloud Project Details &amp; Integration Settings</div>
        </div>
        <div class="rs-d-flex rs-gap-2">
            <a href="/sys-yii3/settings/google-api/update?id=<?= $api->getId() ?>" class="rs-btn rs-btn--primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="rs-mr-1"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                Edit Project
            </a>
            <a href="/sys-yii3/settings/google-api/delete?id=<?= $api->getId() ?>" class="rs-btn rs-btn--outline rs-text-danger" onclick="return confirm('Are you sure you want to delete this Google Project?');">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="rs-mr-1"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                Delete Project
            </a>
        </div>
    </div>
    <hr class="rs-mb-6" style="border: 0; border-top: 1px solid var(--rs-border-color, #e2e8f0); margin-top: 1rem; margin-bottom: 1.5rem;" />

    <?php if ($flash->has('success')): ?>
        <div class="rs-alert rs-alert--success rs-mb-4" role="alert">
            <?= Html::encode((string) $flash->get('success')) ?>
        </div>
    <?php endif; ?>
    <?php if ($flash->has('error')): ?>
        <div class="rs-alert rs-alert--danger rs-mb-4" role="alert">
            <?= Html::encode((string) $flash->get('error')) ?>
        </div>
    <?php endif; ?>

    <div class="rs-section">
        <div class="rs-card rs-card--shadow">
            <div class="rs-card__header rs-d-flex rs-align-items-center rs-justify-content-between">
                <h3 class="rs-card__title">Project Configuration</h3>
                <?= $statusHtml ?>
            </div>
            <div class="rs-card__body">
                <div class="rs-grid rs-grid--cols-1 rs-grid--cols-md-2 rs-gap-6 rs-mb-6">
                    <div>
                        <div class="rs-text-muted rs-font-semibold rs-mb-1" style="font-size: 0.8rem; text-transform: uppercase;">Google Project Label</div>
                        <div class="rs-font-bold rs-text-main" style="font-size: 1.05rem;"><?= Html::encode($api->getLabel()) ?></div>
                    </div>
                    <div>
                        <div class="rs-text-muted rs-font-semibold rs-mb-1" style="font-size: 0.8rem; text-transform: uppercase;">Created Date</div>
                        <div><?= Html::encode($api->getCreatedAtStr()) ?></div>
                    </div>
                </div>

                <div>
                    <div class="rs-text-muted rs-font-semibold rs-mb-2" style="font-size: 0.8rem; text-transform: uppercase;">Client Credentials JSON</div>
                    <pre style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem; font-family: monospace; font-size: 0.8rem; overflow-x: auto; max-height: 350px;"><?= Html::encode($jsonPretty) ?></pre>
                </div>
            </div>
            <div class="rs-card__footer rs-d-flex rs-justify-content-between rs-align-items-center">
                <a href="/sys-yii3/settings/google-api" class="rs-btn rs-btn--outline">
                    &larr; Back to Google Projects
                </a>
            </div>
        </div>
    </div>
</div>
