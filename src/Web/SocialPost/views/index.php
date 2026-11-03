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
        <a href="/sys-yii3/social-post/create" class="rs-btn rs-btn--primary">Create Social Post</a>
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

    <!-- Search & Filter Card matching Yii 2 Modern Theme -->
    <div class="rs-card rs-mb-4">
        <div class="rs-card__header">
            <h3 class="rs-card__title">Search & Filter Publishing Logs</h3>
        </div>
        <div class="rs-card__body rs-p-4">
            <form method="get" action="/sys-yii3/social-post" class="rs-filter-form">
                <div class="rs-d-flex rs-flex-wrap rs-gap-3 rs-align-items-end">
                    <div style="flex: 2; min-width: 200px;">
                        <label class="rs-label">Search Keyword / ID</label>
                        <input type="text" name="q" class="rs-input" value="<?= Html::encode($q) ?>" placeholder="Search by Post ID, Caption, Location...">
                    </div>

                    <div style="flex: 1; min-width: 150px;">
                        <label class="rs-label">Status</label>
                        <select name="status" class="rs-input">
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
                        <select name="platform" class="rs-input">
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
                        <a href="/sys-yii3/social-post" class="rs-btn rs-btn--secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Publishing Logs & Queue Status Card -->
    <div class="rs-card">
        <div class="rs-card__header">
            <h3 class="rs-card__title">Publishing Logs & Queue Status</h3>
        </div>
        <div class="rs-card__body rs-p-0">
            <div class="rs-table-wrapper">
                <table class="rs-table rs-table--hover">
                    <thead>
                        <tr>
                            <th style="width: 70px; text-align: left;"># ID</th>
                            <th style="text-align: left;">Post Content</th>
                            <th style="text-align: left;">Target Destinations</th>
                            <th style="text-align: left;">Scheduled / Published</th>
                            <th style="text-align: left;">Status</th>
                            <th style="text-align: right; width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($posts)): ?>
                            <tr>
                                <td colspan="6" class="rs-text-center rs-p-6 rs-text-muted">
                                    No social publishing jobs found matching your criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($posts as $p): ?>
                                <tr>
                                    <td class="rs-font-bold rs-text-muted">
                                        #<?= $p->getId() ?>
                                    </td>
                                    <td style="max-width: 320px;">
                                        <div class="rs-font-medium rs-text-primary" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            <?= Html::encode($p->getContent()) ?>
                                        </div>
                                        <div class="rs-text-xs rs-text-muted rs-mt-1">
                                            By <?= Html::encode($p->getAuthorEmail() ?? 'Admin') ?> &bull; <?= $p->getCreatedAt() ? date('M d, Y H:i', $p->getCreatedAt()) : '' ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                            $allDests = $p->getDestinations();
                                            $destCount = count($allDests);
                                            $maxVisible = 3;
                                            $visibleDests = array_slice($allDests, 0, $maxVisible);
                                            $remainingCount = $destCount - $maxVisible;
                                            $fullNames = array_map(static fn($d) => strtoupper($d->getPlatform()) . ' (' . ($d->getDestinationName() ?: '#' . $d->getAccountId()) . '): ' . $d->getStatus(), $allDests);
                                            $tooltipText = implode("\n", $fullNames);
                                        ?>
                                        <div class="rs-d-flex rs-gap-1 rs-align-items-center rs-flex-wrap" style="max-width: 220px;" title="<?= Html::encode($tooltipText) ?>">
                                            <?php foreach ($visibleDests as $dest): ?>
                                                <?php if ($dest->getPlatform() === 'facebook'): ?>
                                                    <span class="rs-badge rs-badge--primary" style="font-size: 11px; padding: 2px 6px;">FB</span>
                                                <?php elseif ($dest->getPlatform() === 'instagram'): ?>
                                                    <span class="rs-badge rs-badge--warning" style="font-size: 11px; padding: 2px 6px;">IG</span>
                                                <?php elseif ($dest->getPlatform() === 'google'): ?>
                                                    <span class="rs-badge rs-badge--default" style="font-size: 11px; padding: 2px 6px;">Google</span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                            <?php if ($remainingCount > 0): ?>
                                                <span class="rs-badge rs-badge--secondary" style="font-size: 11px; padding: 2px 6px; cursor: help;" title="<?= Html::encode($tooltipText) ?>">
                                                    +<?= $remainingCount ?> more
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="rs-text-sm">
                                        <?php if ($p->isScheduled()): ?>
                                            <div><span class="rs-text-warning rs-font-bold">⏰ Scheduled:</span></div>
                                            <div class="rs-text-muted"><?= Html::encode($p->getScheduledAt() ?? 'Pending') ?></div>
                                        <?php else: ?>
                                            <div><span class="rs-text-success rs-font-bold">⚡ Immediate</span></div>
                                            <div class="rs-text-muted rs-text-xs"><?= $p->getCreatedAt() ? date('Y-m-d H:i', $p->getCreatedAt()) : '' ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php $st = $p->getOverallStatus(); ?>
                                        <?php if ($st === 'published'): ?>
                                            <span class="rs-badge rs-badge--success">Published</span>
                                        <?php elseif ($st === 'partial_failure'): ?>
                                            <span class="rs-badge rs-badge--danger">Failed / Partial</span>
                                        <?php elseif ($st === 'scheduled'): ?>
                                            <span class="rs-badge rs-badge--warning">Queued</span>
                                        <?php else: ?>
                                            <span class="rs-badge rs-badge--default"><?= Html::encode(ucfirst($st)) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <a href="/sys-yii3/social-post/view?id=<?= $p->getId() ?>" class="rs-btn rs-btn--sm rs-btn--outline" title="View Details">
                                            View
                                        </a>
                                        <?php if ($p->isScheduled() && $p->getOverallStatus() !== 'published'): ?>
                                            <a href="/sys-yii3/social-post/update?id=<?= $p->getId() ?>" class="rs-btn rs-btn--sm rs-btn--secondary" title="Edit Post">
                                                Edit
                                            </a>
                                            <a href="/sys-yii3/social-post/cancel?id=<?= $p->getId() ?>" class="rs-btn rs-btn--sm rs-btn--ghost rs-text-danger" onclick="return confirm('Cancel this scheduled post?');" title="Cancel">
                                                Cancel
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
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
                            <a href="/sys-yii3/social-post?page=<?= $pg ?><?= !empty($q) ? '&q=' . urlencode($q) : '' ?><?= !empty($status) ? '&status=' . urlencode($status) : '' ?><?= !empty($platform) ? '&platform=' . urlencode($platform) : '' ?><?= !empty($dateFrom) ? '&date_from=' . urlencode($dateFrom) : '' ?><?= !empty($dateTo) ? '&date_to=' . urlencode($dateTo) : '' ?>" class="rs-btn rs-btn--sm <?= $pg === $page ? 'rs-btn--primary' : 'rs-btn--secondary' ?>" style="min-width: 32px; text-align: center;">
                                <?= $pg ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
