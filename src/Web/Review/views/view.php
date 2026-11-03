<?php

declare(strict_types=1);

use App\Domain\Review\Review;
use App\Domain\Review\ReviewReply;
use Yiisoft\Html\Html;

/**
 * @var \Yiisoft\View\WebView $this
 * @var Review $review
 * @var ReviewReply[] $replies
 * @var \App\Domain\User\User $currentUser
 * @var \App\Security\TenantContextInterface $context
 * @var \Yiisoft\Session\Flash\FlashInterface|null $flash
 * @var string|null $q
 */

$customerDisplay = !empty(trim($review->getCustomerName())) ? $review->getCustomerName() : 'Anonymous User';
$businessDisplay = $review->getServiceConfigLabel() ?? 'Connected Business';
$platformDisplay = $review->getServiceLabel() ?? 'Direct Review';

$pageTitle = "{$customerDisplay} on {$businessDisplay}";
$this->setTitle($pageTitle);

// Date formatting helper
$formatDate = static function (?string $dateStr): string {
    if (empty($dateStr)) {
        return '—';
    }
    $ts = strtotime($dateStr);
    if (!$ts || $ts <= 0 || date('Y', $ts) === '1970') {
        return '—';
    }
    return date('d M Y H:i', $ts);
};

// Avatar initial
$avatarChar = !empty(trim($review->getCustomerName())) ? strtoupper(substr(trim($review->getCustomerName()), 0, 1)) : '?';

// Star rating render helper
$renderStars = static function (?float $rate): string {
    if ($rate === null) {
        return '<span class="rs-text-muted rs-text-sm">No Rating</span>';
    }
    $rateFloat = max(0.0, min(5.0, (float) $rate));
    $html = '<div class="rs-rating-stars rs-d-flex rs-align-items-center rs-gap-1" aria-label="' . $rateFloat . ' out of 5 stars">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= (int) floor($rateFloat)) {
            $html .= '<svg width="18" height="18" viewBox="0 0 24 24" class="rs-star-filled"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>';
        } elseif ($i === (int) ceil($rateFloat) && ($rateFloat - floor($rateFloat)) > 0) {
            $pct = round(($rateFloat - floor($rateFloat)) * 100);
            $gradId = 'star-grad-' . bin2hex(random_bytes(4));
            $html .= '
            <svg width="18" height="18" viewBox="0 0 24 24" class="rs-star-half">
                <defs>
                    <linearGradient id="' . $gradId . '">
                        <stop offset="' . $pct . '%" stop-color="var(--rs-warning, #F59E0B)" />
                        <stop offset="' . $pct . '%" stop-color="var(--rs-border, #E5E7EB)" />
                    </linearGradient>
                </defs>
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" fill="url(#' . $gradId . ')"></polygon>
            </svg>';
        } else {
            $html .= '<svg width="18" height="18" viewBox="0 0 24 24" class="rs-star-empty"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>';
        }
    }
    $html .= '</div>';
    return $html;
};

// Status HTML
if ($review->isReplied()) {
    $statusBadge = '<span class="rs-badge rs-badge--primary">REPLIED</span>';
} elseif ($review->isRead()) {
    $statusBadge = '<span class="rs-badge rs-badge--success">READ</span>';
} else {
    $statusBadge = '<span class="rs-badge rs-badge--danger">UNREAD</span>';
}

$extra = $review->getExtra();
$img = null;
if (isset($extra['attachment']['media']['image'])) {
    $imgData = $extra['attachment']['media']['image'];
    $img = '<img src="' . Html::encode($imgData['src']) . '" width="' . (int) ($imgData['width'] ?? 300) . '" height="' . (int) ($imgData['height'] ?? 200) . '" class="rs-review-image rs-mt-4" style="max-width: 100%; border-radius: 8px;" />';
} elseif (isset($extra['media']) && is_string($extra['media'])) {
    $img = '<img src="' . Html::encode($extra['media']) . '" class="rs-review-image rs-mt-4" style="max-width: 100%; border-radius: 8px;" />';
}

$backUrl = '/sys-yii3/reviews';
if (!empty($q)) {
    $backUrl .= '?' . base64_decode($q);
}
?>

<link rel="stylesheet" href="/sys-yii3/themes/modern/css/view.css">

<div class="rs-page rs-p-6">
    <?php if (isset($flash)): ?>
        <?php include dirname(__DIR__, 2) . '/Shared/Components/_alerts.php'; ?>
    <?php endif; ?>

    <!-- Header Card -->
    <div class="rs-review-header rs-mb-6">
        <div class="rs-d-flex rs-justify-content-between rs-align-items-start rs-flex-wrap rs-gap-4">
            <div class="rs-flex-grow">
                <h1 class="rs-page-title rs-mb-3"><?= Html::encode($pageTitle) ?></h1>
                <div class="rs-header-meta-row rs-d-flex rs-align-items-center rs-flex-wrap rs-gap-3 rs-text-sm rs-text-muted">
                    <span class="rs-meta-item rs-font-bold rs-text-main"><?= Html::encode($businessDisplay) ?></span>
                    <span class="rs-meta-separator">•</span>
                    <span class="rs-meta-item"><?= Html::encode($platformDisplay) ?></span>
                    <span class="rs-meta-separator">•</span>
                    <span class="rs-meta-item"><?= Html::encode($formatDate($review->getReviewDate())) ?></span>
                    <?php if ($review->getType() === Review::TYPE_REVIEW): ?>
                        <span class="rs-meta-separator">•</span>
                        <span class="rs-meta-item rs-d-flex rs-align-items-center rs-gap-1">
                            <?= $renderStars($review->getReviewRate()) ?>
                            <?php if ($review->getReviewRate() !== null): ?>
                                <span class="rs-font-semibold rs-text-main"><?= Html::encode((string) $review->getReviewRate()) ?></span>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                    <span class="rs-meta-separator">•</span>
                    <span class="rs-meta-item rs-d-flex rs-align-items-center">
                        <?= $statusBadge ?>
                    </span>
                </div>
            </div>
            <div class="rs-header-actions rs-d-flex rs-align-items-center rs-gap-2">
                <a href="<?= Html::encode($backUrl) ?>" class="rs-btn rs-btn--outline">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Back to Reviews
                </a>
                <form method="POST" action="/sys-yii3/reviews/delete" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this review? This action cannot be undone.');">
                    <input type="hidden" name="id" value="<?= $review->getId() ?>">
                    <button type="submit" class="rs-btn rs-btn--outline rs-btn--danger-outline" title="Delete Review">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 5px;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="rs-dashboard-grid">
        <!-- LEFT COLUMN: Main Details & Composer (8 cols) -->
        <div class="rs-col-span-8 rs-d-flex rs-flex-column rs-gap-5">
            <!-- Customer Review Card -->
            <div class="rs-card rs-customer-review-card">
                <div class="rs-card__body rs-p-6">
                    <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-flex-wrap rs-gap-4 rs-mb-6">
                        <div class="rs-d-flex rs-align-items-center rs-gap-3">
                            <div class="rs-avatar rs-avatar--lg rs-avatar-initials">
                                <span class="rs-avatar__initial"><?= Html::encode($avatarChar) ?></span>
                            </div>
                            <div>
                                <h2 class="rs-review-customer-name rs-font-bold rs-text-lg rs-mb-1 rs-m-0"><?= Html::encode($customerDisplay) ?></h2>
                                <div class="rs-d-flex rs-align-items-center rs-gap-2 rs-text-sm rs-text-muted">
                                    <span><?= Html::encode($platformDisplay) ?></span>
                                    <span>•</span>
                                    <span><?= Html::encode($formatDate($review->getReviewDate())) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="rs-d-flex rs-align-items-center rs-gap-2">
                            <?php if ($review->getType() === Review::TYPE_REVIEW): ?>
                                <?= $renderStars($review->getReviewRate()) ?>
                            <?php endif; ?>
                            <?= $statusBadge ?>
                        </div>
                    </div>

                    <div class="rs-review-content">
                        <div class="rs-review-text rs-text-main rs-line-height-lg" style="font-size: 16px; line-height: 1.6;">
                            <?= !empty(trim($review->getReviewText())) ? nl2br(Html::encode($review->getReviewText())) : '<span class="rs-text-muted rs-font-italic">No review text provided.</span>' ?>
                        </div>
                        <?php if ($img !== null): ?>
                            <div class="rs-review-image-wrapper rs-mt-4">
                                <?= $img ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Single Review Reply Composer Card -->
            <?php
            $isPlugin = str_contains(strtolower($platformDisplay), 'plugin');
            $canReplyDirectly = $review->getType() !== Review::TYPE_MENTION && $review->isServiceReady() && !$isPlugin;
            ?>

            <?php if ($canReplyDirectly): ?>
                <div class="rs-card rs-reply-composer-card">
                    <div class="rs-card__header rs-py-3 rs-d-flex rs-justify-content-between rs-align-items-center">
                        <h3 class="rs-card__title" style="font-size: 16px;">
                            <?= $review->getType() === Review::TYPE_POST_REPLY ? 'Reply Comment' : 'Reply Review' ?>
                        </h3>
                        <?php if ($review->getType() === Review::TYPE_POST_REPLY && isset($extra['url'])): ?>
                            <a href="<?= Html::encode($extra['url']) ?>" target="_blank" rel="noopener noreferrer" class="rs-btn rs-btn--sm rs-btn--outline">
                                View Post on Facebook ↗
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="rs-card__body rs-p-6">
                        <form id="single-reply-form" method="POST" action="/sys-yii3/reviews/view?id=<?= $review->getId() ?>" class="rs-form">
                            <input type="hidden" name="id" value="<?= $review->getId() ?>">
                            <div class="rs-form-group rs-mb-4">
                                <label for="single_reply_text" class="rs-label rs-font-semibold rs-mb-2">Your Response <span class="rs-text-danger">*</span></label>
                                <textarea id="single_reply_text" name="reply_text" rows="8" class="rs-input rs-w-100" style="resize: vertical; font-family: inherit; line-height: 1.5;" placeholder="Write a professional, personalized reply to <?= Html::encode($customerDisplay) ?>..." required></textarea>
                            </div>

                            <div class="rs-d-flex rs-justify-content-between rs-align-items-center">
                                <span class="rs-text-xs rs-text-muted">
                                    This reply will be published directly to <strong><?= Html::encode($platformDisplay) ?></strong> and saved to the conversation history.
                                </span>
                                <button type="submit" id="btn-submit-single-reply" class="rs-btn rs-btn--primary">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                                    Send Reply
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <!-- Platform Notice Card for Mentions / Plugins / Unconnected services matching Yii 2 -->
                <div class="rs-card rs-p-5" style="background-color: var(--rs-bg-app, #f8fafc); border: 1px solid var(--rs-border, #e2e8f0);">
                    <div class="rs-d-flex rs-align-items-start rs-gap-3">
                        <div class="rs-text-primary" style="margin-top: 2px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        </div>
                        <div>
                            <h4 class="rs-font-bold rs-text-sm rs-text-main rs-m-0 rs-mb-1">
                                <?php if ($review->getType() === Review::TYPE_MENTION): ?>
                                    Social Mention Interaction
                                <?php elseif (!$review->isServiceReady()): ?>
                                    Channel Configuration Required
                                <?php else: ?>
                                    External Platform Reply
                                <?php endif; ?>
                            </h4>
                            <p class="rs-text-muted rs-text-xs rs-m-0 rs-mb-3">
                                <?php if ($review->getType() === Review::TYPE_MENTION): ?>
                                    Mentions cannot be replied to via automated API. Please engage directly with the user on their native platform.
                                <?php elseif (!$review->isServiceReady()): ?>
                                    This channel is not currently authorized with active API credentials. Re-authenticate in channel settings to enable direct replies.
                                <?php else: ?>
                                    This channel uses an external plugin integration. Manage responses through the plugin interface.
                                <?php endif; ?>
                            </p>
                            <?php if (isset($extra['url']) && is_string($extra['url']) && $extra['url'] !== ''): ?>
                                <a href="<?= Html::encode($extra['url']) ?>" target="_blank" rel="noopener noreferrer" class="rs-btn rs-btn--sm rs-btn--outline">
                                    Open Post on Native Platform ↗
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Review Insights Card -->
            <div class="rs-card rs-review-insights-card">
                <div class="rs-card__header rs-py-3">
                    <h3 class="rs-card__title" style="font-size: 15px;">Review Metadata & Attributes</h3>
                </div>
                <div class="rs-card__body rs-p-5">
                    <div class="rs-insights-grid">
                        <div class="rs-insight-item">
                            <span class="rs-insight-label">Business</span>
                            <span class="rs-insight-value"><?= Html::encode($businessDisplay) ?></span>
                        </div>
                        <div class="rs-insight-item">
                            <span class="rs-insight-label">Customer</span>
                            <span class="rs-insight-value"><?= Html::encode($customerDisplay) ?></span>
                        </div>
                        <div class="rs-insight-item">
                            <span class="rs-insight-label">Platform</span>
                            <span class="rs-insight-value"><?= Html::encode($platformDisplay) ?></span>
                        </div>
                        <div class="rs-insight-item">
                            <span class="rs-insight-label">Status</span>
                            <span class="rs-insight-value"><?= $statusBadge ?></span>
                        </div>
                        <div class="rs-insight-item">
                            <span class="rs-insight-label">Created Date</span>
                            <span class="rs-insight-value"><?= Html::encode($formatDate($review->getReviewDate())) ?></span>
                        </div>
                        <div class="rs-insight-item">
                            <span class="rs-insight-label">Spam Assessment</span>
                            <span class="rs-insight-value">
                                <?php if ($review->getSpamStatus() === Review::SPAM_HIGH_RISK): ?>
                                    <span class="rs-badge rs-badge--danger">High Risk</span>
                                <?php elseif ($review->getSpamStatus() === Review::SPAM_SUSPICIOUS): ?>
                                    <span class="rs-badge rs-badge--warning">Suspicious</span>
                                <?php else: ?>
                                    <span class="rs-badge rs-badge--success">Normal</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Google Moderation Card -->
            <?php if (
                (stripos($platformDisplay, 'google') !== false || $review->getServiceConfigId() === 1) &&
                $review->getType() === Review::TYPE_REVIEW
            ): ?>
                <div class="rs-card rs-moderation-card">
                    <div class="rs-card__body rs-p-5">
                        <div class="rs-d-flex rs-align-items-start rs-gap-3">
                            <div class="rs-moderation-icon-wrapper rs-text-danger" style="margin-top: 2px;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                            </div>
                            <div class="rs-flex-grow">
                                <div class="rs-d-flex rs-align-items-center rs-gap-2 rs-mb-1">
                                    <h4 class="rs-font-bold rs-text-base rs-m-0">Google Moderation</h4>
                                    <?php if ($review->getSpamStatus() === Review::SPAM_HIGH_RISK): ?>
                                        <span class="rs-badge rs-badge--danger">High Risk Review</span>
                                    <?php elseif ($review->getSpamStatus() === Review::SPAM_SUSPICIOUS): ?>
                                        <span class="rs-badge rs-badge--warning">Suspicious Review</span>
                                    <?php endif; ?>
                                </div>
                                <p class="rs-text-muted rs-text-sm rs-mb-3">
                                    You can inspect or report this review directly on your Google Business Profile.
                                </p>
                                <?php
                                $queryStr = !empty($review->getServiceConfigLabel()) ? $review->getServiceConfigLabel() : '';
                                $googleReportUrl = $queryStr !== ''
                                    ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($queryStr)
                                    : 'https://business.google.com/reviews';
                                ?>
                                <a href="<?= Html::encode($googleReportUrl) ?>" target="_blank" rel="noopener noreferrer" class="rs-btn rs-btn--outline rs-btn--danger-outline rs-btn--sm">
                                    Report to Google
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left: 4px;"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT COLUMN: Timeline (4 cols) -->
        <div class="rs-col-span-4 rs-d-flex rs-flex-column rs-gap-5">
            <!-- Activity Timeline Card -->
            <div class="rs-card rs-timeline-card">
                <div class="rs-card__header rs-py-3">
                    <h3 class="rs-card__title" style="font-size: 15px;">Conversation History (<?= count($replies) ?>)</h3>
                </div>
                <div class="rs-card__body rs-p-5">
                    <?php if (!empty($replies)): ?>
                        <div class="rs-timeline">
                            <?php foreach ($replies as $reply): ?>
                                <?php $isSentByStaff = $reply->getType() === ReviewReply::TYPE_SENT; ?>
                                <div class="rs-timeline__item rs-mb-4" style="border-left: 3px solid <?= $isSentByStaff ? 'var(--rs-primary, #2563eb)' : '#10b981' ?>; padding-left: 12px;">
                                    <div class="rs-timeline__header rs-d-flex rs-justify-content-between rs-align-items-center rs-mb-1">
                                        <div class="rs-d-flex rs-align-items-center rs-gap-2">
                                            <span class="rs-timeline__author rs-font-bold rs-text-sm rs-text-main">
                                                <?= Html::encode($reply->getAuthorLabel()) ?>
                                            </span>
                                            <span class="rs-badge <?= $isSentByStaff ? 'rs-badge--primary' : 'rs-badge--success' ?> rs-text-xs">
                                                <?= $isSentByStaff ? 'Staff' : 'Customer' ?>
                                            </span>
                                        </div>
                                        <span class="rs-timeline__date rs-text-xs rs-text-muted">
                                            <?= Html::encode($formatDate($reply->getReplyDate())) ?>
                                        </span>
                                    </div>
                                    <div class="rs-timeline__body rs-text-sm rs-text-main rs-line-height-md rs-mt-2">
                                        <?= nl2br(Html::encode($reply->getReplyText())) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="rs-empty-state rs-text-center rs-p-6" style="border: 1px dashed var(--rs-border, #cbd5e1); border-radius: 8px;">
                            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" class="rs-mb-2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                            <h4 class="rs-font-bold rs-text-sm rs-text-main rs-m-0">No replies yet</h4>
                            <p class="rs-text-muted rs-text-xs rs-m-0 rs-mt-1">This review has not received any replies yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const replyForm = document.getElementById('single-reply-form');
    const submitBtn = document.getElementById('btn-submit-single-reply');
    const replyText = document.getElementById('single_reply_text');

    if (replyForm && submitBtn) {
        let isSubmitting = false;
        replyForm.addEventListener('submit', function (e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }

            if (!replyText || replyText.value.trim() === '') {
                e.preventDefault();
                alert('Please enter a response message.');
                return false;
            }

            isSubmitting = true;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="rs-spinner rs-mr-2"></span> Sending...';
            return true;
        });
    }
});
</script>
