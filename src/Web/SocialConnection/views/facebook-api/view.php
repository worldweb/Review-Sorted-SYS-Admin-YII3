<?php

declare(strict_types=1);

use App\Domain\SocialConnection\Api;
use Yiisoft\Html\Html;

/**
 * @var Api $api
 * @var App\Domain\User\User $currentUser
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 * @var Yiisoft\View\WebView $this
 */

$this->setTitle($api->getLabel());
?>

<div class="api-view rs-page">
    <div class="rs-page-header rs-d-flex rs-justify-content-between rs-align-items-center rs-mb-5">
        <h1 class="rs-h1 rs-m-0"><?= Html::encode($this->getTitle()) ?></h1>
        <div class="rs-d-flex rs-gap-2">
            <a href="<?= \App\Environment::url('/settings/facebook-api/update?id=' . $api->getId()) ?>" class="btn btn-primary rs-btn rs-btn--primary">
                Update
            </a>
            <a href="<?= \App\Environment::url('/settings/facebook-api/delete?id=' . $api->getId()) ?>" class="btn btn-danger rs-btn rs-btn--danger" onclick="return confirm('Are you sure you want to delete this item?');">
                Delete
            </a>
            <a href="<?= \App\Environment::url('/settings/facebook-api') ?>" class="btn btn-default rs-btn rs-btn--secondary">
                Back to Facebook Apps
            </a>
        </div>
    </div>

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

    <!-- DetailView Table Matching Yii 2 Modern Theme -->
    <div class="rs-card rs-mb-5">
        <div class="rs-card__body rs-p-0">
            <table class="table table-striped table-bordered detail-view rs-table" style="width: 100%; margin-bottom: 0;">
                <tbody>
                    <tr>
                        <th style="width: 280px; font-weight: 600;">Label</th>
                        <td><?= Html::encode($api->getLabel()) ?></td>
                    </tr>
                    <tr>
                        <th style="font-weight: 600;">App ID</th>
                        <td><?= Html::encode($api->getClientId() ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th style="font-weight: 600;">App Secret</th>
                        <td><?= Html::encode($api->getAppSecretMasked()) ?></td>
                    </tr>
                    <tr>
                        <th style="font-weight: 600;">Authorized</th>
                        <td><?= $api->isFacebookAuthenticated() ? 'Yes' : 'No' ?></td>
                    </tr>
                    <tr>
                        <th style="font-weight: 600;">Created At</th>
                        <td><?= Html::encode($api->getCreatedAtStr()) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
