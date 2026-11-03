<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Workspaces & Billing Index View (Yii 3 Modern Theme)
 * Rendered within Main Authenticated Layout matching Yii 2 Modern Theme.
 *
 * @var \App\Domain\Account\Account[] $accounts
 * @var int $total
 * @var \App\Domain\Account\AccountSearchFilter $filter
 * @var array $billingData
 * @var \App\Domain\User\User $currentUser
 * @var \App\Security\TenantContextInterface $context
 * @var string|null $csrfToken
 * @var array<\App\Domain\Subscription\Plan> $availablePlans
 * @var Yiisoft\View\WebView $this
 */

$this->setTitle('Workspaces & Billing');

$totalPages = max(1, (int) ceil($total / $filter->pageSize));
$currentPage = $filter->page;

// Reusable Page Header Actions
$actions = [];
if ($currentUser->isAdmin()) {
    $actions[] = '<a href="/sys-yii3/accounts/export" class="rs-btn rs-btn--outline">Export</a>';
    $actions[] = '<a href="/sys-yii3/accounts/create" class="rs-btn rs-btn--primary">Create Workspace</a>';
}

$title = 'Workspaces & Billing';
$description = 'Manage billing, subscriptions, and workspace members.';
include __DIR__ . '/../../Shared/Components/_page_header.php';
?>

<div class="rs-page">
    <!-- Modular Enterprise Cards (Dashboard) -->
    <div class="rs-d-grid rs-grid-cols-12 rs-gap-6 rs-mb-6">
        <!-- Billing Card -->
        <div class="rs-col-4">
            <div class="rs-card">
                <div class="rs-card__header rs-d-flex rs-justify-content-between rs-align-items-center">
                    <h3 class="rs-card__title">Billing Plan</h3>
                    <?php if ($billingData['status'] === 'active' || $billingData['status'] === 'succeeded'): ?>
                        <span class="rs-badge rs-badge--success">Active</span>
                    <?php else: ?>
                        <span class="rs-badge rs-badge--default"><?= Html::encode(ucfirst((string) ($billingData['status'] ?? 'Free'))) ?></span>
                    <?php endif; ?>
                </div>
                <div class="rs-card__body rs-p-4">
                    <div class="rs-d-flex rs-align-items-center rs-justify-content-between rs-mb-2">
                        <span class="rs-font-medium rs-text-md"><?= Html::encode((string) ($billingData['planName'] ?? 'Standard Plan')) ?></span>
                    </div>
                    <?php if (!empty($billingData['renewalDate'])): ?>
                        <div class="rs-text-muted rs-text-sm">Renews on <?= Html::encode((string) $billingData['renewalDate']) ?></div>
                    <?php else: ?>
                        <div class="rs-text-muted rs-text-sm">Standard Free Tier</div>
                    <?php endif; ?>
                </div>
                <div class="rs-card__footer rs-p-4 rs-border-top">
                    <?php if ($currentUser->isAdmin() || $currentUser->isAccountAdmin()): ?>
                        <?php if (empty($billingData['hasSubscription']) || $billingData['status'] !== 'active'): ?>
                            <a href="#available-plans-section" class="rs-btn rs-btn--sm rs-btn--primary">Select Plan</a>
                        <?php else: ?>
                            <button type="button" class="rs-btn rs-btn--sm rs-btn--outline" disabled title="Plan upgrade available in future release">Upgrade</button>
                            <button type="button" class="rs-btn rs-btn--sm rs-btn--ghost rs-text-danger" disabled title="Cancellation available in future release">Cancel</button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Seat Usage Card -->
        <div class="rs-col-4">
            <div class="rs-card rs-card--stat">
                <div class="rs-card__header">
                    <h3 class="rs-card__title">Seat Usage</h3>
                </div>
                <div class="rs-card__body rs-p-4">
                    <div class="rs-d-flex rs-align-items-center rs-justify-content-between">
                        <div>
                            <div class="rs-stat-number rs-text-lg rs-font-bold"><?= (int) ($billingData['usedSeats'] ?? 1) ?> / <?= (int) ($billingData['allowedSeats'] ?? 5) ?></div>
                        </div>
                    </div>
                    <?php $remaining = max(0, (int) ($billingData['allowedSeats'] ?? 5) - (int) ($billingData['usedSeats'] ?? 1)); ?>
                    <p class="rs-text-sm rs-text-muted rs-mt-3"><?= $remaining ?> seats remaining on current plan.</p>
                </div>
            </div>
        </div>

        <!-- Payment Method Card -->
        <div class="rs-col-4">
            <div class="rs-card">
                <div class="rs-card__header">
                    <h3 class="rs-card__title">Payment Method</h3>
                </div>
                <div class="rs-card__body rs-p-4">
                    <?php if (!empty($billingData['paymentLast4'])): ?>
                        <div class="rs-d-flex rs-align-items-center rs-gap-3">
                            <div class="rs-badge rs-badge--default"><?= Html::encode((string) $billingData['paymentBrand']) ?></div>
                            <span class="rs-font-monospace">**** <?= Html::encode((string) $billingData['paymentLast4']) ?></span>
                        </div>
                    <?php else: ?>
                        <div class="rs-text-muted">No payment method on file</div>
                    <?php endif; ?>
                </div>
                <div class="rs-card__footer rs-p-4 rs-border-top">
                    <?php if ($currentUser->isAdmin()): ?>
                        <button type="button" class="rs-btn rs-btn--sm rs-btn--outline" disabled title="Payment method update available in future release">Update Card</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Restored Available Subscription Plans Section -->
    <?php if (($currentUser->isAdmin() || $currentUser->isAccountAdmin()) && !empty($availablePlans)): ?>
    <div id="available-plans-section" class="rs-mb-6">
        <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mb-4">
            <div>
                <h2 class="rs-text-lg rs-font-bold rs-mb-1">Available Subscription Plans</h2>
                <p class="rs-text-sm rs-text-muted">Select a plan to activate platform features and capabilities.</p>
            </div>
        </div>

        <div class="rs-d-grid rs-grid-cols-12 rs-gap-6">
            <?php foreach ($availablePlans as $plan): ?>
                <?php
                    $isCurrentActivePlan = (string) ($billingData['planName'] ?? '') === $plan->getPlanName();
                    $isFreePlan = (float) $plan->getPlanPrice() === 0.0;
                ?>
                <div class="rs-col-4">
                    <div class="rs-card rs-h-100 rs-d-flex rs-flex-column rs-justify-content-between <?= $isCurrentActivePlan ? 'rs-border-primary' : '' ?>">
                        <div class="rs-card__header rs-d-flex rs-justify-content-between rs-align-items-center">
                            <h3 class="rs-card__title rs-font-bold"><?= Html::encode($plan->getPlanName()) ?></h3>
                            <?php if ($isCurrentActivePlan): ?>
                                <span class="rs-badge rs-badge--success">Active</span>
                            <?php elseif ($isFreePlan): ?>
                                <span class="rs-badge rs-badge--default">Free Tier</span>
                            <?php endif; ?>
                        </div>
                        <div class="rs-card__body rs-p-4 rs-flex-grow-1">
                            <div class="rs-mb-4">
                                <span class="rs-text-2xl rs-font-bold">$<?= Html::encode($plan->getPlanPrice()) ?></span>
                                <span class="rs-text-muted rs-text-sm">/ <?= Html::encode(ucfirst($plan->getPlanLimit() ?: 'month')) ?></span>
                            </div>

                            <div class="rs-features-list">
                                <h4 class="rs-text-xs rs-text-muted rs-font-bold rs-text-uppercase rs-mb-2">Included Features</h4>
                                <ul class="rs-list-unstyled rs-text-sm rs-mb-0" style="padding-left: 0; list-style: none;">
                                    <?php
                                        $features = $plan->getFeaturesList();
                                        if (!empty($features)):
                                            foreach ($features as $feature):
                                    ?>
                                         <li class="rs-d-flex rs-align-items-center rs-gap-2 rs-mb-2">
                                             <span style="color: #10b981;">&#10003;</span>
                                             <span><?= Html::encode($feature) ?></span>
                                         </li>
                                    <?php
                                            endforeach;
                                        else:
                                    ?>
                                         <li class="rs-text-muted">Standard platform access</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        <div class="rs-card__footer rs-p-4 rs-border-top">
                            <?php if ($isCurrentActivePlan): ?>
                                <button type="button" class="rs-btn rs-btn--success rs-w-100" disabled>&#10003; Currently Active</button>
                            <?php elseif ($isFreePlan): ?>
                                <button type="button" class="rs-btn rs-btn--outline rs-w-100" disabled>Free Tier</button>
                            <?php else: ?>
                                <button type="button" class="rs-btn rs-btn--primary rs-w-100" onclick="alert('Stripe checkout integration will be triggered for ' + <?= json_encode($plan->getPlanName()) ?>);">
                                    Upgrade to <?= Html::encode($plan->getPlanName()) ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Enterprise GridView matching Yii 2 Modern Theme -->
    <div class="rs-card">
        <form method="get" action="/sys-yii3/accounts">
            <div class="rs-card__header rs-d-flex rs-justify-content-between rs-align-items-center">
                <div class="rs-d-flex rs-gap-2">
                    <div class="rs-input-group" style="width: 300px;">
                        <input type="text" name="q" class="rs-input" value="<?= Html::encode($filter->search) ?>" placeholder="Search workspaces...">
                    </div>
                    <button type="submit" class="rs-btn rs-btn--primary">Search</button>
                    <?php if ($filter->search !== '' || $filter->status !== null || $filter->businessName !== '' || $filter->firstName !== '' || $filter->email !== ''): ?>
                        <a href="/sys-yii3/accounts" class="rs-btn rs-btn--ghost">Reset</a>
                    <?php endif; ?>
                </div>
                <div class="rs-d-flex rs-gap-2">
                    <button type="button" class="rs-btn rs-btn--outline">Density: Comfort</button>
                    <button type="button" class="rs-btn rs-btn--outline">Columns</button>
                </div>
            </div>

            <div class="rs-table-wrapper">
                <table class="rs-table rs-table--hover">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">#</th>
                            <th>Workspace / Business</th>
                            <th>Primary Contact</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Seats</th>
                            <th>Created At</th>
                            <th style="width: 140px; text-align: right;">Actions</th>
                        </tr>
                        <tr class="filters" style="background-color: #f8fafc;">
                            <th></th>
                            <th>
                                <input type="text" name="business_name" class="rs-input rs-input--sm" value="<?= Html::encode($filter->businessName) ?>" placeholder="Filter business...">
                            </th>
                            <th>
                                <input type="text" name="first_name" class="rs-input rs-input--sm" value="<?= Html::encode($filter->firstName) ?>" placeholder="Filter contact...">
                            </th>
                            <th>
                                <input type="text" name="email" class="rs-input rs-input--sm" value="<?= Html::encode($filter->email) ?>" placeholder="Filter email...">
                            </th>
                            <th>
                                <select name="status" class="rs-input rs-input--sm" onchange="this.form.submit()">
                                    <option value="">All</option>
                                    <option value="1" <?= $filter->status === 1 ? 'selected' : '' ?>>Active</option>
                                    <option value="0" <?= $filter->status === 0 ? 'selected' : '' ?>>Suspended</option>
                                </select>
                            </th>
                            <th></th>
                            <th></th>
                            <th style="text-align: right; white-space: nowrap;">
                                <button type="submit" class="rs-btn rs-btn--sm rs-btn--primary">Filter</button>
                                <a href="/sys-yii3/accounts" class="rs-btn rs-btn--sm rs-btn--ghost">Reset</a>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($accounts)): ?>
                            <tr>
                                <td colspan="8" class="rs-text-center rs-p-6 rs-text-muted">
                                    No workspaces found matching the criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($accounts as $idx => $acc): ?>
                                <tr>
                                    <td class="rs-text-center rs-text-muted rs-text-sm">
                                        <?= $filter->getOffset() + $idx + 1 ?>
                                    </td>
                                    <td>
                                        <a href="/sys-yii3/accounts/view?id=<?= $acc->getId() ?>" class="rs-font-medium rs-text-primary" style="text-decoration: none;">
                                            <?= Html::encode($acc->getBusinessName() ?? "Workspace #{$acc->getId()}") ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?= Html::encode(trim(($acc->getFirstName() ?? '') . ' ' . ($acc->getLastName() ?? '')) ?: '-') ?>
                                    </td>
                                    <td class="rs-text-muted">
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
                                    <td class="rs-text-muted rs-text-sm">
                                        <?= Html::encode($acc->getCreatedAtStr()) ?>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <a href="/sys-yii3/accounts/view?id=<?= $acc->getId() ?>" class="rs-btn rs-btn--sm rs-btn--outline">View</a>
                                        <a href="/sys-yii3/accounts/update?id=<?= $acc->getId() ?>" class="rs-btn rs-btn--sm rs-btn--secondary">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Table Footer / Pagination -->
            <div class="rs-table-footer rs-d-flex rs-align-items-center rs-justify-content-between rs-p-4">
                <div class="rs-text-sm rs-text-muted">
                    Showing <strong><?= count($accounts) ?></strong> of <strong><?= $total ?></strong> workspaces
                </div>
                <?php if ($totalPages > 1): ?>
                    <div class="rs-d-flex rs-gap-2 rs-align-items-center">
                        <?php if ($currentPage > 1): ?>
                            <a href="?page=<?= $currentPage - 1 ?>&q=<?= urlencode($filter->search) ?>&business_name=<?= urlencode($filter->businessName) ?>&first_name=<?= urlencode($filter->firstName) ?>&email=<?= urlencode($filter->email) ?>&status=<?= $filter->status ?? '' ?>" class="rs-btn rs-btn--sm rs-btn--ghost">&laquo; Prev</a>
                        <?php endif; ?>
                        <span class="rs-text-sm rs-text-muted rs-px-2">Page <?= $currentPage ?> of <?= $totalPages ?></span>
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?page=<?= $currentPage + 1 ?>&q=<?= urlencode($filter->search) ?>&business_name=<?= urlencode($filter->businessName) ?>&first_name=<?= urlencode($filter->firstName) ?>&email=<?= urlencode($filter->email) ?>&status=<?= $filter->status ?? '' ?>" class="rs-btn rs-btn--sm rs-btn--ghost">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
