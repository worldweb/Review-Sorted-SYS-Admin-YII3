<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var \Yiisoft\View\WebView $this
 * @var \App\Domain\User\User $currentUser
 * @var \App\Security\TenantContextInterface $context
 * @var string|null $csrfToken
 */

$this->setTitle('Export Reviews');
?>

<div class="rs-page">
    <div class="rs-page-header rs-mb-6 rs-d-flex rs-justify-content-between rs-align-items-center rs-flex-wrap rs-gap-3">
        <div>
            <h1 class="rs-page-title rs-m-0">Export Reviews</h1>
            <p class="rs-text-muted rs-m-0 rs-mt-1">Download review and customer feedback data in CSV format.</p>
        </div>
        <div>
            <a href="<?= \App\Environment::url('/reviews') ?>" class="rs-btn rs-btn--outline">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Back to Reviews
            </a>
        </div>
    </div>

    <div class="rs-card" style="max-width: 620px; margin: 0 auto;">
        <div class="rs-card__header">
            <h3 class="rs-card__title">Export Options</h3>
        </div>
        <div class="rs-card__body rs-p-6">
            <form method="POST" action="<?= \App\Environment::url('/reviews/export') ?>" class="rs-form">
                <?php if (!empty($csrfToken)): ?>
                    <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
                <?php endif; ?>

                <!-- Date Range Selection -->
                <div class="rs-form-group rs-mb-4">
                    <label class="rs-label rs-mb-2">Date Range</label>
                    <select class="rs-select rs-input rs-w-100" name="ReviewSearchExport[date_range]" id="export-date-range">
                        <option value="30">Last 30 days</option>
                        <option value="60">Last 60 days</option>
                        <option value="90">Last 90 days</option>
                        <option value="all" selected>All time</option>
                        <option value="custom">Custom Date Range</option>
                    </select>
                </div>

                <!-- Custom Date Range Inputs (Collapsible) -->
                <div id="custom-date-group" class="rs-form-group rs-mb-4" style="display: none;">
                    <div class="rs-row rs-d-flex rs-gap-3">
                        <div style="flex: 1;">
                            <label class="rs-label rs-text-xs rs-mb-1">From Date</label>
                            <input type="date" name="review_date_min" class="rs-input rs-w-100">
                        </div>
                        <div style="flex: 1;">
                            <label class="rs-label rs-text-xs rs-mb-1">To Date</label>
                            <input type="date" name="review_date_max" class="rs-input rs-w-100">
                        </div>
                    </div>
                </div>

                <!-- Export Format Selection -->
                <div class="rs-form-group rs-mb-5">
                    <label class="rs-label rs-mb-2">File Format</label>
                    <div class="rs-d-flex rs-gap-4 rs-align-items-center">
                        <label class="rs-d-flex rs-align-items-center rs-gap-2" style="cursor: pointer;">
                            <input type="radio" name="format" value="csv" checked>
                            <span class="rs-font-medium">CSV Format</span>
                            <span class="rs-badge rs-badge--soft rs-badge--primary rs-text-xs">Excel Compatible (UTF-8 BOM)</span>
                        </label>
                    </div>
                    <div class="rs-text-xs rs-text-muted rs-mt-2">
                        Includes: Date, Customer, Page, Rating, Status, Review Text.
                    </div>
                </div>

                <div class="rs-d-flex rs-justify-content-end rs-gap-3 rs-pt-3" style="border-top: 1px solid var(--rs-border, #e2e8f0);">
                    <a href="<?= \App\Environment::url('/reviews') ?>" class="rs-btn rs-btn--outline">Cancel</a>
                    <button type="submit" class="rs-btn rs-btn--primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        Generate Export
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var select = document.getElementById('export-date-range');
    var customGroup = document.getElementById('custom-date-group');
    if (select && customGroup) {
        select.addEventListener('change', function() {
            customGroup.style.display = this.value === 'custom' ? 'block' : 'none';
        });
    }
});
</script>
