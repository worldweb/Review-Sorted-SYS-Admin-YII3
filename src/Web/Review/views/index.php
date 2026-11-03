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
 */

$this->setTitle('Reviews & Mentions');

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

<div class="rs-page rs-p-6">
    <?php if (isset($flash)): ?>
        <?php include dirname(__DIR__, 2) . '/Shared/Components/_alerts.php'; ?>
    <?php endif; ?>

    <!-- Header Section with KPI Metric Cards -->
    <div class="reviews-header rs-mb-6">
        <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-flex-wrap rs-gap-3 rs-mb-4">
            <div>
                <h1 class="rs-page-title rs-m-0">Reviews & Mentions</h1>
                <p class="rs-text-muted rs-m-0 rs-mt-1">Manage and inspect customer feedback across all connected channels.</p>
            </div>
            <div class="rs-d-flex rs-align-items-center rs-gap-2">
                <?php if ($isGoogleOrFacebookChannel): ?>
                    <button type="button" class="rs-btn rs-btn--info btn-reply-all" onclick="submitBulkReply();">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                        Reply all
                    </button>
                <?php endif; ?>
                <a href="/sys-yii3/reviews/export" class="rs-btn rs-btn--secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Export Data
                </a>
            </div>
        </div>

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

    <!-- Filter Toolbar Card -->
    <div class="rs-card rs-mb-5">
        <div class="rs-card__header rs-py-3">
            <h3 class="rs-card__title" style="font-size: 15px;">Filter & Search Reviews</h3>
        </div>
        <div class="rs-card__body rs-p-4">
            <form method="GET" action="/sys-yii3/reviews" class="rs-form">
                <div class="rs-row rs-d-flex rs-flex-wrap rs-gap-3 rs-align-items-end">
                    <div style="flex: 1 1 160px;">
                        <label class="rs-label rs-text-xs rs-mb-1">Channel</label>
                        <select name="service" class="rs-select rs-input rs-w-100">
                            <option value="" <?= $filter->getServiceId() === null ? 'selected' : '' ?>>All Channels</option>
                            <?php foreach ($serviceOptions as $val => $lbl): ?>
                                <option value="<?= $val ?>" <?= $filter->getServiceId() === $val ? 'selected' : '' ?>><?= Html::encode($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="flex: 1 1 180px;">
                        <label class="rs-label rs-text-xs rs-mb-1">Page / Alias</label>
                        <select name="service_config" class="rs-select rs-input rs-w-100">
                            <option value="" <?= $filter->getServiceConfigId() === null ? 'selected' : '' ?>>All Pages</option>
                            <?php foreach ($serviceConfigOptions as $cfgId => $cfgLabel): ?>
                                <option value="<?= $cfgId ?>" <?= $filter->getServiceConfigId() === $cfgId ? 'selected' : '' ?>><?= Html::encode($cfgLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="flex: 1 1 120px;">
                        <label class="rs-label rs-text-xs rs-mb-1">Rating</label>
                        <select name="review_rate" class="rs-select rs-input rs-w-100">
                            <option value="" <?= $filter->getReviewRate() === null ? 'selected' : '' ?>>All Ratings</option>
                            <option value="5" <?= $filter->getReviewRate() === 5.0 ? 'selected' : '' ?>>5 Stars</option>
                            <option value="4" <?= $filter->getReviewRate() === 4.0 ? 'selected' : '' ?>>4 Stars</option>
                            <option value="3" <?= $filter->getReviewRate() === 3.0 ? 'selected' : '' ?>>3 Stars</option>
                            <option value="2" <?= $filter->getReviewRate() === 2.0 ? 'selected' : '' ?>>2 Stars</option>
                            <option value="1" <?= $filter->getReviewRate() === 1.0 ? 'selected' : '' ?>>1 Star</option>
                        </select>
                    </div>

                    <div style="flex: 1 1 130px;">
                        <label class="rs-label rs-text-xs rs-mb-1">Status</label>
                        <select name="read" class="rs-select rs-input rs-w-100">
                            <option value="" <?= $filter->getRead() === null ? 'selected' : '' ?>>All Statuses</option>
                            <option value="0" <?= $filter->getRead() === 0 ? 'selected' : '' ?>>Unread</option>
                            <option value="1" <?= $filter->getRead() === 1 ? 'selected' : '' ?>>Read</option>
                            <option value="9" <?= $filter->getRead() === 9 ? 'selected' : '' ?>>Replied</option>
                        </select>
                    </div>

                    <div style="flex: 1 1 160px;">
                        <label class="rs-label rs-text-xs rs-mb-1">Customer</label>
                        <input type="text" name="customer_name" class="rs-input rs-w-100" placeholder="Search customer..." value="<?= Html::encode($filter->getCustomerName() ?? '') ?>">
                    </div>

                    <div style="flex: 1 1 200px;">
                        <label class="rs-label rs-text-xs rs-mb-1">Review Text</label>
                        <input type="text" name="review_text" class="rs-input rs-w-100" placeholder="Keyword in review..." value="<?= Html::encode($filter->getReviewText() ?? '') ?>">
                    </div>

                    <div class="rs-d-flex rs-gap-2">
                        <button type="submit" class="rs-btn rs-btn--primary">Filter</button>
                        <a href="/sys-yii3/reviews" class="rs-btn rs-btn--outline">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Notice if Bulk Reply is available or prompt to filter -->
    <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mb-3">
        <div>
            <?php if ($isGoogleOrFacebookChannel): ?>
                <span class="rs-text-sm rs-text-muted">
                    <span class="rs-badge rs-badge--primary rs-mr-1">Bulk Reply Ready</span>
                    Select reviews using the checkboxes below, then click <strong>Reply all</strong>.
                </span>
            <?php else: ?>
                <span class="rs-text-sm rs-text-muted rs-font-italic">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -2px; margin-right: 4px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    To use Bulk Reply, please filter reviews by Google or Facebook channel above.
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reviews Grid Table Card -->
    <div class="rs-card rs-mb-6">
        <div class="rs-table-wrapper">
            <table class="rs-table rs-table--hover rs-w-100" style="margin: 0;">
                <thead>
                    <tr>
                        <?php if ($isGoogleOrFacebookChannel): ?>
                            <th style="width: 40px; text-align: center;">
                                <input type="checkbox" id="check-all-reviews" title="Select all on this page">
                            </th>
                        <?php endif; ?>
                        <th style="width: 14%;">Alias</th>
                        <th style="width: 10%;">Channel</th>
                        <th style="width: 12%;">Rate</th>
                        <th style="width: 8%;">Risk</th>
                        <th style="width: <?= $isGoogleOrFacebookChannel ? '28%' : '32%' ?>;">Review Text</th>
                        <th style="width: 10%;">Date</th>
                        <th style="width: 8%;">Status</th>
                        <th class="rs-text-center" style="width: 120px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)): ?>
                        <tr>
                            <td colspan="<?= $isGoogleOrFacebookChannel ? 9 : 8 ?>" class="rs-text-center rs-py-6 rs-text-muted">
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
                            $viewUrl = '/sys-yii3/reviews/view?id=' . $item->getId();
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
                                    <span class="rs-font-medium" style="font-size: 13px;"><?= $aliasDisplay ?></span>
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
                                    <?php if ($item->getSpamStatus() === Review::SPAM_HIGH_RISK): ?>
                                        <span class="rs-badge rs-badge--danger">High Risk</span>
                                    <?php elseif ($item->getSpamStatus() === Review::SPAM_SUSPICIOUS): ?>
                                        <span class="rs-badge rs-badge--warning">Suspicious</span>
                                    <?php else: ?>
                                        <span class="rs-text-muted rs-text-xs">-</span>
                                    <?php endif; ?>
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
                                <td class="rs-text-center">
                                    <div class="rs-d-flex rs-justify-content-center rs-align-items-center rs-gap-1">
                                        <a href="<?= Html::encode($viewUrl) ?>" class="rs-btn rs-btn--sm rs-btn--outline" title="Inspect Review">
                                            View
                                        </a>
                                        <form method="POST" action="/sys-yii3/reviews/delete" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this review?');">
                                            <input type="hidden" name="id" value="<?= $item->getId() ?>">
                                            <button type="submit" class="rs-btn rs-btn--sm rs-btn--outline rs-btn--danger-outline" title="Delete Review">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Table Footer / Action Bar -->
        <div class="rs-table-footer rs-d-flex rs-align-items-center rs-justify-content-between rs-p-4 rs-flex-wrap rs-gap-3">
            <div class="rs-d-flex rs-align-items-center rs-gap-3">
                <span class="rs-text-sm rs-text-muted">
                    Showing <strong><?= count($reviews) ?></strong> of <strong><?= number_format($total) ?></strong> reviews
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
                    $queryParams = $_GET;
                    $buildPageUrl = static function (int $p) use ($queryParams): string {
                        $params = array_merge($queryParams, ['page' => $p]);
                        return '/sys-yii3/reviews?' . http_build_query($params);
                    };

                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    ?>

                    <?php if ($currentPage > 1): ?>
                        <a href="<?= $buildPageUrl(1) ?>" class="rs-btn rs-btn--sm rs-btn--outline">« First</a>
                        <a href="<?= $buildPageUrl($currentPage - 1) ?>" class="rs-btn rs-btn--sm rs-btn--outline">‹ Prev</a>
                    <?php endif; ?>

                    <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                        <a href="<?= $buildPageUrl($p) ?>" class="rs-btn rs-btn--sm <?= $p === $currentPage ? 'rs-btn--primary' : 'rs-btn--outline' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a href="<?= $buildPageUrl($currentPage + 1) ?>" class="rs-btn rs-btn--sm rs-btn--outline">Next ›</a>
                        <a href="<?= $buildPageUrl($totalPages) ?>" class="rs-btn rs-btn--sm rs-btn--outline">Last »</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
window.reviewReplyAllUrl = '/sys-yii3/reviews/reply-all';
window.rquery = '<?= Html::encode($query64 ?? '') ?>';

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
});
</script>
