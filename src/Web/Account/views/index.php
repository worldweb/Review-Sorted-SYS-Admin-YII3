<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Workspaces & Billing Index View (Yii 3 Modern Theme)
 * Rendered within Main Authenticated Layout.
 *
 * @var \App\Domain\Account\Account[] $accounts
 * @var int $total
 * @var \App\Domain\Account\AccountSearchFilter $filter
 * @var array $billingData
 * @var \App\Domain\User\User $currentUser
 * @var \App\Security\TenantContextInterface $context
 * @var string|null $csrfToken
 * @var Yiisoft\View\WebView $this
 */

$this->setTitle('Workspaces & Billing');

$totalPages = max(1, (int) ceil($total / $filter->pageSize));
$currentPage = $filter->page;

// Reusable Page Header
$actionButtons = [];
if ($currentUser->isAdmin()) {
    $actionButtons[] = '<a href="/sys-yii3/accounts/create" class="rs-btn rs-btn--primary">+ Create Workspace</a>';
}

$title = 'Workspaces & Billing';
$description = 'Manage billing, subscription tiers, seat allocation, and workspace members.';
$actions = $actionButtons;
include __DIR__ . '/../../Shared/Components/_page_header.php';
?>

<!-- Modular Enterprise Cards (Billing, Seats, Payment) matching Yii 2 Modern Theme -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px;">
    <!-- Billing Card -->
    <div class="rs-card">
        <div class="rs-card__header" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #E2E8F0); display: flex; justify-content: space-between; align-items: center;">
            <h3 class="rs-card__title" style="margin: 0; font-size: 16px; font-weight: 600; color: var(--rs-primary, #234137);">Billing Plan</h3>
            <?php if ($billingData['status'] === 'active' || $billingData['status'] === 'succeeded'): ?>
                <span class="rs-badge rs-badge--success">Active</span>
            <?php else: ?>
                <span class="rs-badge rs-badge--default"><?= Html::encode(ucfirst((string) $billingData['status'])) ?></span>
            <?php endif; ?>
        </div>
        <div class="rs-card__body rs-p-4" style="padding: 20px;">
            <div style="font-size: 18px; font-weight: 700; color: var(--rs-primary, #234137); margin-bottom: 6px;">
                <?= Html::encode((string) $billingData['planName']) ?>
            </div>
            <?php if (!empty($billingData['renewalDate'])): ?>
                <div style="font-size: 13px; color: var(--rs-text-secondary, #7B8A8B);">
                    Renews on <?= Html::encode((string) $billingData['renewalDate']) ?>
                </div>
            <?php else: ?>
                <div style="font-size: 13px; color: var(--rs-text-secondary, #7B8A8B);">Standard Free Tier</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Seat Usage Card -->
    <div class="rs-card">
        <div class="rs-card__header" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #E2E8F0);">
            <h3 class="rs-card__title" style="margin: 0; font-size: 16px; font-weight: 600; color: var(--rs-primary, #234137);">Seat Usage</h3>
        </div>
        <div class="rs-card__body rs-p-4" style="padding: 20px;">
            <div style="font-size: 24px; font-weight: 700; color: var(--rs-primary, #234137); margin-bottom: 6px;">
                <?= (int) $billingData['usedSeats'] ?> / <?= (int) $billingData['allowedSeats'] ?>
            </div>
            <?php $remaining = max(0, (int) $billingData['allowedSeats'] - (int) $billingData['usedSeats']); ?>
            <div style="font-size: 13px; color: var(--rs-text-secondary, #7B8A8B);">
                <?= $remaining ?> seats remaining on current plan.
            </div>
        </div>
    </div>

    <!-- Payment Method Card -->
    <div class="rs-card">
        <div class="rs-card__header" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #E2E8F0);">
            <h3 class="rs-card__title" style="margin: 0; font-size: 16px; font-weight: 600; color: var(--rs-primary, #234137);">Payment Method</h3>
        </div>
        <div class="rs-card__body rs-p-4" style="padding: 20px;">
            <?php if (!empty($billingData['paymentLast4'])): ?>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="rs-badge rs-badge--default"><?= Html::encode((string) $billingData['paymentBrand']) ?></span>
                    <span style="font-family: monospace; font-size: 14px; font-weight: 600;">**** <?= Html::encode((string) $billingData['paymentLast4']) ?></span>
                </div>
            <?php else: ?>
                <div style="color: var(--rs-text-secondary, #7B8A8B); font-size: 14px;">No payment method on file</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Filter Form -->
<div class="rs-card rs-mb-4" style="margin-bottom: 20px; padding: 16px 20px;">
    <form method="get" action="/sys-yii3/accounts" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 240px;">
            <label for="filter-q" class="rs-label" style="font-size: 12px; margin-bottom: 4px;">Search Workspace Name, Contact, or Email</label>
            <input type="text" id="filter-q" name="q" class="rs-input" value="<?= Html::encode($filter->search) ?>" placeholder="Search workspaces...">
        </div>

        <div style="width: 180px;">
            <label for="filter-status" class="rs-label" style="font-size: 12px; margin-bottom: 4px;">Status</label>
            <select id="filter-status" name="status" class="rs-input" style="height: 44px;">
                <option value="">All Statuses</option>
                <option value="1" <?= $filter->status === 1 ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= $filter->status === 0 ? 'selected' : '' ?>>Suspended</option>
            </select>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="rs-btn rs-btn--primary" style="height: 44px;">Filter</button>
            <a href="/sys-yii3/accounts" class="rs-btn rs-btn--ghost" style="height: 44px; display: inline-flex; align-items: center; text-decoration: none;">Reset</a>
        </div>
    </form>
</div>

<!-- Data Grid -->
<div class="rs-card">
    <div class="rs-table-wrapper" style="overflow-x: auto;">
        <table class="rs-table rs-table--hover" style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="background: rgba(0,0,0,0.02);">
                    <th style="width: 50px; text-align: center;">#</th>
                    <th>Workspace / Business</th>
                    <th>Primary Contact</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Seats</th>
                    <th>Created At</th>
                    <th style="width: 140px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($accounts)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 32px; color: var(--rs-text-secondary, #7B8A8B);">
                            No workspaces found matching the criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($accounts as $idx => $acc): ?>
                        <tr>
                            <td style="text-align: center; color: var(--rs-text-secondary, #7B8A8B); font-size: 13px;">
                                <?= $filter->getOffset() + $idx + 1 ?>
                            </td>
                            <td>
                                <a href="/sys-yii3/accounts/view?id=<?= $acc->getId() ?>" style="color: var(--rs-primary, #234137); font-weight: 600; text-decoration: none;">
                                    <?= Html::encode($acc->getBusinessName() ?? "Workspace #{$acc->getId()}") ?>
                                </a>
                            </td>
                            <td>
                                <?= Html::encode(trim(($acc->getFirstName() ?? '') . ' ' . ($acc->getLastName() ?? '')) ?: '-') ?>
                            </td>
                            <td style="color: var(--rs-text-secondary, #7B8A8B);">
                                <?= Html::encode($acc->getEmail() ?? '-') ?>
                            </td>
                            <td>
                                <span class="rs-badge <?= $acc->isActive() ? 'rs-badge--success' : 'rs-badge--danger' ?>">
                                    <?= Html::encode($acc->getStatusName()) ?>
                                </span>
                            </td>
                            <td>
                                <?= $acc->getMaxUsers() ?> seats
                            </td>
                            <td style="color: var(--rs-text-secondary, #7B8A8B); font-size: 13px;">
                                <?= Html::encode($acc->getCreatedAtStr()) ?>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="/sys-yii3/accounts/view?id=<?= $acc->getId() ?>" class="rs-btn rs-btn--sm rs-btn--ghost" style="text-decoration: none; padding: 4px 8px; font-size: 12px;">View</a>
                                <a href="/sys-yii3/accounts/update?id=<?= $acc->getId() ?>" class="rs-btn rs-btn--sm rs-btn--secondary" style="text-decoration: none; padding: 4px 8px; font-size: 12px;">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Table Footer / Pagination -->
    <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-top: 1px solid var(--rs-border, #E2E8F0); font-size: 14px; color: var(--rs-text-secondary, #7B8A8B);">
        <div>
            Showing <strong><?= count($accounts) ?></strong> of <strong><?= $total ?></strong> workspaces
        </div>
        <?php if ($totalPages > 1): ?>
            <div style="display: flex; gap: 6px;">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?= $currentPage - 1 ?>&q=<?= urlencode($filter->search) ?>&status=<?= $filter->status ?? '' ?>" class="rs-btn rs-btn--sm rs-btn--ghost" style="text-decoration: none;">&laquo; Prev</a>
                <?php endif; ?>
                <span style="display: flex; align-items: center; padding: 0 8px;">Page <?= $currentPage ?> of <?= $totalPages ?></span>
                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?= $currentPage + 1 ?>&q=<?= urlencode($filter->search) ?>&status=<?= $filter->status ?? '' ?>" class="rs-btn rs-btn--sm rs-btn--ghost" style="text-decoration: none;">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
