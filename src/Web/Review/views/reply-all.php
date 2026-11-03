<?php

declare(strict_types=1);

use App\Domain\Review\Review;
use Yiisoft\Html\Html;

/**
 * @var \Yiisoft\View\WebView $this
 * @var Review[] $reviews
 * @var string $idsStr
 * @var string $replyText
 * @var string $q
 * @var \Yiisoft\Session\Flash\FlashInterface|null $flash
 */

$this->setTitle('Reply Reviews (Bulk)');

// Star rating renderer
$renderStars = static function (?float $rate): string {
    if ($rate === null) {
        return '<span class="rs-text-muted">-</span>';
    }
    $starsHtml = '';
    for ($i = 1; $i <= 5; $i++) {
        $fill = $i <= (int) floor($rate) ? '#F59E0B' : '#CBD5E1';
        $starsHtml .= '<svg width="12" height="12" viewBox="0 0 24 24" fill="' . $fill . '" stroke="' . $fill . '"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>';
    }
    return '<div class="rs-d-flex rs-align-items-center rs-gap-1">' . $starsHtml . ' <span class="rs-font-semibold rs-text-xs rs-ml-1">' . number_format($rate, 1) . '</span></div>';
};

$backUrl = '/sys-yii3/reviews';
if (!empty($q)) {
    $backUrl .= '?' . base64_decode($q);
}
?>

<div class="rs-page rs-p-6">
    <?php if (isset($flash)): ?>
        <?php include dirname(__DIR__, 2) . '/Shared/Components/_alerts.php'; ?>
    <?php endif; ?>

    <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mb-6">
        <div>
            <h1 class="rs-page-title rs-m-0">Bulk Reply Reviews</h1>
            <p class="rs-text-muted rs-m-0 rs-mt-1">Compose a personalized response template for the <strong><?= count($reviews) ?></strong> selected reviews.</p>
        </div>
        <div>
            <a href="<?= Html::encode($backUrl) ?>" class="rs-btn rs-btn--outline">
                Cancel & Return
            </a>
        </div>
    </div>

    <!-- Preview Table Card -->
    <div class="rs-card rs-mb-6">
        <div class="rs-card__header rs-py-3">
            <h3 class="rs-card__title" style="font-size: 15px;">Target Reviews (<?= count($reviews) ?>)</h3>
        </div>
        <div class="rs-table-wrapper">
            <table class="rs-table rs-w-100" style="margin: 0;">
                <thead>
                    <tr>
                        <th style="width: 18%;">Alias</th>
                        <th style="width: 12%;">Channel</th>
                        <th style="width: 16%;">Customer</th>
                        <th style="width: 12%;">Rating</th>
                        <th style="width: 30%;">Review Text</th>
                        <th style="width: 12%;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews as $item): ?>
                        <?php
                        $cfgLabel = $item->getServiceConfigLabel() ?? 'Page';
                        $servLabel = $item->getServiceLabel() ?? 'Channel';
                        $isReady = $item->isServiceReady();
                        ?>
                        <tr class="<?= !$isReady ? 'rs-bg-danger-soft' : '' ?>">
                            <td>
                                <span class="rs-font-medium" style="font-size: 13px;"><?= Html::encode($cfgLabel) ?> (<?= Html::encode($servLabel) ?>)</span>
                                <?php if (!$isReady): ?>
                                    <span class="rs-badge rs-badge--danger rs-text-xs" title="Not connected or missing tokens">(!) Not Ready</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="rs-text-sm"><?= Html::encode($servLabel) ?></span>
                            </td>
                            <td>
                                <span class="rs-font-semibold rs-text-sm"><?= Html::encode($item->getCustomerName()) ?></span>
                            </td>
                            <td>
                                <?= $renderStars($item->getReviewRate()) ?>
                            </td>
                            <td>
                                <div class="rs-text-sm" style="max-height: 3.5em; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                    <?= Html::encode($item->getReviewText()) ?>
                                </div>
                            </td>
                            <td>
                                <span class="rs-text-xs rs-text-muted"><?= date('d/m/Y H:i', strtotime($item->getReviewDate())) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Compose Reply Form Card -->
    <div class="rs-card">
        <div class="rs-card__header rs-py-3">
            <h3 class="rs-card__title" style="font-size: 15px;">Compose Bulk Response</h3>
        </div>
        <div class="rs-card__body rs-p-6">
            <div class="rs-mb-4">
                <label class="rs-label rs-mb-2 rs-font-semibold">Insert Dynamic Placeholder:</label>
                <div class="rs-d-flex rs-flex-wrap rs-gap-2">
                    <button type="button" class="rs-btn rs-btn--sm rs-btn--outline btn-add-placeholder" data-token="{{customer_name}}">
                        + {{customer_name}}
                    </button>
                    <button type="button" class="rs-btn rs-btn--sm rs-btn--outline btn-add-placeholder" data-token="{{service_config_label}}">
                        + {{service_config_label}}
                    </button>
                    <button type="button" class="rs-btn rs-btn--sm rs-btn--outline btn-add-placeholder" data-token="{{review_rate}}">
                        + {{review_rate}}
                    </button>
                    <button type="button" class="rs-btn rs-btn--sm rs-btn--outline btn-add-placeholder" data-token="{{review_date}}">
                        + {{review_date}}
                    </button>
                </div>
                <p class="rs-text-xs rs-text-muted rs-mt-2 rs-mb-0">
                    Click any tag to insert it at your cursor position. Each review will have these tags automatically replaced with the customer's actual data.
                </p>
            </div>

            <form id="bulk-reply-form" method="POST" action="/sys-yii3/reviews/reply-all" class="rs-form">
                <input type="hidden" name="ids" value="<?= Html::encode($idsStr) ?>">
                <input type="hidden" name="q" value="<?= Html::encode($q) ?>">

                <div class="rs-form-group rs-mb-5">
                    <label for="bulk_reply_text" class="rs-label rs-font-semibold rs-mb-2">Message Template <span class="rs-text-danger">*</span></label>
                    <textarea id="bulk_reply_text" name="reply_text" rows="12" class="rs-input rs-w-100" style="resize: vertical; font-family: inherit; line-height: 1.5;" placeholder="Hello {{customer_name}}, thank you for sharing your experience with {{service_config_label}}..." required><?= Html::encode($replyText) ?></textarea>
                </div>

                <div class="rs-d-flex rs-align-items-center rs-gap-3">
                    <button type="submit" id="btn-submit-bulk-reply" class="rs-btn rs-btn--primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                        Send Replies
                    </button>
                    <a href="<?= Html::encode($backUrl) ?>" class="rs-btn rs-btn--outline">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const textarea = document.getElementById('bulk_reply_text');
    const placeholderButtons = document.querySelectorAll('.btn-add-placeholder');
    const form = document.getElementById('bulk-reply-form');
    const submitBtn = document.getElementById('btn-submit-bulk-reply');

    placeholderButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const token = this.getAttribute('data-token');
            if (!token || !textarea) return;

            const startPos = textarea.selectionStart;
            const endPos = textarea.selectionEnd;
            const val = textarea.value;

            textarea.value = val.substring(0, startPos) + token + val.substring(endPos, val.length);
            textarea.focus();
            textarea.selectionStart = textarea.selectionEnd = startPos + token.length;
        });
    });

    let isSubmitting = false;
    form.addEventListener('submit', function (e) {
        if (isSubmitting) {
            e.preventDefault();
            return false;
        }

        if (textarea.value.trim() === '') {
            e.preventDefault();
            alert('Please enter a reply message before sending.');
            return false;
        }

        isSubmitting = true;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="rs-spinner rs-mr-2"></span> Sending replies sequentially (rate-limited)...';
        return true;
    });
});
</script>
