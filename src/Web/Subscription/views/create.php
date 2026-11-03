<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Create Subscription Plan View (Yii 3 Modern Theme)
 *
 * @var App\Domain\User\User $currentUser
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 * @var Yiisoft\View\WebView $this
 */

$this->setTitle('Create New Plan');

$title = 'Create Subscription Plan';
$description = 'Define a new tier with pricing, seat limits, and platform features.';
$actions = [
    '<a href="/sys-yii3/subscription" class="rs-btn rs-btn--outline">← Back to Plans</a>'
];
include __DIR__ . '/../../Shared/Components/_page_header.php';
?>

<div class="rs-page">
    <?php if ($flash->has('error')): ?>
        <div class="rs-alert rs-alert--danger rs-mb-4" role="alert">
            <?= Html::encode((string) $flash->get('error')) ?>
        </div>
    <?php endif; ?>

    <div class="rs-card" style="max-width: 800px; margin: 0 auto;">
        <div class="rs-card__header" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #E2E8F0);">
            <h3 class="rs-card__title" style="margin: 0; font-size: 1.05rem; font-weight: 700;">Plan Properties</h3>
        </div>
        <div class="rs-card__body rs-p-6" style="padding: 24px;">
            <?= $this->render('_form', [
                'model' => null,
                'csrfToken' => $csrfToken,
            ]) ?>
        </div>
    </div>
</div>
