<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var App\Domain\User\User $currentUser
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var array<string, string> $errors
 * @var string $csrfToken
 */

$this->setTitle('Create Facebook Application');
?>

<div class="rs-container">
    <?= $this->render('../partials/_header', [
        'title' => 'Create Facebook Application',
        'subtitle' => 'Configure developer credentials for Facebook API integration.'
    ]) ?>

    <?php if ($flash->has('error')): ?>
        <div class="rs-alert rs-alert--danger rs-mb-4" role="alert">
            <?= Html::encode((string) $flash->get('error')) ?>
        </div>
    <?php endif; ?>

    <div class="rs-section">
        <?= $this->render('_form', [
            'api' => null,
            'errors' => $errors,
            'csrfToken' => $csrfToken,
            'actionUrl' => '/sys-yii3/settings/facebook-api/create',
        ]) ?>
    </div>
</div>
