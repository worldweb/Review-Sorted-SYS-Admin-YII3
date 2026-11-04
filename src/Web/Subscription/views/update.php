<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Update Subscription Plan View (Yii 3 Modern Theme)
 *
 * @var App\Domain\Subscription\Plan $model
 * @var App\Domain\User\User $currentUser
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 * @var Yiisoft\View\WebView $this
 */

$planTitle = 'Update Plan: ' . $model->getPlanName();
$this->setTitle($planTitle);

$title = $planTitle;
$description = 'Modify pricing tiers, usage limits, features, and active status.';
$actions = [
    '<a href="' . \App\Environment::url('/subscription') . '" class="rs-btn rs-btn--outline">← Back to Plans</a>'
];
include __DIR__ . '/../../Shared/Components/_page_header.php';
?>

<div class="rs-page">
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

    <?= $this->render('_form', [
        'model' => $model,
        'csrfToken' => $csrfToken,
    ]) ?>
</div>
