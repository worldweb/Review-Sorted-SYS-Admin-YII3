<?php

declare(strict_types=1);

use App\Domain\SocialPost\SocialPostDestination;
use Yiisoft\Html\Html;

/**
 * @var App\Domain\SocialPost\SocialPost $model
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 * @var Yiisoft\View\WebView $this
 */

$this->setTitle('Social Post Details #' . $model->getId());

$progress = $model->getPublishingProgress();
$hasActiveJobs = ($progress['queued'] > 0 || $progress['processing'] > 0);
?>

<div class="rs-social-post-view">
    <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-flex-wrap rs-gap-3 rs-mb-5">
        <h2 class="rs-h1 rs-m-0"><?= Html::encode($this->getTitle()) ?></h2>
        <div class="rs-d-flex rs-gap-2">
            <?php if ($model->isEditable()): ?>
                <a href="<?= \App\Environment::url('/social-post/update?id=' . $model->getId()) ?>" class="rs-btn rs-btn--secondary">
                    <span class="glyphicon glyphicon-pencil"></span> Edit Scheduled Post
                </a>
                <form method="post" action="<?= \App\Environment::url('/social-post/cancel?id=' . $model->getId()) ?>" style="display: inline-block; margin: 0;">
                    <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
                    <button type="submit" class="rs-btn rs-btn--secondary" onclick="return confirm('Are you sure you want to cancel this scheduled post?');">
                        <span class="glyphicon glyphicon-remove"></span> Cancel Scheduled Post
                    </button>
                </form>
            <?php endif; ?>

            <?php if ($model->isCancelled()): ?>
                <form method="post" action="<?= \App\Environment::url('/social-post/delete?id=' . $model->getId()) ?>" style="display: inline-block; margin: 0;">
                    <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
                    <button type="submit" class="rs-btn rs-btn--secondary" onclick="return confirm('Are you sure you want to permanently delete this cancelled social post? This action cannot be undone.');">
                        <span class="glyphicon glyphicon-trash"></span> Delete Cancelled Post
                    </button>
                </form>
            <?php endif; ?>

            <a href="<?= \App\Environment::url('/social-post') ?>" class="rs-btn rs-btn--secondary">Back to History</a>
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
    <div class="rs-card rs-mb-4">
        <div class="rs-card__header rs-d-flex rs-justify-content-between rs-align-items-center">
            <h3 class="rs-card__title">Publishing Progress Summary</h3>
            <?php if ($hasActiveJobs): ?>
                <span class="rs-badge rs-badge--info" style="font-size: 12px;">
                    <span class="glyphicon glyphicon-refresh rs-spin" style="margin-right: 4px;"></span> Publishing in progress...
                </span>
            <?php endif; ?>
        </div>
        <div class="rs-card__body">
            <div class="rs-d-flex rs-flex-wrap rs-gap-4 rs-align-items-center">
                <div class="rs-p-3" style="background-color: var(--rs-bg-app); border-radius: var(--rs-radius-md); min-width: 120px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 700; color: var(--rs-text-main);"><?= $progress['total'] ?></div>
                    <div class="rs-text-sm rs-text-muted">Total Jobs</div>
                </div>
                <div class="rs-p-3" style="background-color: #ecfdf5; border-radius: var(--rs-radius-md); min-width: 120px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 700; color: #047857;"><?= $progress['published'] ?></div>
                    <div class="rs-text-sm" style="color: #047857; font-weight: 500;">Published</div>
                </div>
                <div class="rs-p-3" style="background-color: #eff6ff; border-radius: var(--rs-radius-md); min-width: 120px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 700; color: #1d4ed8;"><?= $progress['processing'] ?></div>
                    <div class="rs-text-sm" style="color: #1d4ed8; font-weight: 500;">Processing</div>
                </div>
                <div class="rs-p-3" style="background-color: #fef3c7; border-radius: var(--rs-radius-md); min-width: 120px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 700; color: #b45309;"><?= $progress['queued'] ?></div>
                    <div class="rs-text-sm" style="color: #b45309; font-weight: 500;">Queued</div>
                </div>
                <div class="rs-p-3" style="background-color: #fef2f2; border-radius: var(--rs-radius-md); min-width: 120px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 700; color: #b91c1c;"><?= $progress['failed'] ?></div>
                    <div class="rs-text-sm" style="color: #b91c1c; font-weight: 500;">Failed</div>
                </div>
                <?php if ($progress['cancelled'] > 0): ?>
                    <div class="rs-p-3" style="background-color: #f3f4f6; border-radius: var(--rs-radius-md); min-width: 120px; text-align: center;">
                        <div style="font-size: 24px; font-weight: 700; color: #4b5563;"><?= $progress['cancelled'] ?></div>
                        <div class="rs-text-sm" style="color: #4b5563; font-weight: 500;">Cancelled</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Master Post Information Card -->
    <div class="rs-card rs-mb-4">
        <div class="rs-card__header">
            <h3 class="rs-card__title">Master Post Information</h3>
        </div>
        <div class="rs-card__body">
            <table class="table table-striped table-bordered detail-view rs-table">
                <tbody>
                    <tr>
                        <th style="width: 200px;">ID</th>
                        <td>#<?= $model->getId() ?></td>
                    </tr>
                    <tr>
                        <th>Creator Account</th>
                        <td><?= Html::encode($model->getAccountBusinessName() ?? ('#' . $model->getAccountId())) ?></td>
                    </tr>
                    <tr>
                        <th>Author</th>
                        <td><?= Html::encode($model->getAuthorEmail() ?? ('#' . $model->getUserId())) ?></td>
                    </tr>
                    <tr>
                        <th>Content</th>
                        <td style="white-space: pre-wrap;"><?= Html::encode($model->getContent()) ?></td>
                    </tr>
                    <tr>
                        <th>Media Type</th>
                        <td><?= Html::encode(ucfirst($model->getMediaType())) ?></td>
                    </tr>
                    <tr>
                        <th>Media Attachment</th>
                        <td>
                            <?php if (!$model->hasMedia()): ?>
                                None
                            <?php elseif ($model->getMediaType() === 'image'): ?>
                                <img src="<?= Html::encode($model->getMediaUrl()) ?>" style="max-width: 200px; max-height: 150px; border-radius: var(--rs-radius-md);" class="img-thumbnail" alt="Attachment">
                            <?php else: ?>
                                <a href="<?= Html::encode($model->getMediaUrl()) ?>" target="_blank">View Media Attachment</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Post Type</th>
                        <td><?= Html::encode(ucfirst($model->getPostType())) ?></td>
                    </tr>
                    <tr>
                        <th>Scheduled Date & Time</th>
                        <td>
                            <?php if (empty($model->getScheduledAt())): ?>
                                Immediate
                            <?php else: ?>
                                <?= Html::encode($model->getScheduledAt()) ?> <span class="rs-text-sm rs-text-muted">(Australia/Perth)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Overall Status</th>
                        <td>
                            <?php
                            $status = $model->getDerivedStatus();
                            $badgeClass = 'rs-badge rs-badge--info';
                            if ($status === SocialPostDestination::STATUS_PUBLISHED) $badgeClass = 'rs-badge rs-badge--success';
                            if ($status === SocialPostDestination::STATUS_FAILED) $badgeClass = 'rs-badge rs-badge--danger';
                            if ($status === 'partially_published') $badgeClass = 'rs-badge rs-badge--warning';
                            if ($status === 'cancelled') $badgeClass = 'rs-badge rs-badge--muted';
                            if ($status === 'scheduled') $badgeClass = 'rs-badge rs-badge--primary';
                            ?>
                            <span class="<?= $badgeClass ?>"><?= ucfirst(str_replace('_', ' ', $status)) ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th>Created At</th>
                        <td><?= $model->getCreatedAt() ? date('M d, Y H:i:s', $model->getCreatedAt()) : '-' ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Destination Queue Jobs Card -->
    <div class="rs-card">
        <div class="rs-card__header">
            <h3 class="rs-card__title">Destination Queue Jobs (<?= count($model->getDestinations()) ?>)</h3>
        </div>
        <div class="rs-card__body">
            <div class="rs-table-wrapper">
                <table class="table table-striped table-bordered rs-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>Target Location</th>
                            <th>Platform</th>
                            <th>Status</th>
                            <th>Scheduled At</th>
                            <th>Published At</th>
                            <th>Retry Engine</th>
                            <th style="max-width: 320px;">Error & Diagnostics</th>
                            <th style="width: 120px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($model->getDestinations())): ?>
                            <tr>
                                <td colspan="9" class="rs-text-center rs-p-5 rs-text-muted">No destination jobs recorded.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($model->getDestinations() as $d): ?>
                                <tr>
                                    <td><?= $d->getId() ?></td>
                                    <td><?= Html::encode($d->getDestinationName() ?? $model->getAccountBusinessName() ?? ('#' . $d->getAccountId())) ?></td>
                                    <td>
                                        <?php
                                        $plat = $d->getPlatform();
                                        $platBadge = 'rs-badge rs-badge--muted';
                                        if ($plat === 'facebook') $platBadge = 'rs-badge rs-badge--primary';
                                        if ($plat === 'instagram') $platBadge = 'rs-badge rs-badge--danger';
                                        if ($plat === 'google') $platBadge = 'rs-badge rs-badge--info';
                                        ?>
                                        <span class="<?= $platBadge ?>"><?= ucfirst($plat) ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        if ($d->getErrorMessage() === 'Cancelled by user' || $model->isCancelled()) {
                                            echo '<span class="rs-badge rs-badge--muted">Cancelled</span>';
                                        } elseif ($d->isPublished()) {
                                            echo '<span class="rs-badge rs-badge--success">Published</span>';
                                        } elseif ($d->isFailed()) {
                                            echo '<span class="rs-badge rs-badge--danger">Failed</span>';
                                        } elseif ($d->isProcessing()) {
                                            echo '<span class="rs-badge rs-badge--info">Processing</span>';
                                        } else {
                                            echo '<span class="rs-badge rs-badge--warning">Queued</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?= Html::encode($d->getScheduledAt()) ?> <span class="rs-text-sm rs-text-muted">(Perth)</span></td>
                                    <td><?= Html::encode($d->getPublishedAt() ?? '-') ?></td>
                                    <td>
                                        <?php
                                        $count = (int) $d->getRetryCount();
                                        $label = 'Attempt #' . ($count + 1);
                                        if ($d->isFailed() && $d->getErrorMessage() !== 'Cancelled by user') {
                                            $label .= ' <span class="rs-badge rs-badge--warning" style="font-size:10px;">Retry Available</span>';
                                        } elseif ($d->isPublished()) {
                                            $label .= ' <span class="rs-badge rs-badge--success" style="font-size:10px;">Completed</span>';
                                        }
                                        echo $label;
                                        ?>
                                    </td>
                                    <td style="word-break: break-word; overflow-wrap: anywhere; max-width: 320px;">
                                        <?php if (!empty($d->getErrorMessage())): ?>
                                            <div class="has-error"><span class="rs-help-block" title="<?= Html::encode($d->getErrorMessage()) ?>"><?= Html::encode($d->getErrorMessage()) ?></span></div>
                                        <?php elseif (!empty($d->getRemotePostId())): ?>
                                            <span class="rs-text-sm" style="font-weight: 500; word-break: break-all; overflow-wrap: anywhere;">Remote ID: <code style="word-break: break-all; overflow-wrap: anywhere; font-size: 12px; background-color: var(--rs-bg-app); padding: 2px 6px; border-radius: var(--rs-radius-sm); color: var(--rs-text-main); font-family: monospace;"><?= Html::encode($d->getRemotePostId()) ?></code></span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <?php if ($d->isFailed() && $d->getErrorMessage() !== 'Cancelled by user'): ?>
                                            <form method="post" action="<?= \App\Environment::url('/social-post/retry?id=' . $model->getId()) ?>" style="display: inline-block; margin: 0;">
                                                <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
                                                <button type="submit" class="rs-btn rs-btn--secondary rs-btn--sm" onclick="return confirm('Are you sure you want to retry publishing to this destination?');">
                                                    <span class="glyphicon glyphicon-repeat"></span> Retry
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            -
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
