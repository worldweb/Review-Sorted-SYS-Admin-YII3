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
 * @var App\Domain\User\User $currentUser
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 */

$this->setTitle('Social Publishing History');
?>

<div class="rs-social-post-index">
    <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mb-5" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h1 class="rs-h1 rs-m-0" style="font-size: 1.75rem; font-weight: 700; color: #0f172a; margin: 0;">Social Publishing History</h1>
        <a href="/sys-yii3/social-post/create" class="rs-btn rs-btn--primary" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.65rem 1.25rem; font-weight: 600; text-decoration: none; border-radius: 6px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Create Social Post
        </a>
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

    <!-- Search & Filter Card -->
    <div class="rs-card rs-mb-4" style="background:#fff; border-radius:8px; border:1px solid #e2e8f0; margin-bottom: 1.5rem;">
        <div class="rs-card__header" style="padding: 1rem 1.5rem; border-bottom: 1px solid #e2e8f0;">
            <h3 class="rs-card__title" style="font-size: 1rem; font-weight: 600; margin: 0; color: #0f172a;">Search &amp; Filter Publishing Logs</h3>
        </div>
        <div class="rs-card__body" style="padding: 1.25rem 1.5rem;">
            <form method="get" action="/sys-yii3/social-post" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
                <div style="flex: 2; min-width: 220px;">
                    <label class="rs-label" style="display: block; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: #64748b; margin-bottom: 0.35rem;">Search Caption / ID</label>
                    <input type="text" name="q" class="rs-input" value="<?= Html::encode($_GET['q'] ?? '') ?>" placeholder="Search by Post ID, Caption, Location..." style="width: 100%; height: 40px; padding: 0.5rem 0.85rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>

                <div style="flex: 1; min-width: 160px;">
                    <label class="rs-label" style="display: block; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: #64748b; margin-bottom: 0.35rem;">Schedule Mode</label>
                    <select name="type" class="rs-select" style="width: 100%; height: 40px; padding: 0.5rem 0.85rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <option value="">All Modes</option>
                        <option value="immediate" <?= ($typeFilter === 'immediate') ? 'selected' : '' ?>>Immediate</option>
                        <option value="scheduled" <?= ($typeFilter === 'scheduled') ? 'selected' : '' ?>>Scheduled</option>
                    </select>
                </div>

                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="rs-btn rs-btn--primary" style="height: 40px; padding: 0 1.25rem; font-weight: 600;">Filter</button>
                    <a href="/sys-yii3/social-post" class="rs-btn rs-btn--secondary" style="height: 40px; padding: 0 1rem; display: inline-flex; align-items: center;">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Publishing History Data Table -->
    <div class="rs-card rs-card--shadow" style="background:#fff; border-radius:8px; border:1px solid #e2e8f0; overflow:hidden;">
        <div class="rs-table-wrapper" style="overflow-x: auto;">
            <table class="rs-table rs-table--hover" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                        <th style="padding: 12px 16px; width: 70px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b;"># ID</th>
                        <th style="padding: 12px 16px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b;">Post Content</th>
                        <th style="padding: 12px 16px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b;">Target Destinations</th>
                        <th style="padding: 12px 16px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b;">Scheduled / Published</th>
                        <th style="padding: 12px 16px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b;">Status</th>
                        <th style="padding: 12px 16px; text-align: right; font-size: 0.8rem; text-transform: uppercase; color: #64748b;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($posts)): ?>
                        <tr>
                            <td colspan="6" style="padding: 3rem 1rem; text-align: center; color: #64748b;">
                                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📢</div>
                                <div style="font-weight: 600; font-size: 1.125rem; color: #0f172a;">No Social Posts Found</div>
                                <p style="font-size: 0.875rem; color: #64748b; margin-top: 0.25rem;">Create a new post to broadcast updates to your Google and Meta social channels.</p>
                                <a href="/sys-yii3/social-post/create" class="rs-btn rs-btn--primary" style="margin-top: 1rem;">Create Social Post</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($posts as $p): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 14px 16px; font-weight: 700; color: #64748b;">
                                    #<?= $p->getId() ?>
                                </td>
                                <td style="padding: 14px 16px; max-width: 320px;">
                                    <div style="font-weight: 600; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= Html::encode($p->getContent()) ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 2px;">
                                        By <?= Html::encode($p->getAuthorEmail() ?? 'Admin') ?> &bull; <?= $p->getCreatedAt() ? date('M d, Y H:i', $p->getCreatedAt()) : '' ?>
                                    </div>
                                </td>
                                <td style="padding: 14px 16px;">
                                    <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                        <?php foreach ($p->getDestinations() as $dest): ?>
                                            <?php if ($dest->getPlatform() === 'facebook'): ?>
                                                <span class="rs-badge" style="background:#e0e7ff; color:#3730a3;">FB</span>
                                            <?php elseif ($dest->getPlatform() === 'instagram'): ?>
                                                <span class="rs-badge" style="background:#fdf2f8; color:#be185d;">IG</span>
                                            <?php elseif ($dest->getPlatform() === 'google'): ?>
                                                <span class="rs-badge" style="background:#e0f2fe; color:#0369a1;">Google</span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td style="padding: 14px 16px; font-size: 0.85rem; color: #334155;">
                                    <?php if ($p->isScheduled()): ?>
                                        <div><span style="color:#b45309; font-weight:600;">⏰ Scheduled:</span></div>
                                        <div><?= Html::encode($p->getScheduledAt() ?? 'Pending') ?></div>
                                    <?php else: ?>
                                        <div><span style="color:#047857; font-weight:600;">⚡ Immediate</span></div>
                                        <div style="color:#64748b; font-size:0.75rem;"><?= $p->getCreatedAt() ? date('Y-m-d H:i', $p->getCreatedAt()) : '' ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 14px 16px;">
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
                                <td style="padding: 14px 16px; text-align: right;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <a href="/sys-yii3/social-post/view?id=<?= $p->getId() ?>" class="rs-btn rs-btn--sm rs-btn--secondary" title="View Details">
                                            View
                                        </a>
                                        <?php if ($p->isScheduled() && $p->getOverallStatus() !== 'published'): ?>
                                            <a href="/sys-yii3/social-post/update?id=<?= $p->getId() ?>" class="rs-btn rs-btn--sm rs-btn--outline" title="Edit Post">
                                                Edit
                                            </a>
                                            <a href="/sys-yii3/social-post/cancel?id=<?= $p->getId() ?>" class="rs-btn rs-btn--sm rs-btn--danger" onclick="return confirm('Cancel this scheduled post?');" title="Cancel">
                                                Cancel
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pageCount > 1): ?>
            <div style="padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e2e8f0;">
                <span style="font-size: 0.875rem; color: #64748b;">Showing <?= count($posts) ?> of <?= $totalCount ?> posts</span>
                <div style="display: flex; gap: 4px;">
                    <?php for ($pg = 1; $pg <= $pageCount; $pg++): ?>
                        <a href="/sys-yii3/social-post?page=<?= $pg ?><?= !empty($typeFilter) ? '&type=' . urlencode($typeFilter) : '' ?>" class="rs-btn rs-btn--sm <?= $pg === $page ? 'rs-btn--primary' : 'rs-btn--secondary' ?>" style="min-width: 32px; text-align: center;">
                            <?= $pg ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
