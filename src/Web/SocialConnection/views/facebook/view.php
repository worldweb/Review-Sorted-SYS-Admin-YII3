<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var App\Domain\SocialConnection\ServiceConfig $model
 */

$this->setTitle('Facebook Connection: ' . $model->getDisplayName());
?>

<div class="rs-container">
    <?= $this->render('../partials/_header', [
        'title' => 'Facebook Page: ' . $model->getDisplayName(),
        'subtitle' => 'View detailed integration attributes and linked Instagram Business accounts.'
    ]) ?>

    <div class="rs-section rs-mb-4">
        <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mb-4">
            <a href="/sys-yii3/settings/facebook" class="rs-btn rs-btn--secondary">
                &larr; Back to Facebook Pages
            </a>
            <div class="rs-d-flex rs-gap-2">
                <a href="/sys-yii3/settings/facebook/update?id=<?= $model->getId() ?>" class="rs-btn rs-btn--primary">
                    Edit Settings
                </a>
                <a href="/sys-yii3/settings/facebook/revoke?id=<?= $model->getId() ?>" class="rs-btn rs-btn--danger" onclick="return confirm('Disconnect this page?');">
                    Disconnect
                </a>
            </div>
        </div>
    </div>

    <div class="rs-card rs-card--shadow rs-mb-5" style="background:#fff; border-radius:8px; border:1px solid #e2e8f0;">
        <?= $this->render('../partials/_card_header', [
            'title' => 'Page Attributes',
            'subtitle' => 'Details synchronized from Meta Graph API'
        ]) ?>
        <div class="rs-card__body rs-p-4" style="padding: 1.5rem;">
            <table class="rs-table" style="width: 100%;">
                <tbody>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <th style="padding: 10px 0; width: 220px; color: #64748b; font-size: 0.875rem;">Page Name</th>
                        <td style="padding: 10px 0; font-weight: 600; color: #0f172a;"><?= Html::encode($model->getPageName() ?? 'N/A') ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <th style="padding: 10px 0; color: #64748b; font-size: 0.875rem;">Custom Label</th>
                        <td style="padding: 10px 0;"><?= Html::encode($model->getLabel()) ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <th style="padding: 10px 0; color: #64748b; font-size: 0.875rem;">Page ID</th>
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

    <!-- Instagram Sub-Services Card -->
    <div class="rs-card rs-card--shadow" style="background:#fff; border-radius:8px; border:1px solid #e2e8f0; overflow:hidden;">
        <?= $this->render('../partials/_card_header', [
            'title' => 'Linked Instagram Business Profiles',
            'subtitle' => 'Instagram accounts attached to this Facebook Page'
        ]) ?>
        <div class="rs-card__body rs-p-0">
            <?php if (empty($model->getSubServices())): ?>
                <div style="padding: 2.5rem; text-align: center; color: #64748b;">
                    No Instagram Business profiles are linked to this Facebook Page.
                </div>
            <?php else: ?>
                <table class="rs-table rs-table--hover" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                            <th style="padding: 12px 16px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b;">Instagram Name</th>
                            <th style="padding: 12px 16px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b;">Account ID</th>
                            <th style="padding: 12px 16px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b;">Type</th>
                            <th style="padding: 12px 16px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b;">Linked Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($model->getSubServices() as $sub): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 14px 16px; font-weight: 600; color: #0f172a;">
                                    📷 <?= Html::encode($sub->getName()) ?>
                                </td>
                                <td style="padding: 14px 16px; font-family: monospace; font-size: 0.85rem;">
                                    <?= Html::encode($sub->getObjId()) ?>
                                </td>
                                <td style="padding: 14px 16px;">
                                    <span class="rs-badge rs-badge--default"><?= Html::encode($sub->getType()) ?></span>
                                </td>
                                <td style="padding: 14px 16px; font-size: 0.85rem; color: #64748b;">
                                    <?= $sub->getCreatedAt() ? date('M d, Y', $sub->getCreatedAt()) : 'N/A' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
