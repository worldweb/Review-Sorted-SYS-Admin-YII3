<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var App\Domain\SocialPost\SocialPost $model
 * @var Yiisoft\Session\Flash\Flash $flash
 */

$this->setTitle('Social Post Details #' . $model->getId());

$totalDest = count($model->getDestinations());
$publishedCount = 0;
$queuedCount = 0;
$failedCount = 0;
foreach ($model->getDestinations() as $d) {
    if ($d->isPublished()) {
        $publishedCount++;
    } elseif ($d->isFailed()) {
        $failedCount++;
    } else {
        $queuedCount++;
    }
}
?>

<div class="rs-social-post-view">
    <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mb-5" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <h1 class="rs-h1 rs-m-0" style="font-size: 1.75rem; font-weight: 700; color: #0f172a; margin: 0;">Social Post Details #<?= $model->getId() ?></h1>
        <div style="display: flex; gap: 0.5rem;">
            <?php if ($failedCount > 0): ?>
                <a href="/sys-yii3/social-post/retry?id=<?= $model->getId() ?>" class="rs-btn rs-btn--secondary">
                    Retry Failed Jobs
                </a>
            <?php endif; ?>
            <?php if ($model->isScheduled() && $publishedCount === 0): ?>
                <a href="/sys-yii3/social-post/update?id=<?= $model->getId() ?>" class="rs-btn rs-btn--secondary">
                    Edit Scheduled Post
                </a>
                <a href="/sys-yii3/social-post/cancel?id=<?= $model->getId() ?>" class="rs-btn rs-btn--danger" onclick="return confirm('Cancel this scheduled post?');">
                    Cancel Scheduled Post
                </a>
            <?php endif; ?>
            <a href="/sys-yii3/social-post" class="rs-btn rs-btn--secondary">
                &larr; Back to History
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

    <!-- Publishing Progress Summary Card -->
    <div class="rs-card rs-mb-4" style="background:#fff; border-radius:8px; border:1px solid #e2e8f0; margin-bottom: 1.5rem;">
        <div class="rs-card__header rs-d-flex rs-justify-content-between rs-align-items-center" style="padding: 1rem 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <h3 class="rs-card__title" style="font-size: 1.125rem; font-weight: 600; margin: 0; color: #0f172a;">Publishing Progress Summary</h3>
            <?php if ($queuedCount > 0): ?>
                <span class="rs-badge rs-badge--warning" style="font-size: 12px;">⏳ Delivery in progress</span>
            <?php endif; ?>
        </div>
        <div class="rs-card__body" style="padding: 1.5rem;">
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <div style="background: #f8fafc; border-radius: 6px; padding: 1rem 1.5rem; min-width: 140px; text-align: center; border: 1px solid #e2e8f0;">
                    <div style="font-size: 1.75rem; font-weight: 700; color: #0f172a;"><?= $totalDest ?></div>
                    <div style="font-size: 0.85rem; color: #64748b;">Total Jobs</div>
                </div>
                <div style="background: #ecfdf5; border-radius: 6px; padding: 1rem 1.5rem; min-width: 140px; text-align: center; border: 1px solid #a7f3d0;">
                    <div style="font-size: 1.75rem; font-weight: 700; color: #047857;"><?= $publishedCount ?></div>
                    <div style="font-size: 0.85rem; color: #047857; font-weight: 600;">Published</div>
                </div>
                <div style="background: #fef3c7; border-radius: 6px; padding: 1rem 1.5rem; min-width: 140px; text-align: center; border: 1px solid #fde68a;">
                    <div style="font-size: 1.75rem; font-weight: 700; color: #b45309;"><?= $queuedCount ?></div>
                    <div style="font-size: 0.85rem; color: #b45309; font-weight: 600;">Queued</div>
                </div>
                <div style="background: #fef2f2; border-radius: 6px; padding: 1rem 1.5rem; min-width: 140px; text-align: center; border: 1px solid #fecaca;">
                    <div style="font-size: 1.75rem; font-weight: 700; color: #b91c1c;"><?= $failedCount ?></div>
                    <div style="font-size: 0.85rem; color: #b91c1c; font-weight: 600;">Failed</div>
                </div>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; align-items: start;">
        <!-- Card: Post Content -->
        <div class="rs-card" style="background:#fff; border-radius:8px; border:1px solid #e2e8f0;">
            <div class="rs-card__header" style="padding: 1rem 1.5rem; border-bottom: 1px solid #e2e8f0;">
                <h3 class="rs-card__title" style="font-size: 1.125rem; font-weight: 600; margin: 0; color: #0f172a;">Message Content</h3>
            </div>
            <div class="rs-card__body" style="padding: 1.5rem;">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem; font-size: 0.95rem; line-height: 1.5; white-space: pre-wrap; color: #1e293b; margin-bottom: 1.25rem;">
                    <?= Html::encode($model->getContent()) ?>
                </div>
                <table class="rs-table" style="width: 100%;">
                    <tbody>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <th style="padding: 8px 0; color: #64748b; font-size: 0.85rem; width: 140px;">Author</th>
                            <td style="padding: 8px 0; font-weight: 600;"><?= Html::encode($model->getAuthorEmail() ?? 'Admin') ?></td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <th style="padding: 8px 0; color: #64748b; font-size: 0.85rem;">Delivery Mode</th>
                            <td style="padding: 8px 0;"><?= Html::encode(ucfirst($model->getPostType())) ?></td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <th style="padding: 8px 0; color: #64748b; font-size: 0.85rem;">Scheduled Time</th>
                            <td style="padding: 8px 0;"><?= Html::encode($model->getScheduledAt() ?? 'Immediate') ?></td>
                        </tr>
                        <tr>
                            <th style="padding: 8px 0; color: #64748b; font-size: 0.85rem;">Created Date</th>
                            <td style="padding: 8px 0; color: #64748b;"><?= $model->getCreatedAt() ? date('Y-m-d H:i:s', $model->getCreatedAt()) : 'N/A' ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Card: Destination Breakdown -->
        <div class="rs-card" style="background:#fff; border-radius:8px; border:1px solid #e2e8f0; overflow:hidden;">
            <div class="rs-card__header" style="padding: 1rem 1.5rem; border-bottom: 1px solid #e2e8f0;">
                <h3 class="rs-card__title" style="font-size: 1.125rem; font-weight: 600; margin: 0; color: #0f172a;">Target Destination Logs</h3>
            </div>
            <div class="rs-card__body rs-p-0">
                <table class="rs-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                            <th style="padding: 10px 14px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b;">Platform</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b;">Destination</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b;">Status</th>
                            <th style="padding: 10px 14px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b;">Remote Post ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($model->getDestinations())): ?>
                            <tr>
                                <td colspan="4" style="padding: 2rem; text-align: center; color: #94a3b8;">No destination jobs recorded.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($model->getDestinations() as $dest): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 12px 14px;">
                                        <?php if ($dest->getPlatform() === 'facebook'): ?>
                                            <span class="rs-badge" style="background:#e0e7ff; color:#3730a3;">Facebook</span>
                                        <?php elseif ($dest->getPlatform() === 'instagram'): ?>
                                            <span class="rs-badge" style="background:#fdf2f8; color:#be185d;">Instagram</span>
                                        <?php elseif ($dest->getPlatform() === 'google'): ?>
                                            <span class="rs-badge" style="background:#e0f2fe; color:#0369a1;">Google</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px 14px; font-weight: 600; color: #0f172a; font-size: 0.875rem;">
                                        <?= Html::encode($dest->getDestinationName() ?? $dest->getPlatform()) ?>
                                    </td>
                                    <td style="padding: 12px 14px;">
                                        <?php if ($dest->isPublished()): ?>
                                            <span class="rs-badge rs-badge--success">Published</span>
                                        <?php elseif ($dest->isFailed()): ?>
                                            <span class="rs-badge rs-badge--danger">Failed</span>
                                        <?php else: ?>
                                            <span class="rs-badge rs-badge--warning">Queued</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px 14px; font-size: 0.8rem; font-family: monospace;">
                                        <?php if (!empty($dest->getRemotePostId())): ?>
                                            <span style="color:#047857;"><?= Html::encode($dest->getRemotePostId()) ?></span>
                                        <?php elseif (!empty($dest->getErrorMessage())): ?>
                                            <span style="color:#b91c1c;"><?= Html::encode($dest->getErrorMessage()) ?></span>
                                        <?php else: ?>
                                            <span style="color:#94a3b8;">Awaiting dispatch</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
