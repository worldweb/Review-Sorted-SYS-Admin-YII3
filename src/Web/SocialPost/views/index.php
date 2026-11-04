<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var array<int, App\Domain\SocialPost\SocialPost> $posts
 * @var int $totalCount
 * @var int $page
 * @var int $pageCount
 * @var int $pageSize
 * @var string|null $typeFilter
 * @var string $q
 * @var string $status
 * @var string $platform
 * @var string $dateFrom
 * @var string $dateTo
 * @var App\Domain\User\User $currentUser
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 * @var Yiisoft\View\WebView $this
 */

$this->setTitle('Social Publishing History');
?>

<div class="rs-social-post-index">
    <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mb-5">
        <h2 class="rs-h1 rs-m-0"><?= Html::encode($this->getTitle()) ?></h2>
        <a href="<?= \App\Environment::url('/social-post/create') ?>" class="rs-btn rs-btn--primary">Create Social Post</a>
    </div>

    <!-- Search & Filter Styles -->
    <style>
    .rs-filter-form .rs-input,
    .rs-filter-form .rs-select {
        height: 42px;
        box-sizing: border-box;
    }
    .rs-filter-form input[type="date"].rs-input {
        height: 42px;
        padding-top: 0;
        padding-bottom: 0;
        display: inline-flex;
        align-items: center;
    }
    .rs-filter-form .rs-btn {
        height: 42px;
        box-sizing: border-box;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    </style>

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

    <!-- Search & Filter Card -->
    <div class="rs-card rs-mb-4">
        <div class="rs-card__header">
            <h3 class="rs-card__title">Search & Filter Publishing Logs</h3>
        </div>
        <div class="rs-card__body">
            <form method="get" action="<?= \App\Environment::url('/social-post') ?>" class="rs-filter-form">
                <div class="rs-d-flex rs-flex-wrap rs-gap-3 rs-align-items-end">
                    <div style="flex: 2; min-width: 200px;">
                        <label class="rs-label">Search Keyword / ID</label>
                        <input type="text" name="q" class="rs-input" value="<?= Html::encode($q) ?>" placeholder="Search by Post ID, Caption, Location...">
                    </div>

                    <div style="flex: 1; min-width: 150px;">
                        <label class="rs-label">Status</label>
                        <select name="status" class="rs-select rs-input">
                            <option value="">All Statuses</option>
                            <option value="queued" <?= $status === 'queued' ? 'selected' : '' ?>>Queued / Scheduled</option>
                            <option value="processing" <?= $status === 'processing' ? 'selected' : '' ?>>Processing</option>
                            <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Failed</option>
                            <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled by User</option>
                        </select>
                    </div>

                    <div style="flex: 1; min-width: 150px;">
                        <label class="rs-label">Platform</label>
                        <select name="platform" class="rs-select rs-input">
                            <option value="">All Platforms</option>
                            <option value="facebook" <?= $platform === 'facebook' ? 'selected' : '' ?>>Facebook Page</option>
                            <option value="instagram" <?= $platform === 'instagram' ? 'selected' : '' ?>>Instagram Business</option>
                            <option value="google" <?= $platform === 'google' ? 'selected' : '' ?>>Google Business</option>
                        </select>
                    </div>

                    <div style="flex: 1; min-width: 140px;">
                        <label class="rs-label">Date From</label>
                        <input type="date" name="date_from" class="rs-input" value="<?= Html::encode($dateFrom) ?>" placeholder="YYYY-MM-DD">
                    </div>

                    <div style="flex: 1; min-width: 140px;">
                        <label class="rs-label">Date To</label>
                        <input type="date" name="date_to" class="rs-input" value="<?= Html::encode($dateTo) ?>" placeholder="YYYY-MM-DD">
                    </div>

                    <div class="rs-d-flex rs-gap-2">
                        <button type="submit" class="rs-btn rs-btn--primary">Apply Filters</button>
                        <a href="<?= \App\Environment::url('/social-post') ?>" class="rs-btn rs-btn--secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Publishing Logs GridView Card -->
    <div class="rs-card">
        <div class="rs-card__header">
            <h3 class="rs-card__title">Publishing Logs & Queue Status</h3>
        </div>
        <div class="rs-card__body">
            <div class="rs-table-wrapper">
                <table class="table table-striped table-bordered rs-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>Post</th>
                            <th>Target Location</th>
                            <th>Platform</th>
                            <th>Status</th>
                            <th>Scheduled At</th>
                            <th>Published At</th>
                            <th style="max-width: 220px;">Error / Result</th>
                            <th style="width: 160px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($posts)): ?>
                            <tr>
                                <td colspan="9">
                                    <div class="rs-text-center rs-p-5 rs-text-muted">No social publishing jobs found matching your criteria.</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($posts as $p): ?>
                                <?php
                                $dests = $p->getDestinations();
                                if (empty($dests)):
                                ?>
                                    <tr>
                                        <td>#<?= $p->getId() ?></td>
                                        <td>
                                            <a href="<?= \App\Environment::url('/social-post/view?id=' . $p->getId()) ?>" style="font-weight: 600;">#<?= $p->getId() ?></a>
                                        </td>
                                        <td><?= Html::encode($p->getAccountBusinessName() ?? ('#' . $p->getAccountId())) ?></td>
                                        <td>-</td>
                                        <td>
                                            <span class="rs-badge rs-badge--warning"><?= Html::encode(ucfirst($p->getOverallStatus())) ?></span>
                                        </td>
                                        <td><?= Html::encode($p->getScheduledAt() ?? 'Immediate') ?></td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <a href="<?= \App\Environment::url('/social-post/view?id=' . $p->getId()) ?>" class="rs-btn rs-btn--secondary rs-btn--sm">
                                                <span class="glyphicon glyphicon-eye-open"></span> View
                                            </a>
                                            <?php if ($p->isEditable()): ?>
                                                <a href="<?= \App\Environment::url('/social-post/update?id=' . $p->getId()) ?>" class="rs-btn rs-btn--secondary rs-btn--sm">
                                                    <span class="glyphicon glyphicon-pencil"></span> Edit
                                                </a>
                                                <form method="post" action="<?= \App\Environment::url('/social-post/cancel?id=' . $p->getId()) ?>" style="display: inline-block; margin: 0;">
                                                    <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
                                                    <button type="submit" class="rs-btn rs-btn--secondary rs-btn--sm" onclick="return confirm('Are you sure you want to cancel this scheduled post?');">
                                                        <span class="glyphicon glyphicon-remove"></span> Cancel
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($p->isCancelled()): ?>
                                                <form method="post" action="<?= \App\Environment::url('/social-post/delete?id=' . $p->getId()) ?>" style="display: inline-block; margin: 0;">
                                                    <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
                                                    <button type="submit" class="rs-btn rs-btn--secondary rs-btn--sm" onclick="return confirm('Are you sure you want to permanently delete this cancelled social post? This action cannot be undone.');">
                                                        <span class="glyphicon glyphicon-trash"></span> Delete
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($dests as $d): ?>
                                        <tr>
                                            <td><?= $d->getId() ?></td>
                                            <td>
                                                <a href="<?= \App\Environment::url('/social-post/view?id=' . $p->getId()) ?>" style="font-weight: 600;">#<?= $p->getId() ?></a>
                                            </td>
                                            <td><?= Html::encode($d->getDestinationName() ?? $p->getAccountBusinessName() ?? ('#' . $d->getAccountId())) ?></td>
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
                                                if ($d->getErrorMessage() === 'Cancelled by user' || $p->isCancelled()) {
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
                                            <td><?= Html::encode($d->getScheduledAt() ?: ($p->getScheduledAt() ?? 'Immediate')) ?></td>
                                            <td><?= Html::encode($d->getPublishedAt() ?? '-') ?></td>
                                            <td style="max-width: 220px; overflow-wrap: anywhere; word-break: break-word;">
                                                <?php if (!empty($d->getErrorMessage())): ?>
                                                    <span class="has-error"><span class="rs-help-block" title="<?= Html::encode($d->getErrorMessage()) ?>"><?= Html::encode(mb_strimwidth($d->getErrorMessage(), 0, 40, '...')) ?></span></span>
                                                <?php elseif (!empty($d->getRemotePostId())): ?>
                                                    <span class="rs-text-sm" style="font-weight: 500;" title="<?= Html::encode($d->getRemotePostId()) ?>">ID: <?= Html::encode(mb_strimwidth($d->getRemotePostId(), 0, 36, '...')) ?></span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: right; white-space: nowrap;">
                                                <a href="<?= \App\Environment::url('/social-post/view?id=' . $p->getId()) ?>" class="rs-btn rs-btn--secondary rs-btn--sm">
                                                    <span class="glyphicon glyphicon-eye-open"></span> View
                                                </a>
                                                <?php if ($p->isEditable()): ?>
                                                    <a href="<?= \App\Environment::url('/social-post/update?id=' . $p->getId()) ?>" class="rs-btn rs-btn--secondary rs-btn--sm">
                                                        <span class="glyphicon glyphicon-pencil"></span> Edit
                                                    </a>
                                                    <form method="post" action="<?= \App\Environment::url('/social-post/cancel?id=' . $p->getId()) ?>" style="display: inline-block; margin: 0;">
                                                        <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
                                                        <button type="submit" class="rs-btn rs-btn--secondary rs-btn--sm" onclick="return confirm('Are you sure you want to cancel this scheduled post?');">
                                                            <span class="glyphicon glyphicon-remove"></span> Cancel
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if ($p->isCancelled()): ?>
                                                    <form method="post" action="<?= \App\Environment::url('/social-post/delete?id=' . $p->getId()) ?>" style="display: inline-block; margin: 0;">
                                                        <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
                                                        <button type="submit" class="rs-btn rs-btn--secondary rs-btn--sm" onclick="return confirm('Are you sure you want to permanently delete this cancelled social post? This action cannot be undone.');">
                                                            <span class="glyphicon glyphicon-trash"></span> Delete
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if ($d->isFailed() && $d->getErrorMessage() !== 'Cancelled by user'): ?>
                                                    <form method="post" action="<?= \App\Environment::url('/social-post/retry?id=' . $p->getId()) ?>" style="display: inline-block; margin: 0;">
                                                        <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
                                                        <button type="submit" class="rs-btn rs-btn--secondary rs-btn--sm" onclick="return confirm('Are you sure you want to retry publishing to this destination?');">
                                                            <span class="glyphicon glyphicon-repeat"></span> Retry
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Table Footer / Pagination -->
            <div class="rs-table-footer rs-d-flex rs-align-items-center rs-justify-content-between rs-p-4">
                <div class="rs-text-sm rs-text-muted">
                    Showing <strong><?= count($posts) ?></strong> of <strong><?= $totalCount ?></strong> posts
                </div>
                <?php if ($pageCount > 1): ?>
                    <div class="rs-d-flex rs-gap-1 rs-align-items-center">
                        <?php for ($pg = 1; $pg <= $pageCount; $pg++): ?>
                            <?php
                            $pageQuery = http_build_query(array_filter([
                                'page' => $pg,
                                'q' => $q ?: null,
                                'status' => $status ?: null,
                                'platform' => $platform ?: null,
                                'date_from' => $dateFrom ?: null,
                                'date_to' => $dateTo ?: null,
                            ]));
                            ?>
                            <a href="<?= \App\Environment::url('/social-post?' . $pageQuery) ?>" class="rs-btn rs-btn--sm <?= $pg === $page ? 'rs-btn--primary' : 'rs-btn--secondary' ?>" style="min-width: 32px; text-align: center;">
                                <?= $pg ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
