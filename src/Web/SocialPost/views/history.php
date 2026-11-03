<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var array<int, App\Domain\SocialPost\SocialPost> $posts
 * @var int $totalCount
 * @var int $page
 * @var int $pageCount
 * @var int $pageSize
 * @var App\Domain\User\User $currentUser
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 * @var Yiisoft\View\WebView $this
 */

$this->setTitle('Publishing History');
?>

<div class="rs-page">
    <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mb-5">
        <div>
            <h2 class="rs-h1 rs-m-0">Publishing History &amp; Delivery Log</h2>
            <p class="rs-text-sm rs-text-muted rs-mb-0 rs-mt-1">Audit trail of all dispatched posts across connected social channels.</p>
        </div>
        <a href="/sys-yii3/social-post/create" class="rs-btn rs-btn--primary">Create Social Post</a>
    </div>

    <div class="rs-card">
        <div class="rs-card__header rs-d-flex rs-justify-content-between rs-align-items-center">
            <h3 class="rs-card__title">All Dispatches (<?= $totalCount ?>)</h3>
        </div>
        <div class="rs-card__body rs-p-0">
            <?php if (empty($posts)): ?>
                <div class="rs-p-6 rs-text-center rs-text-muted">
                    <h5>No Publishing Records Yet</h5>
                </div>
            <?php else: ?>
                <div class="rs-table-wrapper">
                    <table class="rs-table rs-table--hover">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Content</th>
                                <th>Target Channels</th>
                                <th>Created Date</th>
                                <th>Dispatched Date</th>
                                <th>Result</th>
                                <th style="text-align: right; width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($posts as $p): ?>
                                <tr>
                                    <td class="rs-text-muted">#<?= $p->getId() ?></td>
                                    <td>
                                        <div class="rs-font-medium rs-text-primary" style="max-width: 350px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            <?= Html::encode($p->getContent()) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="rs-d-flex rs-flex-column rs-gap-1">
                                            <?php foreach ($p->getDestinations() as $d): ?>
                                                <div>
                                                    <span class="rs-badge rs-badge--default">
                                                        <?= Html::encode(strtoupper($d->getPlatform())) ?>: <?= Html::encode($d->getStatus()) ?>
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td class="rs-text-sm rs-text-muted"><?= $p->getCreatedAt() ? date('Y-m-d H:i', $p->getCreatedAt()) : 'N/A' ?></td>
                                    <td class="rs-text-sm rs-text-muted"><?= Html::encode($p->getScheduledAt() ?? 'Immediate') ?></td>
                                    <td>
                                        <?php if ($p->getOverallStatus() === 'published'): ?>
                                            <span class="rs-badge rs-badge--success">&#10003; Delivered</span>
                                        <?php else: ?>
                                            <span class="rs-badge rs-badge--warning"><?= Html::encode(ucfirst($p->getOverallStatus())) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <a href="/sys-yii3/social-post/view?id=<?= $p->getId() ?>" class="rs-btn rs-btn--sm rs-btn--outline">
                                            Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($pageCount > 1): ?>
                    <div class="rs-table-footer rs-d-flex rs-align-items-center rs-justify-content-between rs-p-4">
                        <div class="rs-text-sm rs-text-muted">
                            Showing <strong><?= count($posts) ?></strong> of <strong><?= $totalCount ?></strong> records
                        </div>
                        <div class="rs-d-flex rs-gap-1 rs-align-items-center">
                            <?php for ($pg = 1; $pg <= $pageCount; $pg++): ?>
                                <a href="/sys-yii3/social-post/history?page=<?= $pg ?>" class="rs-btn rs-btn--sm <?= $pg === $page ? 'rs-btn--primary' : 'rs-btn--secondary' ?>" style="min-width: 32px; text-align: center;">
                                    <?= $pg ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
