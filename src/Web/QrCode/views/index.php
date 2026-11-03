<?php

declare(strict_types=1);

use App\Domain\QrCode\QrCode;
use Yiisoft\Html\Html;

/**
 * QR Code Studio Index View (Yii 3 Modern Theme)
 * Displays paginated listing of client QR codes with real-time search,
 * status badges, scan metrics, and direct action shortcuts.
 *
 * @var \Yiisoft\View\WebView $this
 * @var QrCode[] $items
 * @var int $total
 * @var int $page
 * @var int $pageSize
 * @var string|null $search
 * @var \App\Domain\User\User $currentUser
 * @var \App\Security\TenantContextInterface $context
 * @var \Yiisoft\Session\Flash\FlashInterface|null $flash
 * @var string|null $csrfToken
 * @var string|null $csrf
 */

$this->setTitle('QR Codes');

$totalPages = max(1, (int) ceil($total / max(1, $pageSize)));
$currentPage = max(1, min($page, $totalPages));
$startItem = $total > 0 ? (($currentPage - 1) * $pageSize) + 1 : 0;
$endItem = min($total, $currentPage * $pageSize);

// Page Header
$title = 'QR Codes';
$description = 'Manage static and dynamic QR codes, landing pages, and scan analytics.';
$actions = [
    '<a href="/sys-yii3/qrcode/create" class="rs-btn rs-btn--primary"><i class="fa-solid fa-plus rs-mr-1"></i> Create QR Code</a>'
];
include __DIR__ . '/../../Shared/Components/_page_header.php';
?>

<div class="rs-card">
    <div class="rs-card__header rs-d-flex rs-justify-content-between rs-align-items-center rs-flex-wrap rs-gap-3" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #E2E8F0);">
        <form method="GET" action="/sys-yii3/qrcode/index" class="rs-d-flex rs-align-items-center rs-gap-2" style="margin: 0;">
            <div class="rs-input-group" style="width: 320px; max-width: 100%;">
                <input
                    type="text"
                    name="search"
                    class="rs-input"
                    placeholder="Filter visible QR codes..."
                    id="qr-search-input"
                    value="<?= Html::encode($search ?? '') ?>"
                    onkeyup="filterQrCodesTable()"
                    autocomplete="off"
                >
            </div>
            <?php if (!empty($search)): ?>
                <a href="/sys-yii3/qrcode/index" class="rs-btn rs-btn--sm rs-btn--ghost rs-text-muted" title="Clear filter">Clear</a>
            <?php endif; ?>
        </form>

        <div class="rs-text-sm rs-text-muted">
            Total QR Codes: <strong class="rs-text-main"><?= number_format($total) ?></strong>
        </div>
    </div>

    <div class="rs-table-wrapper" style="overflow-x: auto;">
        <?php if (empty($items)): ?>
            <div class="rs-text-center rs-p-8" style="padding: 48px 24px;">
                <div style="font-size: 3rem; margin-bottom: 12px; opacity: 0.6;">📱</div>
                <h3 class="rs-font-bold rs-text-main rs-mb-2">No QR codes found</h3>
                <p class="rs-text-muted rs-mb-4">
                    <?= !empty($search) ? 'No QR codes match your search criteria.' : 'Create your first branded QR code to direct clients to your review profiles or custom URLs.' ?>
                </p>
                <?php if (empty($search)): ?>
                    <a href="/sys-yii3/qrcode/create" class="rs-btn rs-btn--primary">Create QR Code</a>
                <?php else: ?>
                    <a href="/sys-yii3/qrcode/index" class="rs-btn rs-btn--outline">Reset Search</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table class="rs-table rs-table--hover" id="qr-codes-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="padding: 12px 16px; text-align: left; font-weight: 600; font-size: 13px; color: var(--rs-text-secondary, #7B8A8B); border-bottom: 1px solid var(--rs-border, #E2E8F0);">Name & Destination</th>
                        <th style="padding: 12px 16px; text-align: left; font-weight: 600; font-size: 13px; color: var(--rs-text-secondary, #7B8A8B); border-bottom: 1px solid var(--rs-border, #E2E8F0); width: 120px;">Status</th>
                        <th style="padding: 12px 16px; text-align: center; font-weight: 600; font-size: 13px; color: var(--rs-text-secondary, #7B8A8B); border-bottom: 1px solid var(--rs-border, #E2E8F0); width: 100px;">Scans</th>
                        <th style="padding: 12px 16px; text-align: left; font-weight: 600; font-size: 13px; color: var(--rs-text-secondary, #7B8A8B); border-bottom: 1px solid var(--rs-border, #E2E8F0); width: 170px;">Created At</th>
                        <th style="padding: 12px 16px; text-align: right; font-weight: 600; font-size: 13px; color: var(--rs-text-secondary, #7B8A8B); border-bottom: 1px solid var(--rs-border, #E2E8F0); width: 220px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $model): ?>
                        <?php
                        $status = $model->getStatus();
                        $isActive = ($status === QrCode::STATUS_ACTIVE);
                        $badgeClass = $isActive ? 'rs-badge--success' : 'rs-badge--default';
                        $statusLabel = $isActive ? 'Active' : 'Not Active';
                        $createdDate = $model->getCreatedAt() > 0 ? date('d M Y, H:i', $model->getCreatedAt()) : '—';
                        $destinationUrl = $model->getUrl() ?? '';
                        ?>
                        <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                            <td style="padding: 14px 16px;">
                                <div class="rs-font-semibold rs-text-main" style="font-size: 14px;">
                                    <a href="/sys-yii3/qrcode/view?id=<?= $model->getId() ?>" style="color: var(--rs-primary, #234137); text-decoration: none;">
                                        <?= Html::encode($model->getName() ?? ('QR Code #' . $model->getId())) ?>
                                    </a>
                                </div>
                                <?php if (!empty($destinationUrl)): ?>
                                    <div class="rs-text-xs rs-text-muted" style="margin-top: 4px; max-width: 380px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <i class="fa-solid fa-link rs-mr-1" style="font-size: 11px;"></i>
                                        <a href="<?= Html::encode($destinationUrl) ?>" target="_blank" rel="noopener" style="color: var(--rs-text-secondary, #7B8A8B); text-decoration: none;">
                                            <?= Html::encode($destinationUrl) ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 14px 16px;">
                                <span class="rs-badge <?= $badgeClass ?>" style="display: inline-block;">
                                    <?= Html::encode($statusLabel) ?>
                                </span>
                            </td>
                            <td style="padding: 14px 16px; text-align: center;">
                                <span class="rs-font-semibold rs-text-main" style="font-size: 14px;">
                                    <?= number_format((int) ($model->getScanCount() ?? 0)) ?>
                                </span>
                            </td>
                            <td style="padding: 14px 16px;">
                                <span class="rs-text-sm rs-text-muted">
                                    <?= Html::encode($createdDate) ?>
                                </span>
                            </td>
                            <td style="padding: 14px 16px; text-align: right;">
                                <div class="rs-d-flex rs-align-items-center rs-justify-content-end rs-gap-2">
                                    <a href="/sys-yii3/qrcode/view?id=<?= $model->getId() ?>" class="rs-btn rs-btn--sm rs-btn--outline" title="View details and scan analytics">
                                        Analytics
                                    </a>
                                    <a href="/sys-yii3/qrcode/update?id=<?= $model->getId() ?>" class="rs-btn rs-btn--sm rs-btn--outline" title="Edit QR code configuration">
                                        Edit
                                    </a>
                                    <form method="POST" action="/sys-yii3/qrcode/delete?id=<?= $model->getId() ?>" style="display: inline; margin: 0;" onsubmit="return confirm('Are you sure you want to delete this QR code?');">
                                        <?php 
                                        $effectiveCsrf = $csrfToken ?? $csrf ?? '';
                                        if (!empty($effectiveCsrf)): 
                                        ?>
                                            <input type="hidden" name="_csrf" value="<?= Html::encode($effectiveCsrf) ?>">
                                        <?php endif; ?>
                                        <input type="hidden" name="id" value="<?= $model->getId() ?>">
                                        <button type="submit" class="rs-btn rs-btn--sm rs-btn--ghost rs-text-danger" title="Delete QR Code">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Table Footer with Summary and Pager -->
            <div class="rs-table-footer rs-d-flex rs-align-items-center rs-justify-content-between rs-p-4 rs-flex-wrap rs-gap-3" style="padding: 16px 20px; border-top: 1px solid var(--rs-border, #E2E8F0);">
                <div class="rs-text-sm rs-text-muted">
                    Showing <strong class="rs-text-main"><?= $startItem ?></strong> to <strong class="rs-text-main"><?= $endItem ?></strong> of <strong class="rs-text-main"><?= number_format($total) ?></strong> QR codes
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="rs-pagination rs-d-flex rs-gap-1">
                        <?php if ($currentPage > 1): ?>
                            <a href="/sys-yii3/qrcode/index?page=<?= $currentPage - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="rs-btn rs-btn--sm rs-btn--outline">&laquo; Prev</a>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <?php if ($p === $currentPage): ?>
                                <span class="rs-btn rs-btn--sm rs-btn--primary" style="cursor: default;"><?= $p ?></span>
                            <?php elseif ($p === 1 || $p === $totalPages || abs($p - $currentPage) <= 2): ?>
                                <a href="/sys-yii3/qrcode/index?page=<?= $p ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="rs-btn rs-btn--sm rs-btn--outline"><?= $p ?></a>
                            <?php elseif (abs($p - $currentPage) === 3): ?>
                                <span class="rs-text-muted rs-px-2" style="line-height: 32px;">...</span>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <a href="/sys-yii3/qrcode/index?page=<?= $currentPage + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="rs-btn rs-btn--sm rs-btn--outline">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function filterQrCodesTable() {
    var input = document.getElementById('qr-search-input');
    if (!input) return;
    var filter = input.value.toUpperCase();
    var table = document.getElementById('qr-codes-table');
    if (!table) return;
    var rows = table.getElementsByTagName('tbody')[0] ? table.getElementsByTagName('tbody')[0].getElementsByTagName('tr') : [];
    for (var i = 0; i < rows.length; i++) {
        var td = rows[i].getElementsByTagName('td')[0];
        if (td) {
            var text = td.textContent || td.innerText;
            if (text.toUpperCase().indexOf(filter) > -1) {
                rows[i].style.display = '';
            } else {
                rows[i].style.display = 'none';
            }
        }
    }
}
</script>
