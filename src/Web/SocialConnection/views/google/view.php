<?php

declare(strict_types=1);

use App\Domain\SocialConnection\ServiceConfig;
use Yiisoft\Html\Html;

/**
 * @var ServiceConfig $model
 * @var Yiisoft\View\WebView $this
 */

$this->setTitle($model->getLabel());
?>

<div class="service-config-view rs-page">
    <div class="rs-page-header rs-d-flex rs-justify-content-between rs-align-items-center rs-mb-5">
        <h1 class="rs-h1 rs-m-0"><?= Html::encode($this->getTitle()) ?></h1>
        <div class="rs-d-flex rs-gap-2">
            <a href="<?= \App\Environment::url('/settings/google/update?id=' . $model->getId()) ?>" class="btn btn-primary rs-btn rs-btn--primary">
                Update
            </a>
            <a href="<?= \App\Environment::url('/settings/google/delete?id=' . $model->getId()) ?>" class="btn btn-danger rs-btn rs-btn--danger" onclick="return confirm('Are you sure you want to delete this item?');">
                Delete
            </a>
            <a href="<?= \App\Environment::url('/settings/google') ?>" class="btn btn-default rs-btn rs-btn--secondary">
                Back to Google Accounts
            </a>
        </div>
    </div>

    <!-- DetailView Table Matching Yii 2 Modern Theme -->
    <div class="rs-card rs-mb-5">
        <div class="rs-card__body rs-p-0">
            <table class="table table-striped table-bordered detail-view rs-table" style="width: 100%; margin-bottom: 0;">
                <tbody>
                    <tr>
                        <th style="width: 280px; font-weight: 600;">Label</th>
                        <td><?= Html::encode($model->getLabel()) ?></td>
                    </tr>
                    <tr>
                        <th style="font-weight: 600;">Email for low rate reviews (1, 2 or 3)</th>
                        <td><?= !empty($model->getEmailLowReview()) ? Html::encode($model->getEmailLowReview()) : '<span class="rs-text-muted">(not configured)</span>' ?></td>
                    </tr>
                    <tr>
                        <th style="font-weight: 600;">Created At</th>
                        <td><?= $model->getCreatedAt() ? date('M d, Y', $model->getCreatedAt()) : 'N/A' ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
