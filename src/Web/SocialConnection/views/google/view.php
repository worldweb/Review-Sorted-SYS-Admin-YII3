<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var App\Domain\SocialConnection\ServiceConfig $model
 */

$this->setTitle('Google Location: ' . $model->getDisplayName());
?>

<div class="rs-container">
    <?= $this->render('../partials/_header', [
        'title' => 'Google Location: ' . $model->getDisplayName(),
        'subtitle' => 'View Google Business Profile synchronization status and details.'
    ]) ?>

    <div class="rs-section rs-mb-4">
        <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mb-4">
            <a href="/sys-yii3/settings/google" class="rs-btn rs-btn--secondary">
                &larr; Back to Google Locations
            </a>
            <div class="rs-d-flex rs-gap-2">
                <a href="/sys-yii3/settings/google/update?id=<?= $model->getId() ?>" class="rs-btn rs-btn--primary">
                    Edit Settings
                </a>
                <a href="/sys-yii3/settings/google/revoke?id=<?= $model->getId() ?>" class="rs-btn rs-btn--danger" onclick="return confirm('Disconnect this location?');">
                    Disconnect
                </a>
            </div>
        </div>
    </div>

    <div class="rs-card rs-card--shadow rs-mb-5" style="background:#fff; border-radius:8px; border:1px solid #e2e8f0;">
        <?= $this->render('../partials/_card_header', [
            'title' => 'Google Location Attributes',
            'subtitle' => 'Location identifiers synchronized from Google Business Profile API'
        ]) ?>
        <div class="rs-card__body rs-p-4" style="padding: 1.5rem;">
            <table class="rs-table" style="width: 100%;">
                <tbody>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <th style="padding: 10px 0; width: 220px; color: #64748b; font-size: 0.875rem;">Location Name</th>
                        <td style="padding: 10px 0; font-weight: 600; color: #0f172a;"><?= Html::encode($model->getPageName() ?? 'N/A') ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <th style="padding: 10px 0; color: #64748b; font-size: 0.875rem;">Custom Label</th>
                        <td style="padding: 10px 0;"><?= Html::encode($model->getLabel()) ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <th style="padding: 10px 0; color: #64748b; font-size: 0.875rem;">Google Location ID</th>
                        <td style="padding: 10px 0; font-family: monospace;"><?= Html::encode($model->getPageId() ?? 'N/A') ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <th style="padding: 10px 0; color: #64748b; font-size: 0.875rem;">Connection Status</th>
                        <td style="padding: 10px 0;">
                            <?= $this->render('../partials/_status_badge', [
                                'status' => $model->isLinked() ? 'Connected' : 'Not Linked'
                            ]) ?>
                        </td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <th style="padding: 10px 0; color: #64748b; font-size: 0.875rem;">Low Rating Alert Email</th>
                        <td style="padding: 10px 0;"><?= !empty($model->getEmailLowReview()) ? Html::encode($model->getEmailLowReview()) : '<span style="color:#94a3b8; font-style:italic;">Not configured</span>' ?></td>
                    </tr>
                    <tr>
                        <th style="padding: 10px 0; color: #64748b; font-size: 0.875rem;">Connected At</th>
                        <td style="padding: 10px 0; color: #64748b;"><?= $model->getCreatedAt() ? date('Y-m-d H:i:s', $model->getCreatedAt()) : 'N/A' ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
