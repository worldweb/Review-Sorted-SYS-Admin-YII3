<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var App\Domain\SocialConnection\Api $api
 * @var App\Domain\User\User $currentUser
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var array<string, string> $errors
 * @var string $csrfToken
 */

$this->setTitle('Update Google Project: ' . $api->getLabel());
?>

<div class="rs-container">
    <?= $this->render('../partials/_header', [
        'title' => 'Update Google Project',
        'subtitle' => 'Update credentials and settings for ' . Html::encode($api->getLabel()) . '.'
    ]) ?>

    <?php if ($flash->has('error')): ?>
        <div class="rs-alert rs-alert--danger rs-mb-4" role="alert">
            <?= Html::encode((string) $flash->get('error')) ?>
        </div>
    <?php endif; ?>

    <div class="rs-section">
        <?= $this->render('_form', [
            'api' => $api,
            'errors' => $errors,
            'csrfToken' => $csrfToken,
            'actionUrl' => '/sys-yii3/settings/google-api/update?id=' . $api->getId(),
        ]) ?>
    </div>
</div>
