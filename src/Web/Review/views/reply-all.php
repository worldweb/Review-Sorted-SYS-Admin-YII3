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
 * @var string|null $csrfToken
 * @var \Yiisoft\Session\Flash\FlashInterface|null $flash
 */

$this->setTitle('Bulk Reply');

$decodedQ = !empty($q) ? trim(base64_decode($q)) : '';
$backUrl = \App\Environment::url('/reviews');
if ($decodedQ !== '') {
    $backUrl .= '?' . $decodedQ;
}
?>

<div class="rs-page">
    <?php if (isset($flash)): ?>
        <?php include dirname(__DIR__, 2) . '/Shared/Components/_alerts.php'; ?>
    <?php endif; ?>

    <div class="rs-page-header rs-mb-6 rs-d-flex rs-justify-content-between rs-align-items-center rs-flex-wrap rs-gap-3">
        <div>
            <h1 class="rs-page-title rs-m-0">Bulk Reply</h1>
            <p class="rs-text-muted rs-m-0 rs-mt-1">Apply a standardized response to the selected reviews.</p>
        </div>
        <div>
            <a href="<?= Html::encode($backUrl) ?>" class="rs-btn rs-btn--outline">
                Back to Reviews
            </a>
        </div>
    </div>

    <!-- Selected Reviews Preview Card -->
    <div class="rs-card rs-mb-6">
        <div class="rs-card__header">
            <h3 class="rs-card__title">Selected Reviews</h3>
        </div>
        <div class="rs-table-wrapper">
            <table class="rs-table rs-table--hover rs-w-100">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th style="width: 100px;">Rating</th>
                        <th>Review Text</th>
                        <th style="width: 140px;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews as $item): ?>
                        <tr>
                            <td>
                                <span class="rs-font-medium"><?= Html::encode($item->getCustomerName()) ?></span>
                            </td>
                            <td>
                                <?= $item->getReviewRate() ? Html::encode((string)$item->getReviewRate()) . ' ★' : '-' ?>
                            </td>
                            <td>
                                <?= Html::encode($item->getReviewText()) ?>
                            </td>
                            <td>
                                <?= date('d/m/Y H:i', strtotime($item->getReviewDate())) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Reply Form Card -->
    <div class="rs-card" style="max-width: 800px; margin: 0 auto;">
        <div class="rs-card__header">
            <h3 class="rs-card__title">Reply Message</h3>
        </div>
        <div class="rs-card__body rs-p-5">
            <div class="rs-mb-3">
                <button type="button" class="rs-btn rs-btn--sm rs-btn--outline btn-add-field" data-token="{{customer_name}}">{{customer_name}}</button>
                <span class="rs-text-muted rs-text-xs" style="margin-left: 8px;">Click tag to insert customer name placeholder.</span>
            </div>

            <form id="reply-form" method="POST" action="<?= \App\Environment::url('/reviews/reply-all') ?>" class="rs-form">
                <?php if (!empty($csrfToken)): ?>
                    <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
                <?php endif; ?>
                <input type="hidden" name="ids" value="<?= Html::encode($idsStr) ?>">
                <input type="hidden" name="q" value="<?= Html::encode($q) ?>">

                <div class="rs-form-group rs-mb-4">
                    <label class="rs-label rs-mb-2" for="reviewmultireplyform-reply_text">Your Message</label>
                    <textarea
                        class="rs-textarea rs-input rs-w-100"
                        rows="6"
                        id="reviewmultireplyform-reply_text"
                        name="reply_text"
                        placeholder="Write your reply message... e.g. Thank you for your feedback, {{customer_name}}!"
                        required
                    ><?= Html::encode($replyText) ?></textarea>
                </div>

                <div class="rs-help rs-mb-4 rs-text-muted rs-text-xs">
                    This message will be dispatched to all selected reviews.
                </div>

                <div class="rs-d-flex rs-justify-content-end rs-gap-3">
                    <a href="<?= Html::encode($backUrl) ?>" class="rs-btn rs-btn--outline">Cancel</a>
                    <button type="submit" id="btn-submit-bulk-reply" class="rs-btn rs-btn--primary btn-submit-bulk-reply" name="send-button">
                        Send Bulk Reply
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Processing Overlay Indicator -->
    <div id="bulk-reply-loader" class="rs-loader-overlay rs-loader-overlay--transparent" style="display: none; position: fixed; inset: 0; z-index: 99999; background: rgba(15, 23, 42, 0.7); align-items: center; justify-content: center; flex-direction: column;">
        <div class="rs-spinner rs-spinner--lg" style="width: 48px; height: 48px; border: 4px solid #f3f3f3; border-top: 4px solid #2563eb; border-radius: 50%; animation: rsSpin 1s linear infinite;"></div>
        <div class="rs-text-center rs-mt-4" style="max-width: 480px; padding: 0 24px; color: #ffffff;">
            <h3 class="rs-font-bold rs-text-white" style="margin: 16px 0 8px 0; font-size: 20px; color: #ffffff;">
                Sending replies...
            </h3>
            <p class="rs-text-muted" style="margin: 0; font-size: 14px; line-height: 1.5; color: #cbd5e1;">
                Please do not close or refresh this page while your replies are being dispatched.
            </p>
        </div>
    </div>
</div>

<style>
@keyframes rsSpin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const textarea = document.getElementById('reviewmultireplyform-reply_text');
    const addFieldBtns = document.querySelectorAll('.btn-add-field');
    const form = document.getElementById('reply-form');
    const submitBtn = document.getElementById('btn-submit-bulk-reply');
    const loader = document.getElementById('bulk-reply-loader');

    addFieldBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const token = this.getAttribute('data-token') || this.textContent.trim();
            if (!token || !textarea) return;

            const startPos = textarea.selectionStart || 0;
            const endPos = textarea.selectionEnd || 0;
            const val = textarea.value;

            textarea.value = val.substring(0, startPos) + token + val.substring(endPos);
            textarea.focus();
            const newPos = startPos + token.length;
            textarea.setSelectionRange(newPos, newPos);
        });
    });

    let isSubmitting = false;
    if (form) {
        form.addEventListener('submit', function (e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }

            if (!textarea || textarea.value.trim() === '') {
                e.preventDefault();
                alert('Please enter a reply message before sending.');
                return false;
            }

            isSubmitting = true;
            if (submitBtn) {
                submitBtn.disabled = true;
            }
            if (loader) {
                loader.style.display = 'flex';
            }
            return true;
        });
    }
});
</script>
