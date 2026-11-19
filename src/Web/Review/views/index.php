<?php

declare(strict_types=1);

use App\Domain\Review\Review;
use App\Domain\Review\ReviewSearchFilter;
use Yiisoft\Html\Html;

/**
 * @var \Yiisoft\View\WebView $this
 * @var Review[] $reviews
 * @var int $total
 * @var array{total: int, unread: int} $metrics
 * @var ReviewSearchFilter $filter
 * @var array<int, string> $serviceConfigOptions
 * @var array<int, string> $serviceOptions
 * @var int $autoRefreshInterval
 * @var \App\Domain\User\User $currentUser
 * @var \App\Security\TenantContextInterface $context
 * @var \Yiisoft\Session\Flash\FlashInterface|null $flash
 * @var string|null $query64
 * @var array<string, mixed>|null $queryParams
 * @var string|null $csrfToken
 * @var string|null $csrf
 */

$this->setTitle('Reviews & Mentions');

$effectiveCsrf = (string) ($csrfToken ?? (isset($csrf) ? (string) $csrf : ''));
$currentPage = $filter->getPage();
$pageSize = $filter->getPageSize();
$totalPages = max(1, (int) ceil($total / $pageSize));

// Check if channel is filtered by Google (1) or Facebook (2) to enable Bulk Reply matching legacy Yii 2
$isGoogleOrFacebookChannel = ($filter->getServiceId() === 1 || $filter->getServiceId() === 2);

// Star Rating Render Function
$renderStars = static function (?float $rawRate): string {
    if ($rawRate === null) {
        return '<span class="rs-text-muted">-</span>';
    }

    $rate = max(0.0, min(5.0, $rawRate));

    if ($rate >= 4.0) {
        $colorClass = 'rs-text-success';
    } elseif ($rate >= 3.0) {
        $colorClass = 'rs-text-warning';
    } else {
        $colorClass = 'rs-text-danger';
    }

    if ($rate == 5.0) $label = 'Excellent';
    elseif ($rate >= 4.0) $label = 'Very Good';
    elseif ($rate >= 3.0) $label = 'Good';
    elseif ($rate >= 2.0) $label = 'Poor';
    else $label = 'Very Poor';

    $starsHtml = '<div class="rs-rating-stars rs-d-flex rs-align-items-center rs-gap-1" aria-hidden="true" style="display: flex; gap: 2px;">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= (int) floor($rate)) {
            $starsHtml .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="#F59E0B" stroke="#F59E0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>';
        } elseif ($i === (int) ceil($rate) && ($rate - floor($rate)) > 0) {
            $gradId = 'star-grad-' . bin2hex(random_bytes(4));
            $pct = round(($rate - floor($rate)) * 100);
            $starsHtml .= '
            <svg width="14" height="14" viewBox="0 0 24 24" stroke="#F59E0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <defs>
                    <linearGradient id="' . $gradId . '">
                        <stop offset="' . $pct . '%" stop-color="#F59E0B" />
                        <stop offset="' . $pct . '%" stop-color="transparent" />
                    </linearGradient>
                </defs>
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" fill="url(#' . $gradId . ')"></polygon>
            </svg>';
        } else {
            $starsHtml .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="transparent" stroke="#CBD5E1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>';
        }
    }
    $starsHtml .= '</div>';

    $formattedRate = number_format($rate, 1);

    return '
    <div class="rs-rating-component" style="display: flex; flex-direction: column; gap: 2px;" aria-label="Rating: ' . $formattedRate . ' out of 5">
        <div>' . $starsHtml . '</div>
        <div style="display: flex; align-items: center; gap: 4px;">
            <span class="' . $colorClass . '" style="font-weight: 700; font-size: 13px;">' . $formattedRate . '</span>
            <span class="rs-text-muted" style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">' . $label . '</span>
        </div>
    </div>';
};
?>

<div class="rs-page">
    <?php if (isset($flash)): ?>
        <?php include dirname(__DIR__, 2) . '/Shared/Components/_alerts.php'; ?>
    <?php endif; ?>

    <!-- Header Section with KPI Metric Cards -->
    <div class="reviews-header rs-mb-6">
        <div class="rs-d-flex rs-align-items-center rs-justify-content-between rs-w-100 rs-mb-2">
            <h1 class="rs-page-title rs-m-0">Reviews & Mentions</h1>
            <div class="rs-page-header__actions rs-d-flex rs-align-items-center rs-gap-3">
                <?php if ($currentUser->isAdmin() || $currentUser->isAccountAdmin()): ?>
                    <button type="button" id="btn-sync-reviews" class="rs-btn rs-btn--outline" onclick="triggerReviewSync();" title="Sync Reviews from Google & Facebook">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="rs-mr-1" style="vertical-align: -2px;"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                        <span id="sync-btn-text">Sync Reviews</span>
                        <span id="sync-btn-spinner" style="display:none;">Syncing...</span>
                    </button>
                <?php endif; ?>
                <?php if ($isGoogleOrFacebookChannel): ?>
                    <button type="button" class="rs-btn rs-btn--primary btn-reply-all" onclick="submitBulkReply();">
                        Bulk Reply
                    </button>
                <?php else: ?>
                    <span class="rs-text-muted rs-text-xs" style="font-style: italic; line-height: 36px;">
                        <i class="fa fa-info-circle"></i> To use Bulk Reply, please filter reviews by Google or Facebook channel.
                    </span>
                <?php endif; ?>
                <a href="<?= \App\Environment::url('/reviews/export') ?>" class="rs-btn rs-btn--secondary">
                    Export Data
                </a>
            </div>
        </div>
        <p class="rs-text-muted rs-mb-5">Manage and reply to customer feedback across all channels.</p>

        <!-- KPI Metric Cards matching Yii 2 Dashboard Group -->
        <div class="rs-d-flex rs-gap-4 rs-flex-wrap rs-w-100">
            <div class="rs-card rs-p-5" style="flex: 1 1 300px; min-width: 260px; border-top: 3px solid var(--rs-primary, #2563eb);">
                <div class="rs-d-flex rs-align-items-center rs-justify-content-between rs-mb-2">
                    <span class="rs-text-xs rs-font-bold rs-text-uppercase" style="letter-spacing: 0.05em; color: var(--rs-primary, #2563eb);">Total Reviews</span>
                    <span class="rs-badge rs-badge--soft rs-badge--primary">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    </span>
                </div>
                <div class="rs-font-bold rs-text-main" style="font-size: 28px; line-height: 1.2;"><?= number_format($metrics['total']) ?></div>
                <div class="rs-text-muted rs-text-xs rs-mt-2">All imported customer reviews</div>
            </div>

            <div class="rs-card rs-p-5" style="flex: 1 1 300px; min-width: 260px; border-top: 3px solid #ef4444;">
                <div class="rs-d-flex rs-align-items-center rs-justify-content-between rs-mb-2">
                    <span class="rs-text-xs rs-font-bold rs-text-uppercase" style="letter-spacing: 0.05em; color: #ef4444;">Unread Reviews</span>
                    <span class="rs-badge rs-badge--soft rs-badge--danger">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    </span>
                </div>
                <div class="rs-font-bold rs-text-main" style="font-size: 28px; line-height: 1.2;"><?= number_format($metrics['unread']) ?></div>
                <div class="rs-text-muted rs-text-xs rs-mt-2">Requires attention</div>
            </div>
        </div>
    </div>

    <!-- Manual Review Sync Information Banner (Visible only during active synchronization) -->
    <div id="sync-info-banner" class="rs-alert rs-alert--info rs-mb-4" style="display: none; background-color: #f0f7ff; border: 1px solid #bae0ff; border-left: 4px solid #1677ff; color: #1e293b; padding: 12px 16px; border-radius: 6px; align-items: flex-start; gap: 12px;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1677ff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
        </svg>
        <div style="font-size: 13px; line-height: 1.5;">
            <div style="font-weight: 700; color: #0958d9; margin-bottom: 2px;">Manual Review Sync Information</div>
            <div>Manual sync fetches only the latest reviews (up to 2 pages or 200 reviews per connected location). It does not fetch all historical reviews. Full historical synchronization is handled separately by scheduled background synchronization.</div>
        </div>
    </div>

    <!-- Review Synchronization Progress Card -->
    <style>@keyframes rs-spin { 100% { transform: rotate(360deg); } }</style>
    <div id="sync-progress-card" class="rs-card rs-p-4 rs-mb-4" style="display: none; border-left: 4px solid var(--rs-primary, #2563eb); background: #ffffff;">
        <div class="rs-d-flex rs-align-items-center rs-justify-content-between rs-mb-2">
            <div class="rs-d-flex rs-align-items-center rs-gap-2">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" class="rs-sync-spinner" style="animation: rs-spin 1s linear infinite;"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                <span id="sync-progress-title" class="rs-font-bold rs-text-sm rs-text-main">Synchronizing Reviews...</span>
            </div>
            <span id="sync-progress-percent" class="rs-text-xs rs-font-bold" style="color: var(--rs-primary, #2563eb);">0%</span>
        </div>
        <div style="height: 8px; background-color: #e2e8f0; border-radius: 4px; overflow: hidden; margin-bottom: 8px;">
            <div id="sync-progress-bar" style="width: 0%; height: 100%; background-color: var(--rs-primary, #2563eb); transition: width 0.3s ease;"></div>
        </div>
        <div class="rs-d-flex rs-align-items-center rs-justify-content-between rs-text-xs rs-text-muted">
            <span id="sync-progress-details">Initializing sync...</span>
            <span id="sync-progress-stats">0 fetched &bull; 0 inserted &bull; 0 updated</span>
        </div>

        <!-- Active Sync Tab Warning (Visible while sync is running) -->
        <div id="sync-active-warning" class="rs-mt-3 rs-p-2" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; display: none; align-items: flex-start; gap: 8px; font-size: 12px; color: #475569; line-height: 1.4;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 1px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
            <span><strong>Please keep this browser tab open until synchronization is complete.</strong> Closing the tab or browser before completion may interrupt the sync, and remaining locations may not be processed.</span>
        </div>

        <div id="sync-pause-banner" class="rs-mt-3 rs-p-3" style="display: none; background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px;">
            <div class="rs-d-flex rs-align-items-center rs-justify-content-between">
                <span id="sync-pause-msg" class="rs-text-xs" style="color: #92400e;">Network issue encountered.</span>
                <div class="rs-d-flex rs-gap-2">
                    <button type="button" id="btn-sync-resume" class="rs-btn rs-btn--xs rs-btn--primary">Resume Sync</button>
                    <button type="button" id="btn-sync-cancel" class="rs-btn rs-btn--xs rs-btn--outline">Cancel</button>
                </div>
            </div>
        </div>
        <div id="sync-error-list" class="rs-mt-3 rs-text-xs rs-text-danger" style="display: none; max-height: 120px; overflow-y: auto; background: #fef2f2; padding: 8px; border-radius: 4px;"></div>
    </div>

    <!-- Reviews Grid Table Card -->
    <div class="rs-card rs-mb-6">
        <form method="GET" action="<?= \App\Environment::url('/reviews') ?>" id="reviews-filter-form">
            <div class="rs-table-wrapper">
                <table class="rs-table rs-table--hover rs-w-100" style="margin: 0;">
                    <thead>
                        <tr>
                            <?php if ($isGoogleOrFacebookChannel): ?>
                                <th style="width: 40px; text-align: center;">
                                    <input type="checkbox" id="check-all-reviews" title="Select all on this page">
                                </th>
                            <?php endif; ?>
                            <th style="width: 14%; min-width: 120px;">Alias</th>
                            <th style="width: 9%; min-width: 75px;">Channel</th>
                            <th style="width: 11%; min-width: 95px;">Rate</th>
                            <th style="width: <?= $isGoogleOrFacebookChannel ? '33%' : '37%' ?>; min-width: 180px;">Review Text</th>
                            <th style="width: 12%; min-width: 105px;">Date</th>
                            <th style="width: 10%; min-width: 75px;">Status</th>
                            <th class="rs-text-center" style="width: 120px; min-width: 120px;">Action</th>
                        </tr>
                        <tr class="rs-table__filter-row" style="background: #f8fafc;">
                            <?php if ($isGoogleOrFacebookChannel): ?>
                                <td></td>
                            <?php endif; ?>
                            <td>
                                <select name="service_config" class="rs-select rs-input rs-w-100" style="font-size: 12px; padding: 4px 6px;" onchange="this.form.submit()">
                                    <option value="">All</option>
                                    <?php foreach ($serviceConfigOptions as $cfgId => $cfgLabel): ?>
                                        <option value="<?= $cfgId ?>" <?= $filter->getServiceConfigId() === $cfgId ? 'selected' : '' ?>><?= Html::encode($cfgLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="service" class="rs-select rs-input rs-w-100" style="font-size: 12px; padding: 4px 6px;" onchange="this.form.submit()">
                                    <option value="">All</option>
                                    <?php foreach ($serviceOptions as $val => $lbl): ?>
                                        <option value="<?= $val ?>" <?= $filter->getServiceId() === $val ? 'selected' : '' ?>><?= Html::encode($lbl) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="review_rate" class="rs-select rs-input rs-w-100" style="font-size: 12px; padding: 4px 6px;" onchange="this.form.submit()">
                                    <option value="">All</option>
                                    <option value="5" <?= $filter->getReviewRate() !== null && $filter->getReviewRate() === 5.0 ? 'selected' : '' ?>>5 Stars</option>
                                    <option value="4" <?= $filter->getReviewRate() !== null && $filter->getReviewRate() === 4.0 ? 'selected' : '' ?>>4 Stars</option>
                                    <option value="3" <?= $filter->getReviewRate() !== null && $filter->getReviewRate() === 3.0 ? 'selected' : '' ?>>3 Stars</option>
                                    <option value="2" <?= $filter->getReviewRate() !== null && $filter->getReviewRate() === 2.0 ? 'selected' : '' ?>>2 Stars</option>
                                    <option value="1" <?= $filter->getReviewRate() !== null && $filter->getReviewRate() === 1.0 ? 'selected' : '' ?>>1 Star</option>
                                    <option value="0" <?= $filter->getReviewRate() !== null && $filter->getReviewRate() === 0.0 ? 'selected' : '' ?>>0 Stars (No recommendation)</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="review_text" class="rs-input rs-w-100" style="font-size: 12px; padding: 4px 8px;" placeholder="Search text..." value="<?= Html::encode($filter->getReviewText() ?? '') ?>" onkeydown="if(event.key==='Enter') this.form.submit();">
                            </td>
                            <td></td>
                            <td>
                                <select name="read" class="rs-select rs-input rs-w-100" style="font-size: 12px; padding: 4px 6px;" onchange="this.form.submit()">
                                    <option value="">All</option>
                                    <option value="0" <?= $filter->getRead() === 0 ? 'selected' : '' ?>>Unread</option>
                                    <option value="1" <?= $filter->getRead() === 1 ? 'selected' : '' ?>>Read</option>
                                    <option value="9" <?= $filter->getRead() === 9 ? 'selected' : '' ?>>Replied</option>
                                </select>
                            </td>
                            <td class="rs-text-center">
                                <button type="submit" class="rs-btn rs-btn--xs rs-btn--primary">Filter</button>
                            </td>
                        </tr>
                    </thead>
                <tbody>
                    <?php if (empty($reviews)): ?>
                        <tr>
                            <td colspan="<?= $isGoogleOrFacebookChannel ? 8 : 7 ?>" class="rs-text-center rs-py-6 rs-text-muted">
                                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;">
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                    <span>No reviews found matching the selected filter criteria.</span>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reviews as $item): ?>
                            <?php
                            $subService = $item->getSubServiceId();
                            $cfgLabel = $item->getServiceConfigLabel() ?? 'Connected Page';
                            $servLabel = $item->getServiceLabel() ?? '';
                            $subName = $item->getSubServiceName();
                            $subType = $item->getSubServiceType();

                            $aliasDisplay = $subService === null
                                ? Html::encode($cfgLabel) . ' (' . Html::encode($servLabel) . ')'
                                : Html::encode($subName ?? $cfgLabel) . ' (' . Html::encode(strtolower($subType ?? 'sub-service')) . ')';

                            $channelDisplay = $subService === null
                                ? Html::encode($servLabel)
                                : Html::encode(strtolower($subType ?? 'instagram'));

                            $isLowRate = $item->getReviewRate() !== null && floor((float) $item->getReviewRate()) <= 2.0 && $item->getType() !== Review::TYPE_MENTION;
                            $textClass = $isLowRate ? 'rs-text-danger' : '';

                            $formattedDate = date('d/m/Y H:i', strtotime($item->getReviewDate()));
                            $viewUrl = \App\Environment::url('/reviews/view?id=' . $item->getId());
                            if (!empty($query64)) {
                                $viewUrl .= '&q=' . $query64;
                            }
                            ?>
                            <tr class="<?= !$item->isServiceReady() ? 'rs-bg-danger-soft' : '' ?>">
                                <?php if ($isGoogleOrFacebookChannel): ?>
                                    <td class="rs-text-center">
                                        <input type="checkbox" class="review-checkbox" value="<?= $item->getId() ?>">
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <span class="rs-font-medium" style="font-size: 13px; word-break: break-word; line-height: 1.35; display: inline-block;"><?= $aliasDisplay ?></span>
                                    <?php if (!$item->isServiceReady()): ?>
                                        <span class="rs-badge rs-badge--danger rs-text-xs" title="Connection requires configuration">(!)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="rs-text-sm"><?= $channelDisplay ?></span>
                                </td>
                                <td>
                                    <?= $renderStars($item->getReviewRate()) ?>
                                </td>
                                <td>
                                    <div class="rs-text-sm <?= $textClass ?>" style="max-height: 4.5em; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                                        <?= Html::encode($item->getReviewText()) ?>
                                    </div>
                                    <div class="rs-text-xs rs-text-muted rs-mt-1">
                                        Author: <span class="rs-font-semibold"><?= Html::encode($item->getCustomerName()) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="rs-text-xs rs-text-muted" style="white-space: nowrap;"><?= $formattedDate ?></span>
                                </td>
                                <td>
                                    <?php if ($item->isReplied()): ?>
                                        <span class="rs-badge rs-badge--primary">REPLIED</span>
                                    <?php elseif ($item->isRead()): ?>
                                        <span class="rs-badge rs-badge--success">READ</span>
                                    <?php else: ?>
                                        <span class="rs-badge rs-badge--danger">UNREAD</span>
                                    <?php endif; ?>
                                </td>
                                <td class="rs-text-center" style="white-space: nowrap;">
                                    <div class="rs-d-flex rs-justify-content-center rs-align-items-center rs-gap-1">
                                        <a href="<?= Html::encode($viewUrl) ?>" class="rs-btn rs-btn--sm rs-btn--outline" title="Inspect Review" style="padding: 4px 8px; font-size: 12px;">
                                            View
                                        </a>
                                        <button type="button" class="rs-btn rs-btn--sm rs-btn--outline rs-btn--danger-outline js-delete-review-btn" data-id="<?= $item->getId() ?>" title="Delete Review" style="padding: 4px 8px; font-size: 12px;">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        </form>

        <!-- Dedicated Delete Review Form (Standalone to ensure standard HTML5 compatibility without nested forms) -->
        <form id="review-delete-form" method="POST" action="<?= \App\Environment::url('/reviews/delete') ?>" style="display: none;">
            <?php if ($effectiveCsrf !== ''): ?>
                <input type="hidden" name="_csrf" value="<?= Html::encode($effectiveCsrf) ?>">
            <?php endif; ?>
            <input type="hidden" name="id" id="review-delete-id" value="">
        </form>

        <!-- Table Footer / Action Bar -->
        <div class="rs-table-footer rs-d-flex rs-align-items-center rs-justify-content-between rs-p-4 rs-flex-wrap rs-gap-3">
            <div class="rs-d-flex rs-align-items-center rs-gap-3">
                <span class="rs-text-sm rs-text-muted">
                    Showing <strong><?= count($reviews) ?></strong> of <strong><?= number_format($total) ?></strong> reviews
                    <?php if ($totalPages > 1): ?>
                        (Page <?= $currentPage ?> of <?= $totalPages ?>)
                    <?php endif; ?>
                </span>
                <?php if ($isGoogleOrFacebookChannel): ?>
                    <button type="button" class="rs-btn rs-btn--info rs-btn--sm btn-reply-all" onclick="submitBulkReply();">
                        Reply all
                    </button>
                <?php endif; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="rs-pagination rs-d-flex rs-align-items-center rs-gap-1">
                    <?php
                    $activeQuery = $queryParams ?? $_GET ?? [];
                    $cleanParams = [];
                    foreach ($activeQuery as $k => $v) {
                        if ($v !== null && $v !== '' && $k !== 'page') {
                            $cleanParams[$k] = $v;
                        }
                    }
                    $buildPageUrl = static function (int $p) use ($cleanParams): string {
                        $params = array_merge($cleanParams, ['page' => $p]);
                        return \App\Environment::url('/reviews') . '?' . http_build_query($params);
                    };

                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    ?>

                    <?php if ($currentPage > 1): ?>
                        <a href="<?= $buildPageUrl(1) ?>" class="rs-btn rs-btn--sm rs-btn--outline" title="First Page">&laquo; First</a>
                        <a href="<?= $buildPageUrl($currentPage - 1) ?>" class="rs-btn rs-btn--sm rs-btn--outline" title="Previous Page">&lsaquo; Prev</a>
                    <?php endif; ?>

                    <?php if ($startPage > 1): ?>
                        <a href="<?= $buildPageUrl(1) ?>" class="rs-btn rs-btn--sm rs-btn--outline">1</a>
                        <?php if ($startPage > 2): ?>
                            <span class="rs-text-muted" style="padding: 0 4px;">...</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                        <a href="<?= $buildPageUrl($p) ?>" class="rs-btn rs-btn--sm <?= $p === $currentPage ? 'rs-btn--primary' : 'rs-btn--outline' ?>" style="min-width: 32px; text-align: center;">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <span class="rs-text-muted" style="padding: 0 4px;">...</span>
                        <?php endif; ?>
                        <a href="<?= $buildPageUrl($totalPages) ?>" class="rs-btn rs-btn--sm rs-btn--outline"><?= $totalPages ?></a>
                    <?php endif; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a href="<?= $buildPageUrl($currentPage + 1) ?>" class="rs-btn rs-btn--sm rs-btn--outline" title="Next Page">Next &rsaquo;</a>
                        <a href="<?= $buildPageUrl($totalPages) ?>" class="rs-btn rs-btn--sm rs-btn--outline" title="Last Page">Last &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
window.reviewReplyAllUrl = <?= json_encode(\App\Environment::url('/reviews/reply-all')) ?>;
window.reviewSyncUrl = <?= json_encode(\App\Environment::url('/reviews/sync')) ?>;
window.reviewSyncCsrf = <?= json_encode($effectiveCsrf) ?>;
window.rquery = '<?= Html::encode($query64 ?? '') ?>';

function triggerReviewSync() {
    const btn = document.getElementById('btn-sync-reviews');
    const textSpan = document.getElementById('sync-btn-text');
    const spinnerSpan = document.getElementById('sync-btn-spinner');
    const progressCard = document.getElementById('sync-progress-card');
    const progressBar = document.getElementById('sync-progress-bar');
    const progressPercent = document.getElementById('sync-progress-percent');
    const progressTitle = document.getElementById('sync-progress-title');
    const progressDetails = document.getElementById('sync-progress-details');
    const progressStats = document.getElementById('sync-progress-stats');
    const pauseBanner = document.getElementById('sync-pause-banner');
    const pauseMsg = document.getElementById('sync-pause-msg');
    const btnResume = document.getElementById('btn-sync-resume');
    const btnCancel = document.getElementById('btn-sync-cancel');
    const errorList = document.getElementById('sync-error-list');
    const activeWarning = document.getElementById('sync-active-warning');
    const syncInfoBanner = document.getElementById('sync-info-banner');

    if (!btn || btn.disabled) {
        return;
    }

    const confirmMessage = 'Fetch latest reviews from connected Google and Facebook accounts now?\n\n'
        + '• Manual sync fetches only the latest reviews (up to 200 per location).\n'
        + '• Please keep this browser tab open until synchronization completes.';

    if (!confirm(confirmMessage)) {
        return;
    }

    btn.disabled = true;
    if (textSpan) textSpan.style.display = 'none';
    if (spinnerSpan) spinnerSpan.style.display = 'inline';
    if (progressCard) progressCard.style.display = 'block';
    if (syncInfoBanner) syncInfoBanner.style.display = 'flex';
    if (activeWarning) activeWarning.style.display = 'flex';
    if (pauseBanner) pauseBanner.style.display = 'none';
    if (errorList) {
        errorList.style.display = 'none';
        errorList.innerHTML = '';
    }

    const channelSelect = document.querySelector('select[name="service_config"]');
    const channelId = channelSelect && channelSelect.value ? channelSelect.value : null;

    let currentLastChannelId = 0;
    let hasAttemptedRetry = false;
    let isCancelled = false;

    const accumulated = {
        totalChannels: 0,
        channelsProcessed: 0,
        fetched: 0,
        inserted: 0,
        updated: 0,
        errors: []
    };

    if (btnCancel) {
        btnCancel.onclick = function () {
            isCancelled = true;
            finishSync(accumulated, true);
        };
    }

    if (btnResume) {
        btnResume.onclick = function () {
            if (pauseBanner) pauseBanner.style.display = 'none';
            hasAttemptedRetry = false;
            runChunk(currentLastChannelId);
        };
    }

    function runChunk(lastChannelId) {
        if (isCancelled) {
            return;
        }

        const formData = new FormData();
        if (window.reviewSyncCsrf) {
            formData.append('_csrf', window.reviewSyncCsrf);
        }
        formData.append('last_channel_id', lastChannelId);
        formData.append('limit', 10);
        formData.append('max_pages', 2);
        if (channelId) {
            formData.append('channel_id', channelId);
        }

        const controller = new AbortController();
        const timeoutId = setTimeout(function () {
            controller.abort();
        }, 90000); // 90-second safety timeout per chunk

        fetch(window.reviewSyncUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData,
            signal: controller.signal
        })
        .then(function (response) {
            clearTimeout(timeoutId);
            return response.json();
        })
        .then(function (data) {
            if (!data.success) {
                throw new Error(data.error || data.message || 'Server reported an error during synchronization.');
            }

            // Reset automatic retry on successful chunk response
            hasAttemptedRetry = false;

            accumulated.totalChannels = data.total_channels || 0;
            accumulated.channelsProcessed += (data.channels_processed || 0);
            accumulated.fetched += (data.reviews_fetched || 0);
            accumulated.inserted += (data.reviews_inserted || 0);
            accumulated.updated += (data.reviews_updated || 0);
            if (data.errors && data.errors.length) {
                accumulated.errors.push(...data.errors);
            }

            currentLastChannelId = data.last_channel_id || 0;

            const total = accumulated.totalChannels > 0 ? accumulated.totalChannels : accumulated.channelsProcessed;
            const pct = total > 0 ? Math.min(100, Math.round((accumulated.channelsProcessed / total) * 100)) : 100;

            if (progressBar) progressBar.style.width = pct + '%';
            if (progressPercent) progressPercent.innerText = pct + '%';
            if (progressDetails) {
                progressDetails.innerText = 'Processed ' + accumulated.channelsProcessed + ' of ' + total + ' channel(s)...';
            }
            if (progressStats) {
                progressStats.innerText = accumulated.fetched + ' fetched • ' + accumulated.inserted + ' inserted • ' + accumulated.updated + ' updated';
            }

            if (data.has_more) {
                // Process next chunk sequentially
                runChunk(currentLastChannelId);
            } else {
                // All chunks completed
                finishSync(accumulated, false);
            }
        })
        .catch(function (err) {
            clearTimeout(timeoutId);
            console.warn('Chunk sync error at last_channel_id=' + lastChannelId + ':', err);

            // One automatic retry with 3-second delay
            if (!hasAttemptedRetry) {
                hasAttemptedRetry = true;
                if (progressDetails) {
                    progressDetails.innerText = 'Network delay. Retrying current chunk in 3 seconds...';
                }
                setTimeout(function () {
                    runChunk(lastChannelId);
                }, 3000);
            } else {
                // Second failure: show manual resume/cancel banner
                if (pauseBanner) pauseBanner.style.display = 'block';
                if (pauseMsg) {
                    pauseMsg.innerText = 'Network delay on chunk (' + (err.message || 'Timeout') + '). You can resume or cancel.';
                }
            }
        });
    }

    function finishSync(acc, cancelled) {
        btn.disabled = false;
        if (textSpan) textSpan.style.display = 'inline';
        if (spinnerSpan) spinnerSpan.style.display = 'none';

        if (progressBar) progressBar.style.width = '100%';
        if (progressPercent) progressPercent.innerText = '100%';
        if (progressTitle) {
            progressTitle.innerText = cancelled ? 'Synchronization Cancelled' : 'Synchronization Complete';
        }
        if (pauseBanner) pauseBanner.style.display = 'none';
        if (activeWarning) activeWarning.style.display = 'none';
        if (syncInfoBanner) syncInfoBanner.style.display = 'none';

        let msg = (cancelled ? 'Synchronization cancelled. ' : 'Review synchronization completed: ')
            + acc.channelsProcessed + ' channel(s) processed, '
            + acc.fetched + ' review(s) fetched, '
            + acc.inserted + ' inserted, '
            + acc.updated + ' updated.';

        if (acc.errors.length > 0) {
            msg += '\n\n' + acc.errors.length + ' channel(s) reported warnings.';
            if (errorList) {
                errorList.style.display = 'block';
                errorList.innerHTML = '<strong>Channel Warnings:</strong><br>' + acc.errors.map(function (e) {
                    return '• ' + e;
                }).join('<br>');
            }
        }

        setTimeout(function () {
            alert(msg);
            window.location.reload();
        }, 400);
    }

    // Start with initial channel ID 0
    runChunk(0);
}

function submitBulkReply() {
    const checked = Array.from(document.querySelectorAll('.review-checkbox:checked'));
    if (!checked || checked.length === 0) {
        alert('Select at least 1 review');
        return;
    }

    const ids = checked.map(cb => cb.value).join(',');
    let url = window.reviewReplyAllUrl + '?ids=' + encodeURIComponent(ids);
    if (window.rquery) {
        url += '&q=' + encodeURIComponent(window.rquery);
    }
    window.location.href = url;
}

document.addEventListener('DOMContentLoaded', function () {
    const checkAll = document.getElementById('check-all-reviews');
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('.review-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = checkAll.checked;
            });
        });
    }

    // Review delete confirmation and submission
    document.querySelectorAll('.js-delete-review-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (confirm('Are you sure you want to delete this review?')) {
                var form = document.getElementById('review-delete-form');
                var idInput = document.getElementById('review-delete-id');
                if (form && idInput) {
                    idInput.value = this.getAttribute('data-id');
                    form.submit();
                }
            }
        });
    });
});
</script>
