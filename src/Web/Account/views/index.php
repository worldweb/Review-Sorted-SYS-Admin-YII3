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

$url = static fn(string $p): string => \App\Environment::url($p);

$totalPages = max(1, (int) ceil($total / $filter->pageSize));
$currentPage = $filter->page;

// Reusable Page Header Actions
$actions = [];
if ($currentUser->isAdmin()) {
    $actions[] = '<a href="' . $url('/accounts/export') . '" class="rs-btn rs-btn--outline">Export</a>';
    $actions[] = '<a href="' . $url('/accounts/create') . '" class="rs-btn rs-btn--primary">Create Workspace</a>';
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
                            <a href="#available-plans-section" class="rs-btn rs-btn--sm rs-btn--outline">Change / Upgrade</a>
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
            <?php 
                $hasActivePaidSub = !empty($billingData['hasSubscription']) && ($billingData['status'] ?? '') === 'active';
            ?>
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
                            <?php elseif ($hasActivePaidSub): ?>
                                <button type="button" class="rs-btn rs-btn--primary rs-w-100 rs-btn-change-plan-trigger"
                                    data-plan-id="<?= Html::encode((string)$plan->getId()) ?>"
                                    data-plan-name="<?= Html::encode($plan->getPlanName()) ?>"
                                    data-plan-price="<?= Html::encode((string)$plan->getPlanPrice()) ?>"
                                    data-plan-interval="<?= Html::encode($plan->getPlanLimit() ?: 'month') ?>"
                                    data-current-plan-name="<?= Html::encode((string)($billingData['planName'] ?? 'Current Plan')) ?>">
                                    Upgrade to <?= Html::encode($plan->getPlanName()) ?>
                                </button>
                            <?php else: ?>
                                <button type="button" class="rs-btn rs-btn--primary rs-w-100 rs-btn-subscribe-trigger"
                                    data-plan-id="<?= Html::encode((string)$plan->getId()) ?>"
                                    data-plan-name="<?= Html::encode($plan->getPlanName()) ?>"
                                    data-plan-price="<?= Html::encode((string)$plan->getPlanPrice()) ?>"
                                    data-plan-interval="<?= Html::encode($plan->getPlanLimit() ?: 'month') ?>">
                                    Subscribe Now
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
        <form method="get" action="<?= $url('/accounts') ?>">
            <div class="rs-card__header rs-d-flex rs-justify-content-between rs-align-items-center">
                <div class="rs-d-flex rs-gap-2">
                    <div class="rs-input-group" style="width: 300px;">
                        <input type="text" name="q" class="rs-input" value="<?= Html::encode($filter->search) ?>" placeholder="Search workspaces...">
                    </div>
                    <button type="submit" class="rs-btn rs-btn--primary">Search</button>
                    <?php if ($filter->search !== '' || $filter->status !== null || $filter->businessName !== '' || $filter->firstName !== '' || $filter->email !== ''): ?>
                        <a href="<?= $url('/accounts') ?>" class="rs-btn rs-btn--ghost">Reset</a>
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
                            <th>Business Name</th>
                            <th>First Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Subscription</th>
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
                                <a href="<?= $url('/accounts') ?>" class="rs-btn rs-btn--sm rs-btn--ghost">Reset</a>
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
                                        <a href="<?= $url('/accounts/view?id=' . $acc->getId()) ?>" class="rs-font-medium rs-text-primary" style="text-decoration: none;">
                                            <?= Html::encode($acc->getBusinessName() ?? "Workspace #{$acc->getId()}") ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?= Html::encode($acc->getFirstName() ?? '-') ?>
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
                                        <?php if (!empty($acc->getSubscription())): ?>
                                            <span class="rs-badge rs-badge--primary">$<?= Html::encode($acc->getSubscription()) ?></span>
                                        <?php else: ?>
                                            <span class="rs-badge rs-badge--muted">Free</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="rs-text-muted rs-text-sm">
                                        <?= Html::encode($acc->getCreatedAtStr()) ?>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <a href="<?= $url('/accounts/view?id=' . $acc->getId()) ?>" class="rs-btn rs-btn--sm rs-btn--outline">View</a>
                                        <a href="<?= $url('/accounts/update?id=' . $acc->getId()) ?>" class="rs-btn rs-btn--sm rs-btn--secondary">Edit</a>
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

<!-- Change Plan / Upgrade Confirmation Modal matching Yii 2 -->
<div id="rs-change-plan-modal" class="rs-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.75); z-index: 9999; overflow-y: auto; padding: 2rem 1rem; align-items: center; justify-content: center; box-sizing: border-box;">
    <div class="rs-modal-container" style="background: #ffffff; width: 100%; max-width: 520px; border-radius: 12px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; margin: auto; display: flex; flex-direction: column;">
        <div class="rs-modal-header rs-d-flex rs-justify-content-between rs-align-items-center rs-p-4 rs-border-bottom" style="border-bottom: 1px solid #e2e8f0;">
            <h3 class="rs-font-bold rs-text-lg rs-mb-0" id="rs-change-modal-title">Upgrade Subscription Plan</h3>
            <button type="button" class="rs-btn rs-btn--ghost rs-p-2" id="rs-change-modal-close-btn" style="background: none; border: none; font-size: 1.25rem; line-height: 1; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div class="rs-modal-body rs-p-5">
            <div id="rs-change-plan-alert" class="rs-alert rs-alert--danger rs-mb-4" style="display: none; color: #b91c1c; background: #fef2f2; padding: 0.75rem 1rem; border-radius: 6px; border: 1px solid #fecaca; font-size: 0.875rem;"></div>
            
            <div class="rs-mb-4 rs-p-3" style="background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mb-2">
                    <span class="rs-text-xs rs-text-muted rs-text-uppercase rs-font-bold">Target Plan:</span>
                    <span class="rs-font-bold rs-text-md" id="rs-change-target-plan-name">-</span>
                </div>
                <div class="rs-d-flex rs-justify-content-between rs-align-items-center">
                    <span class="rs-text-xs rs-text-muted rs-text-uppercase rs-font-bold">New Billing Rate:</span>
                    <span class="rs-font-bold rs-text-lg rs-text-primary" id="rs-change-target-plan-price">-</span>
                </div>
            </div>

            <p class="rs-text-sm rs-text-muted rs-mb-3">
                You are transitioning from <strong id="rs-change-current-plan-name">Current Plan</strong> to <strong id="rs-change-target-plan-name2">New Plan</strong>. Your new plan benefits will activate immediately upon confirmation.
            </p>

            <form id="rs-change-plan-form" onsubmit="return false;">
                <input type="hidden" id="rs-change-target-plan-id" name="plan_id" value="">
            </form>
        </div>
        <div class="rs-modal-footer rs-d-flex rs-justify-content-end rs-gap-3 rs-p-4 rs-border-top" style="border-top: 1px solid #e2e8f0; background: #f8fafc;">
            <button type="button" class="rs-btn rs-btn--outline" id="rs-change-modal-cancel-btn">Cancel</button>
            <button type="button" class="rs-btn rs-btn--primary" id="rs-change-modal-submit-btn">
                <span id="rs-change-submit-btn-text">Confirm Plan Change</span>
                <span id="rs-change-submit-btn-spinner" style="display: none;">Updating...</span>
            </button>
        </div>
    </div>
</div>

<!-- Restored Modern Stripe Checkout Modal matching Yii 2 -->
<div id="rs-subscribe-modal" class="rs-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.75); z-index: 9999; overflow-y: auto; padding: 2rem 1rem; align-items: center; justify-content: center;">
    <div class="rs-modal-container" style="background: #ffffff; width: 100%; max-width: 640px; border-radius: 12px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; margin: auto;">
        <!-- Modal Header -->
        <div class="rs-modal-header rs-d-flex rs-justify-content-between rs-align-items-center rs-p-5 rs-border-bottom" style="border-bottom: 1px solid #e2e8f0;">
            <div>
                <h3 class="rs-text-lg rs-font-bold rs-mb-0" id="rs-modal-title">Subscribe to Plan</h3>
                <p class="rs-text-xs rs-text-muted rs-mb-0 rs-mt-1">Complete your billing information below.</p>
            </div>
            <button type="button" id="rs-modal-close-btn" class="rs-btn rs-btn--ghost rs-p-2" style="font-size: 1.25rem; line-height: 1; cursor: pointer; border: none; background: none;">&times;</button>
        </div>

        <!-- Modal Body -->
        <div class="rs-modal-body rs-p-5">
            <div id="rs-payment-alert" class="rs-alert rs-alert--danger rs-mb-4" style="display: none; padding: 0.75rem 1rem; background: #fee2e2; border: 1px solid #f87171; border-radius: 6px; color: #b91c1c; font-size: 0.875rem;"></div>

            <form id="rs-subscribe-form" onsubmit="return false;">
                <input type="hidden" id="rs-plan-id" name="plan_id" value="">

                <!-- Selected Plan Summary Banner -->
                <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-p-3 rs-mb-4" style="background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div>
                        <span class="rs-text-xs rs-text-muted rs-text-uppercase rs-font-bold">Selected Plan</span>
                        <div class="rs-font-bold rs-text-md" id="rs-modal-plan-name">Plan Name</div>
                    </div>
                    <div class="rs-text-right">
                        <div class="rs-font-bold rs-text-lg" id="rs-modal-plan-price">$0</div>
                        <span class="rs-text-xs rs-text-muted" id="rs-modal-plan-interval">/ month</span>
                    </div>
                </div>

                <!-- Customer Details -->
                <div class="rs-row rs-d-flex rs-gap-3 rs-mb-3">
                    <div style="flex: 1;">
                        <label class="rs-label rs-text-xs rs-font-bold rs-mb-1" for="rs-cust-fname">First Name <span class="rs-text-danger">*</span></label>
                        <input type="text" id="rs-cust-fname" class="rs-input rs-w-100" placeholder="First Name" required value="<?= Html::encode($currentUser->getName() ?? '') ?>">
                    </div>
                    <div style="flex: 1;">
                        <label class="rs-label rs-text-xs rs-font-bold rs-mb-1" for="rs-cust-lname">Last Name</label>
                        <input type="text" id="rs-cust-lname" class="rs-input rs-w-100" placeholder="Last Name">
                    </div>
                </div>

                <div class="rs-mb-3">
                    <label class="rs-label rs-text-xs rs-font-bold rs-mb-1" for="rs-cust-email">Billing Email <span class="rs-text-danger">*</span></label>
                    <input type="email" id="rs-cust-email" class="rs-input rs-w-100" placeholder="billing@example.com" required value="<?= Html::encode($currentUser->getEmail() ?? '') ?>">
                </div>

                <div class="rs-mb-3">
                    <label class="rs-label rs-text-xs rs-font-bold rs-mb-1" for="rs-cust-country">Country <span class="rs-text-danger">*</span></label>
                    <select id="rs-cust-country" class="rs-input rs-w-100" required>
                        <option value="AU" selected>Australia</option>
                        <option value="US">United States</option>
                        <option value="GB">United Kingdom</option>
                        <option value="CA">Canada</option>
                        <option value="NZ">New Zealand</option>
                        <option value="DE">Germany</option>
                        <option value="FR">France</option>
                        <option value="SG">Singapore</option>
                        <option value="IN">India</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <!-- Terms and Conditions Checkbox -->
                <div class="rs-d-flex rs-align-items-start rs-gap-2 rs-mb-2">
                    <input type="checkbox" id="rs-terms-agree" style="margin-top: 0.25rem;" checked required>
                    <label for="rs-terms-agree" class="rs-text-xs rs-text-muted">
                        I agree to the terms and conditions and authorize the recurring subscription charges.
                    </label>
                </div>
            </form>
        </div>

        <!-- Modal Footer -->
        <div class="rs-modal-footer rs-d-flex rs-justify-content-end rs-gap-3 rs-p-5 rs-border-top" style="border-top: 1px solid #e2e8f0; background: #f8fafc;">
            <button type="button" id="rs-modal-cancel-btn" class="rs-btn rs-btn--outline">Cancel</button>
            <button type="button" id="rs-modal-submit-btn" class="rs-btn rs-btn--primary">
                <span id="rs-submit-btn-text">Confirm & Subscribe</span>
                <span id="rs-submit-btn-spinner" style="display: none;">Processing...</span>
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = <?= json_encode($csrfToken ?? '') ?>;
    const subscribeUrl = <?= json_encode(\App\Environment::url('/accounts/subscribe')) ?>;

    // 1. Upgrade / Change Plan Modal Logic
    const changeModal = document.getElementById('rs-change-plan-modal');
    const changeModalCloseBtn = document.getElementById('rs-change-modal-close-btn');
    const changeModalCancelBtn = document.getElementById('rs-change-modal-cancel-btn');
    const changeModalSubmitBtn = document.getElementById('rs-change-modal-submit-btn');
    const changeAlertBox = document.getElementById('rs-change-plan-alert');

    function showChangeAlert(msg) {
        if (changeAlertBox) {
            changeAlertBox.textContent = msg;
            changeAlertBox.style.display = 'block';
        }
    }

    function hideChangeAlert() {
        if (changeAlertBox) {
            changeAlertBox.textContent = '';
            changeAlertBox.style.display = 'none';
        }
    }

    function closeChangeModal() {
        if (changeModal) {
            changeModal.style.display = 'none';
            hideChangeAlert();
        }
    }

    if (changeModalCloseBtn) changeModalCloseBtn.addEventListener('click', closeChangeModal);
    if (changeModalCancelBtn) changeModalCancelBtn.addEventListener('click', closeChangeModal);

    const changePlanBtns = document.querySelectorAll('.rs-btn-change-plan-trigger');
    changePlanBtns.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const planId = this.getAttribute('data-plan-id');
            const planName = this.getAttribute('data-plan-name');
            const planPrice = this.getAttribute('data-plan-price');
            const planInterval = this.getAttribute('data-plan-interval');
            const currentPlanName = this.getAttribute('data-current-plan-name') || 'Current Plan';

            const targetIdInput = document.getElementById('rs-change-target-plan-id');
            if (targetIdInput) targetIdInput.value = planId;

            const nameEl = document.getElementById('rs-change-target-plan-name');
            if (nameEl) nameEl.textContent = planName;

            const name2El = document.getElementById('rs-change-target-plan-name2');
            if (name2El) name2El.textContent = planName;

            const priceEl = document.getElementById('rs-change-target-plan-price');
            if (priceEl) priceEl.textContent = '$' + planPrice + ' / ' + planInterval;

            const curPlanEl = document.getElementById('rs-change-current-plan-name');
            if (curPlanEl) curPlanEl.textContent = currentPlanName;

            const titleEl = document.getElementById('rs-change-modal-title');
            if (titleEl) titleEl.textContent = 'Upgrade to ' + planName;

            hideChangeAlert();
            if (changeModal) changeModal.style.display = 'flex';
        });
    });

    if (changeModalSubmitBtn) {
        changeModalSubmitBtn.addEventListener('click', async function (e) {
            e.preventDefault();
            hideChangeAlert();

            const planIdInput = document.getElementById('rs-change-target-plan-id');
            const planId = planIdInput ? planIdInput.value : '';
            if (!planId) {
                showChangeAlert('Please select a target plan.');
                return;
            }

            const spinnerText = document.getElementById('rs-change-submit-btn-spinner');
            const btnText = document.getElementById('rs-change-submit-btn-text');
            if (changeModalSubmitBtn) changeModalSubmitBtn.disabled = true;
            if (spinnerText) spinnerText.style.display = 'inline';
            if (btnText) btnText.style.display = 'none';

            try {
                const formData = new FormData();
                if (csrfToken) formData.append('_csrf', csrfToken);
                formData.append('request_type', 'change_paid_plan');
                formData.append('plan_id', planId);

                const response = await fetch(subscribeUrl, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const result = await response.json();
                if (!response.ok || result.status !== true) {
                    showChangeAlert(result.error || 'Failed to update subscription. Please try again.');
                    if (changeModalSubmitBtn) changeModalSubmitBtn.disabled = false;
                    if (spinnerText) spinnerText.style.display = 'none';
                    if (btnText) btnText.style.display = 'inline';
                    return;
                }

                alert(result.success || 'Subscription updated successfully!');
                window.location.reload();
            } catch (err) {
                showChangeAlert('An unexpected network error occurred. Please try again.');
                if (changeModalSubmitBtn) changeModalSubmitBtn.disabled = false;
                if (spinnerText) spinnerText.style.display = 'none';
                if (btnText) btnText.style.display = 'inline';
            }
        });
    }

    // 2. Initial Subscription Modal Logic
    const subModal = document.getElementById('rs-subscribe-modal');
    const subModalCloseBtn = document.getElementById('rs-modal-close-btn');
    const subModalCancelBtn = document.getElementById('rs-modal-cancel-btn');
    const subModalSubmitBtn = document.getElementById('rs-modal-submit-btn');
    const subAlertBox = document.getElementById('rs-payment-alert');

    function showSubAlert(msg) {
        if (subAlertBox) {
            subAlertBox.textContent = msg;
            subAlertBox.style.display = 'block';
        }
    }

    function hideSubAlert() {
        if (subAlertBox) {
            subAlertBox.textContent = '';
            subAlertBox.style.display = 'none';
        }
    }

    function closeSubModal() {
        if (subModal) {
            subModal.style.display = 'none';
            hideSubAlert();
        }
    }

    if (subModalCloseBtn) subModalCloseBtn.addEventListener('click', closeSubModal);
    if (subModalCancelBtn) subModalCancelBtn.addEventListener('click', closeSubModal);

    const subscribeBtns = document.querySelectorAll('.rs-btn-subscribe-trigger');
    subscribeBtns.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const planId = this.getAttribute('data-plan-id');
            const planName = this.getAttribute('data-plan-name');
            const planPrice = this.getAttribute('data-plan-price');
            const planInterval = this.getAttribute('data-plan-interval');

            const planIdInput = document.getElementById('rs-plan-id');
            if (planIdInput) planIdInput.value = planId;

            const nameEl = document.getElementById('rs-modal-plan-name');
            if (nameEl) nameEl.textContent = planName;

            const priceEl = document.getElementById('rs-modal-plan-price');
            if (priceEl) priceEl.textContent = '$' + planPrice;

            const intervalEl = document.getElementById('rs-modal-plan-interval');
            if (intervalEl) intervalEl.textContent = '/ ' + planInterval;

            const titleEl = document.getElementById('rs-modal-title');
            if (titleEl) titleEl.textContent = 'Subscribe to ' + planName;

            hideSubAlert();
            if (subModal) subModal.style.display = 'flex';
        });
    });

    if (subModalSubmitBtn) {
        subModalSubmitBtn.addEventListener('click', async function (e) {
            e.preventDefault();
            hideSubAlert();

            const planIdInput = document.getElementById('rs-plan-id');
            const planId = planIdInput ? planIdInput.value : '';
            if (!planId) {
                showSubAlert('Please select a subscription plan.');
                return;
            }

            const fnameInput = document.getElementById('rs-cust-fname');
            if (!fnameInput || fnameInput.value.trim() === '') {
                showSubAlert('Please provide your first name.');
                return;
            }

            const emailInput = document.getElementById('rs-cust-email');
            if (!emailInput || emailInput.value.trim() === '') {
                showSubAlert('Please provide your billing email.');
                return;
            }

            const terms = document.getElementById('rs-terms-agree');
            if (terms && !terms.checked) {
                showSubAlert('Please agree to the terms and conditions.');
                return;
            }

            const spinnerText = document.getElementById('rs-submit-btn-spinner');
            const btnText = document.getElementById('rs-submit-btn-text');
            if (subModalSubmitBtn) subModalSubmitBtn.disabled = true;
            if (spinnerText) spinnerText.style.display = 'inline';
            if (btnText) btnText.style.display = 'none';

            try {
                const formData = new FormData();
                if (csrfToken) formData.append('_csrf', csrfToken);
                formData.append('request_type', 'create_customer_subscription');
                formData.append('plan_id', planId);
                formData.append('customer_fname', fnameInput.value.trim());
                formData.append('customer_email', emailInput.value.trim());

                const response = await fetch(subscribeUrl, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const result = await response.json();
                if (!response.ok || result.status !== true) {
                    showSubAlert(result.error || 'Failed to complete subscription. Please try again.');
                    if (subModalSubmitBtn) subModalSubmitBtn.disabled = false;
                    if (spinnerText) spinnerText.style.display = 'none';
                    if (btnText) btnText.style.display = 'inline';
                    return;
                }

                alert(result.success || 'Subscribed successfully!');
                window.location.reload();
            } catch (err) {
                showSubAlert('An unexpected network error occurred. Please try again.');
                if (subModalSubmitBtn) subModalSubmitBtn.disabled = false;
                if (spinnerText) spinnerText.style.display = 'none';
                if (btnText) btnText.style.display = 'inline';
            }
        });
    }
});
</script>
