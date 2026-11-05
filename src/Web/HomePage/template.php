<?php

declare(strict_types=1);

use App\Domain\SocialPost\SocialPost;
use App\Domain\SocialPost\SocialPostDestination;
use App\Domain\User\User;
use App\Environment;
use App\Security\TenantContextInterface;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * Operational Business Dashboard (Priority 1).
 *
 * @var WebView $this
 * @var string $appName
 * @var string $frameworkVersion
 * @var string $environment
 * @var bool $debug
 * @var string $phpVersion
 * @var string $phpSapi
 * @var bool $dbConnected
 * @var string $dbDriverName
 * @var string $dbServerVersion
 * @var string $dbDatabaseName
 * @var string|null $dbError
 * @var User|null $currentUser
 * @var TenantContextInterface $tenantContext
 * @var string|null $workspaceName
 * @var array{
 *     unreadReviews: int,
 *     totalReviews: int,
 *     scheduledPostsCount: int,
 *     totalFailed: int,
 *     ambiguousFailed: int,
 *     regularFailed: int,
 *     activeChannelsCount: int,
 *     facebookChannelsCount: int,
 *     googleChannelsCount: int,
 *     instagramChannelsCount: int,
 * } $kpi
 * @var array<int, SocialPost> $upcomingPosts
 * @var array{
 *     hasFailures: bool,
 *     totalFailed: int,
 *     ambiguousFailed: int,
 *     regularFailed: int,
 *     failedQueueUrl: string,
 * } $failureAlert
 */

$this->setTitle('Dashboard');
?>

<div class="rs-dashboard">
    <!-- Workspace & Welcome Header -->
    <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-flex-wrap rs-gap-3 rs-mb-5">
        <div>
            <h1 class="rs-h1 rs-m-0">Operational Dashboard</h1>
            <div class="rs-d-flex rs-align-items-center rs-flex-wrap rs-gap-2 rs-mt-2 rs-text-muted" style="font-size: var(--rs-font-size-sm);">
                <?php if ($tenantContext->isUnrestricted()): ?>
                    <span class="rs-badge rs-badge--primary">Global Administrator</span>
                    <span>All Accounts Scope</span>
                <?php else: ?>
                    <span class="rs-badge rs-badge--info">Active Workspace</span>
                    <strong><?= Html::encode($workspaceName ?? ('Account #' . ($tenantContext->getPrimaryAccountId() ?? 'N/A'))) ?></strong>
                <?php endif; ?>
                <span>•</span>
                <span>Role: <strong><?= Html::encode($currentUser?->getRoleLabel() ?? 'User') ?></strong></span>
                <?php if ($currentUser !== null): ?>
                    <span>•</span>
                    <span>Logged in as <strong><?= Html::encode($currentUser->getName()) ?></strong></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="rs-d-flex rs-align-items-center rs-gap-2">
            <a href="<?= Environment::url('/social-post/create') ?>" class="rs-btn rs-btn--primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Create Post
            </a>
            <a href="<?= Environment::url('/reviews?read=0') ?>" class="rs-btn rs-btn--secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                Reviews (<?= number_format($kpi['unreadReviews']) ?>)
            </a>
        </div>
    </div>

    <!-- Conditional Publishing Failure Alert Banner -->
    <?php if ($failureAlert['hasFailures']): ?>
        <div class="rs-alert rs-alert--danger rs-mb-5" role="alert" style="box-shadow: var(--rs-shadow-sm);">
            <div class="rs-alert__icon" style="margin-top: 2px;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            </div>
            <div class="rs-alert__content">
                <div style="font-weight: 700; font-size: 1rem; margin-bottom: 4px;">
                    Publishing Attention Required: <?= number_format($failureAlert['totalFailed']) ?> Failed Destination<?= $failureAlert['totalFailed'] > 1 ? 's' : '' ?>
                </div>
                <div>
                    One or more outbound social media destinations encountered errors during publishing and require operator review.
                    <?php if ($failureAlert['ambiguousFailed'] > 0): ?>
                        <div style="margin-top: 4px; font-weight: 600;">
                            ⚠️ <?= number_format($failureAlert['ambiguousFailed']) ?> destination<?= $failureAlert['ambiguousFailed'] > 1 ? 's are' : ' is' ?> under <em>Ambiguous Delivery Quarantine</em> (timeout expired without remote confirmation). Please verify external channels before retrying.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div style="margin-left: auto; padding-left: 16px; align-self: center;">
                <a href="<?= Html::encode($failureAlert['failedQueueUrl']) ?>" class="rs-btn rs-btn--danger rs-btn--sm" style="white-space: nowrap;">
                    View Failed Queue &rarr;
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Four Top KPI Cards Grid -->
    <div class="rs-dashboard-grid rs-mb-5">
        <!-- KPI 1: Unread Reviews -->
        <div class="rs-col-span-3">
            <div class="rs-kpi-card">
                <div class="rs-kpi-card__header">
                    <div>
                        <span class="rs-kpi-card__title">Unread Reviews</span>
                        <div class="rs-kpi-card__value rs-mt-2"><?= number_format($kpi['unreadReviews']) ?></div>
                    </div>
                    <div class="rs-kpi-card__icon" style="background-color: var(--rs-warning-bg); color: var(--rs-warning-text);">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    </div>
                </div>
                <div class="rs-kpi-card__footer rs-d-flex rs-justify-content-between rs-align-items-center">
                    <span class="rs-text-muted"><?= number_format($kpi['totalReviews']) ?> total reviews</span>
                    <a href="<?= Environment::url('/reviews?read=0') ?>" style="font-weight: 600; font-size: var(--rs-font-size-xs);">View Unread &rarr;</a>
                </div>
            </div>
        </div>

        <!-- KPI 2: Scheduled Posts -->
        <div class="rs-col-span-3">
            <div class="rs-kpi-card">
                <div class="rs-kpi-card__header">
                    <div>
                        <span class="rs-kpi-card__title">Scheduled Posts</span>
                        <div class="rs-kpi-card__value rs-mt-2"><?= number_format($kpi['scheduledPostsCount']) ?></div>
                    </div>
                    <div class="rs-kpi-card__icon" style="background-color: var(--rs-info-bg); color: var(--rs-info-text);">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    </div>
                </div>
                <div class="rs-kpi-card__footer rs-d-flex rs-justify-content-between rs-align-items-center">
                    <span class="rs-text-muted">Unique queued posts</span>
                    <a href="<?= Environment::url('/social-post?status=queued') ?>" style="font-weight: 600; font-size: var(--rs-font-size-xs);">View Queue &rarr;</a>
                </div>
            </div>
        </div>

        <!-- KPI 3: Publishing Failures -->
        <div class="rs-col-span-3">
            <div class="rs-kpi-card" <?= $kpi['totalFailed'] > 0 ? 'style="border-color: var(--rs-danger);"' : '' ?>>
                <div class="rs-kpi-card__header">
                    <div>
                        <span class="rs-kpi-card__title">Publishing Failures</span>
                        <div class="rs-kpi-card__value rs-mt-2" style="<?= $kpi['totalFailed'] > 0 ? 'color: var(--rs-danger);' : '' ?>">
                            <?= number_format($kpi['totalFailed']) ?>
                        </div>
                    </div>
                    <div class="rs-kpi-card__icon" style="background-color: <?= $kpi['totalFailed'] > 0 ? 'var(--rs-danger-bg)' : 'var(--rs-success-bg)' ?>; color: <?= $kpi['totalFailed'] > 0 ? 'var(--rs-danger-text)' : 'var(--rs-success-text)' ?>;">
                        <?php if ($kpi['totalFailed'] > 0): ?>
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <?php else: ?>
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="rs-kpi-card__footer rs-d-flex rs-justify-content-between rs-align-items-center">
                    <?php if ($kpi['totalFailed'] > 0): ?>
                        <span class="rs-text-danger" style="font-weight: 600;">
                            <?= $kpi['regularFailed'] ?> standard<?= $kpi['ambiguousFailed'] > 0 ? ', ' . $kpi['ambiguousFailed'] . ' ambiguous' : '' ?>
                        </span>
                        <a href="<?= Environment::url('/social-post?status=failed') ?>" class="rs-text-danger" style="font-weight: 600; font-size: var(--rs-font-size-xs);">Resolve &rarr;</a>
                    <?php else: ?>
                        <span class="rs-text-success" style="font-weight: 600;">● All healthy</span>
                        <a href="<?= Environment::url('/social-post') ?>" style="font-weight: 600; font-size: var(--rs-font-size-xs);">History &rarr;</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- KPI 4: Connected Channels -->
        <div class="rs-col-span-3">
            <div class="rs-kpi-card">
                <div class="rs-kpi-card__header">
                    <div>
                        <span class="rs-kpi-card__title">Connected Channels</span>
                        <div class="rs-kpi-card__value rs-mt-2"><?= number_format($kpi['activeChannelsCount']) ?></div>
                    </div>
                    <div class="rs-kpi-card__icon" style="background-color: #E0E7FF; color: #4338CA;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
                    </div>
                </div>
                <div class="rs-kpi-card__footer rs-d-flex rs-justify-content-between rs-align-items-center">
                    <span class="rs-text-muted" style="font-size: var(--rs-font-size-xs);">
                        <?= $kpi['facebookChannelsCount'] ?> FB • <?= $kpi['instagramChannelsCount'] ?> IG • <?= $kpi['googleChannelsCount'] ?> GBP
                    </span>
                    <a href="<?= Environment::url('/settings/facebook') ?>" style="font-weight: 600; font-size: var(--rs-font-size-xs);">Channels &rarr;</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Operational Row: Upcoming Posts (8 cols) + Quick Actions (4 cols) -->
    <div class="rs-dashboard-grid rs-mb-5">
        <!-- Left: Upcoming Scheduled Posts (8 cols) -->
        <div class="rs-col-span-8">
            <div class="rs-card rs-h-100">
                <div class="rs-card__header rs-d-flex rs-justify-content-between rs-align-items-center">
                    <h2 class="rs-card__title" style="margin: 0; font-size: 1.15rem; font-weight: 700;">
                        Upcoming Scheduled Posts (Next 5)
                    </h2>
                    <a href="<?= Environment::url('/social-post?status=queued') ?>" class="rs-btn rs-btn--secondary rs-btn--sm">
                        View Publishing Queue &rarr;
                    </a>
                </div>
                <div class="rs-card__body rs-p-0">
                    <?php if (empty($upcomingPosts)): ?>
                        <div style="text-align: center; padding: 48px 24px;">
                            <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--rs-surface); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px; color: var(--rs-text-muted);">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            </div>
                            <h3 style="margin: 0 0 6px 0; font-size: 1.05rem; font-weight: 600; color: var(--rs-text-main);">No Upcoming Posts Scheduled</h3>
                            <p style="margin: 0 0 20px 0; color: var(--rs-text-muted); font-size: var(--rs-font-size-sm); max-width: 420px; display: inline-block;">
                                There are currently no social publishing posts queued for future delivery in this workspace.
                            </p>
                            <div>
                                <a href="<?= Environment::url('/social-post/create') ?>" class="rs-btn rs-btn--primary rs-btn--sm">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                    Schedule a Post Now
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="rs-table-responsive">
                            <table class="rs-table" style="margin-bottom: 0;">
                                <thead>
                                    <tr>
                                        <th style="width: 140px;">Scheduled For</th>
                                        <th>Post Content</th>
                                        <th>Target Platform(s)</th>
                                        <th style="width: 90px; text-align: center;">Status</th>
                                        <th style="width: 70px; text-align: right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcomingPosts as $post): ?>
                                        <?php
                                        $dests = $post->getDestinations();
                                        $rawContent = trim($post->getContent());
                                        $preview = !empty($rawContent)
                                            ? (mb_strlen($rawContent) > 75 ? mb_substr($rawContent, 0, 72) . '...' : $rawContent)
                                            : ($post->hasMedia() ? '📷 [Media Post without text]' : '(No content)');

                                        // Format scheduled time: earliest upcoming destination with status = 'queued'
                                        $earliestQueuedTimestamp = null;
                                        if (!empty($dests)) {
                                            foreach ($dests as $dest) {
                                                $destStatus = $dest instanceof SocialPostDestination ? $dest->getStatus() : ($dest['status'] ?? '');
                                                $destSchedAt = $dest instanceof SocialPostDestination ? $dest->getScheduledAt() : ($dest['scheduled_at'] ?? '');
                                                $isCancelled = $dest instanceof SocialPostDestination ? $dest->isCancelled() : (($dest['error_message'] ?? '') === 'Cancelled by user');

                                                if ($destStatus === SocialPostDestination::STATUS_QUEUED && !$isCancelled && !empty($destSchedAt)) {
                                                    $ts = strtotime($destSchedAt);
                                                    if ($ts !== false && ($earliestQueuedTimestamp === null || $ts < $earliestQueuedTimestamp)) {
                                                        $earliestQueuedTimestamp = $ts;
                                                    }
                                                }
                                            }
                                        }

                                        $formattedDate = $earliestQueuedTimestamp !== null ? date('M j, Y', $earliestQueuedTimestamp) : 'Pending';
                                        $formattedTime = $earliestQueuedTimestamp !== null ? date('g:i A', $earliestQueuedTimestamp) : '';
                                        ?>
                                        <tr>
                                            <td style="white-space: nowrap;">
                                                <div style="font-weight: 600; color: var(--rs-text-main); font-size: var(--rs-font-size-sm);"><?= Html::encode($formattedDate) ?></div>
                                                <div class="rs-text-muted" style="font-size: var(--rs-font-size-xs);"><?= Html::encode($formattedTime) ?></div>
                                            </td>
                                            <td>
                                                <a href="<?= Environment::url('/social-post/view?id=' . $post->getId()) ?>" style="font-weight: 600; color: var(--rs-text-main); text-decoration: none;">
                                                    <?= Html::encode($preview) ?>
                                                </a>
                                                <?php if ($tenantContext->isUnrestricted() && $post->getAccountBusinessName() !== null): ?>
                                                    <div class="rs-text-muted" style="font-size: var(--rs-font-size-xs);">
                                                        Workspace: <?= Html::encode($post->getAccountBusinessName()) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="rs-d-flex rs-flex-wrap rs-gap-1">
                                                    <?php if (empty($dests)): ?>
                                                        <span class="rs-badge rs-badge--muted">No destination</span>
                                                    <?php else: ?>
                                                        <?php foreach ($dests as $dest): ?>
                                                            <?php
                                                            $p = $dest->getPlatform();
                                                            $badgeClass = match ($p) {
                                                                'facebook' => 'rs-badge rs-badge--primary',
                                                                'instagram' => 'rs-badge rs-badge--danger',
                                                                'google' => 'rs-badge rs-badge--info',
                                                                default => 'rs-badge rs-badge--muted',
                                                            };
                                                            $destLabel = $dest->getDestinationName() ?? ucfirst($p);
                                                            ?>
                                                            <span class="<?= $badgeClass ?>" title="<?= Html::encode($destLabel) ?>" style="font-size: 11px;">
                                                                <?= ucfirst($p) ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="rs-badge rs-badge--warning">Queued</span>
                                            </td>
                                            <td style="text-align: right; white-space: nowrap;">
                                                <a href="<?= Environment::url('/social-post/view?id=' . $post->getId()) ?>" class="rs-btn rs-btn--secondary rs-btn--sm" title="View details">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: Quick Actions (4 cols) -->
        <div class="rs-col-span-4">
            <div class="rs-card rs-h-100">
                <div class="rs-card__header">
                    <h2 class="rs-card__title" style="margin: 0; font-size: 1.15rem; font-weight: 700;">
                        Quick Actions
                    </h2>
                </div>
                <div class="rs-card__body rs-d-flex rs-flex-column rs-gap-3">
                    <!-- Action 1: Create Post (All Roles) -->
                    <a href="<?= Environment::url('/social-post/create') ?>" class="rs-btn rs-btn--primary" style="justify-content: flex-start; padding: 12px 16px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        <span>Create New Social Post</span>
                    </a>

                    <!-- Action 2: View Unread Reviews (All Roles) -->
                    <a href="<?= Environment::url('/reviews?read=0') ?>" class="rs-btn rs-btn--secondary" style="justify-content: flex-start; padding: 12px 16px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                        <span>View Unread Reviews (<?= number_format($kpi['unreadReviews']) ?>)</span>
                    </a>

                    <!-- Action 3: View Publishing Queue (All Roles) -->
                    <a href="<?= Environment::url('/social-post') ?>" class="rs-btn rs-btn--secondary" style="justify-content: flex-start; padding: 12px 16px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                        <span>View Publishing Queue &amp; History</span>
                    </a>

                    <?php if ($currentUser !== null && ($currentUser->isAdmin() || $currentUser->isAccountAdmin())): ?>
                        <div style="border-top: 1px solid var(--rs-border); margin: 6px 0; padding-top: 8px;">
                            <span style="font-size: var(--rs-font-size-xs); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--rs-text-muted);">
                                Channel &amp; Workspace Management
                            </span>
                        </div>

                        <!-- Action 4: Connect Facebook / Instagram (Admin & Account Admin) -->
                        <a href="<?= Environment::url('/settings/facebook') ?>" class="rs-btn rs-btn--secondary" style="justify-content: flex-start; padding: 12px 16px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                            <span>Connect Facebook &amp; Instagram</span>
                        </a>

                        <!-- Action 5: Connect Google Business Profile (Admin & Account Admin) -->
                        <a href="<?= Environment::url('/settings/google') ?>" class="rs-btn rs-btn--secondary" style="justify-content: flex-start; padding: 12px 16px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 2a10 10 0 0 1 10 10c0 5.52-4.48 10-10 10S2 17.52 2 12 6.48 2 12 2z"></path><path d="M12 8v8"></path><path d="M8 12h8"></path></svg>
                            <span>Connect Google Business Profile</span>
                        </a>

                        <!-- Action 6: Manage Users (Admin & Account Admin) -->
                        <a href="<?= Environment::url('/users') ?>" class="rs-btn rs-btn--secondary" style="justify-content: flex-start; padding: 12px 16px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            <span>Team &amp; User Management</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($currentUser !== null && $currentUser->isAdmin()): ?>
        <!-- Admin-Only Technical Runtime Diagnostics (Compact Collapsible) -->
        <details class="rs-card rs-p-4 rs-mt-4" style="font-size: var(--rs-font-size-sm); color: var(--rs-text-muted); background: var(--rs-surface);">
            <summary style="cursor: pointer; font-weight: 600; color: var(--rs-text-main); display: flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                <span>System Runtime Diagnostics (Administrator View)</span>
                <span class="rs-badge <?= $dbConnected ? 'rs-badge--success' : 'rs-badge--danger' ?>" style="margin-left: 8px;">
                    <?= $dbConnected ? '● Online' : '● DB Error' ?>
                </span>
            </summary>
            <div style="margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--rs-border);">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
                    <div>
                        <span style="display: block; font-size: 11px; text-transform: uppercase; font-weight: 600;">Framework</span>
                        <strong><?= Html::encode($frameworkVersion) ?></strong>
                    </div>
                    <div>
                        <span style="display: block; font-size: 11px; text-transform: uppercase; font-weight: 600;">PHP Runtime</span>
                        <strong>PHP <?= Html::encode($phpVersion) ?> (<?= Html::encode($phpSapi) ?>)</strong>
                    </div>
                    <div>
                        <span style="display: block; font-size: 11px; text-transform: uppercase; font-weight: 600;">Environment</span>
                        <strong><?= strtoupper(Html::encode($environment)) ?> (Debug: <?= $debug ? 'Enabled' : 'Disabled' ?>)</strong>
                    </div>
                    <div>
                        <span style="display: block; font-size: 11px; text-transform: uppercase; font-weight: 600;">Database Engine</span>
                        <strong><?= Html::encode(strtoupper($dbDriverName)) ?> &bull; <?= Html::encode($dbDatabaseName) ?> (<?= Html::encode($dbServerVersion) ?>)</strong>
                    </div>
                </div>
                <?php if ($dbError !== null): ?>
                    <div style="margin-top: 10px; color: var(--rs-danger); font-weight: 600;">
                        Error: <?= Html::encode($dbError) ?>
                    </div>
                <?php endif; ?>
            </div>
        </details>
    <?php endif; ?>
</div>
