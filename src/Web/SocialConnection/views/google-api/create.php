<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var App\Domain\User\User $currentUser
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var array<string, string> $errors
 * @var string $csrfToken
 */

$this->setTitle('Create Google Project');
?>

<div class="rs-container">
    <?= $this->render('../partials/_header', [
        'title' => 'Create Google Project',
        'subtitle' => 'Configure OAuth credentials for Google Business Profile API integration.'
    ]) ?>

    <?php if ($flash->has('error')): ?>
        <div class="rs-alert rs-alert--danger rs-mb-4" role="alert">
            <?= Html::encode((string) $flash->get('error')) ?>
        </div>
    <?php endif; ?>

    <div class="rs-section">
        <?= $this->render('google-api/_form', [
            'api' => null,
            'errors' => $errors,
            'csrfToken' => $csrfToken,
            'actionUrl' => \App\Environment::url('/settings/google-api/create'),
        ]) ?>
    </div>
</div>
