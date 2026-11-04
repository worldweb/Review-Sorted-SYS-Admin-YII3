<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Subscription Plans & Recurring Billing View (Yii 3 Modern Theme)
 *
 * @var array<int, App\Domain\Subscription\Plan> $plans
 * @var array<int, App\Domain\Subscription\Subscription> $subscriptions
 * @var array<int, App\Domain\Subscription\AccountInvoice> $invoices
 * @var App\Domain\User\User $currentUser
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 * @var Yiisoft\View\WebView $this
 */

$this->setTitle('Subscription Plans');

$actions = [];
if ($currentUser->isAdmin()) {
    $actions[] = '<a href="' . \App\Environment::url('/subscription/create') . '" class="rs-btn rs-btn--primary rs-d-flex rs-align-items-center rs-gap-2">'
        . '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg> Create New Plan</a>';
}

$title = 'Subscription Plans';
$description = 'Manage platform subscription plans, limits, active memberships, and recurring billing.';
include __DIR__ . '/../../Shared/Components/_page_header.php';
?>

<div class="rs-page">
    <?php if ($flash->has('success')): ?>
        <div class="rs-alert rs-alert--success rs-mb-4" role="alert">
            <?= Html::encode((string) $flash->get('success')) ?>
        </div>
    <?php endif; ?>
    <?php if ($flash->has('error')): ?>
        <div class="rs-alert rs-alert--danger rs-mb-4" role="alert">
            <?= Html::encode((string) $flash->get('error')) ?>
        </div>
    <?php endif; ?>

    <!-- Subscription Plans Table -->
    <div class="rs-card rs-mb-6">
        <div class="rs-table-wrapper" style="overflow-x: auto;">
            <table class="rs-table rs-table--hover rs-table--modern" style="width: 100%; margin-bottom: 0;">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th style="min-width: 140px; white-space: nowrap;">Plan Name</th>
                        <th style="min-width: 90px; white-space: nowrap;">Price</th>
                        <th style="min-width: 100px; white-space: nowrap;">Limit</th>
                        <th style="max-width: 320px; min-width: 200px;">Features</th>
                        <th style="width: 80px; text-align: center; white-space: nowrap;">Status</th>
                        <th style="width: 120px; white-space: nowrap;">Created At</th>
                        <th style="width: 80px; text-align: right; white-space: nowrap;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($plans)): ?>
                        <tr>
                            <td colspan="8" class="rs-text-center rs-p-6 rs-text-muted" style="padding: 32px 16px;">
                                No subscription plans configured yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($plans as $p): ?>
                            <tr>
                                <td class="rs-text-monospace rs-text-muted">#<?= $p->getId() ?></td>
                                <td style="white-space: nowrap;">
                                    <span class="rs-font-medium rs-text-main"><?= Html::encode($p->getPlanName()) ?></span>
                                </td>
                                <td style="white-space: nowrap;">
                                    <?php $price = (float)$p->getPlanPrice() > 0 ? '$' . $p->getPlanPrice() : 'Free'; ?>
                                    <span class="rs-font-medium"><?= Html::encode($price) ?></span>
                                </td>
                                <td style="white-space: nowrap;">
                                    <span class="rs-text-muted"><?= Html::encode(ucfirst((string)$p->getPlanLimit())) ?></span>
                                </td>
                                <td style="max-width: 320px; white-space: normal;">
                                    <?php
                                    $features = array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string)$p->getPlanFeatures())));
                                    if (count($features) > 0): ?>
                                        <div class="rs-d-flex rs-flex-wrap rs-gap-1" style="max-width: 320px;">
                                            <?php foreach ($features as $feature): ?>
                                                <span class="rs-badge rs-badge--default" style="font-weight: 500; white-space: normal; text-align: left; line-height: 1.35; word-break: break-word; overflow-wrap: anywhere;">
                                                    <?= Html::encode($feature) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="rs-text-muted">No features</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <span class="rs-badge <?= $p->isActive() ? 'rs-badge--success' : 'rs-badge--danger' ?>">
                                        <?= $p->isActive() ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td style="white-space: nowrap;">
                                    <span class="rs-text-muted rs-text-sm"><?= Html::encode($p->getCreatedAt() ? date('d M Y', $p->getCreatedAt()) : '—') ?></span>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <div class="rs-d-flex rs-gap-2 rs-justify-content-end">
                                        <a href="<?= \App\Environment::url('/subscription/update?id=' . $p->getId()) ?>" class="rs-btn-icon rs-btn-icon--sm" title="Edit Plan">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                        </a>
                                        <a href="<?= \App\Environment::url('/subscription/delete?id=' . $p->getId()) ?>" class="rs-btn-icon rs-btn-icon--sm rs-text-danger" title="Delete Plan" onclick="return confirm('Are you sure you want to delete this subscription plan?');">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Active Subscriptions Table -->
    <div class="rs-card rs-mb-6">
        <div class="rs-card__header rs-d-flex rs-justify-content-between rs-align-items-center" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #E2E8F0);">
            <h3 class="rs-card__title" style="margin: 0; font-size: 1rem; font-weight: 700;">Active Account Subscriptions</h3>
            <span class="rs-badge rs-badge--default"><?= count($subscriptions) ?> Memberships</span>
        </div>
        <div class="rs-table-wrapper" style="overflow-x: auto;">
            <table class="rs-table rs-table--hover rs-table--modern" style="width: 100%; margin-bottom: 0;">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Subscriber / Account</th>
                        <th>Plan Tier</th>
                        <th>Billing Cycle Ends</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subscriptions)): ?>
                        <tr>
                            <td colspan="5" class="rs-text-center rs-p-6 rs-text-muted" style="padding: 32px 16px;">
                                No active customer subscriptions found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($subscriptions as $sub): ?>
                            <tr>
                                <td class="rs-text-monospace rs-text-muted">#<?= $sub->getId() ?></td>
                                <td class="rs-font-semibold rs-text-main">User #<?= $sub->getUserId() ?></td>
                                <td>Plan #<?= $sub->getPlanId() ?></td>
                                <td class="rs-text-muted"><?= Html::encode($sub->getPlanPeriodEnd() ?? '—') ?></td>
                                <td>
                                    <?php $statusStr = strtolower((string) $sub->getStatus()); ?>
                                    <span class="rs-badge <?= in_array($statusStr, ['active', 'succeeded', '1'], true) ? 'rs-badge--success' : 'rs-badge--default' ?>">
                                        <?= Html::encode(ucfirst($statusStr)) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Billing Invoices Table -->
    <div class="rs-card">
        <div class="rs-card__header rs-d-flex rs-justify-content-between rs-align-items-center" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #E2E8F0);">
            <h3 class="rs-card__title" style="margin: 0; font-size: 1rem; font-weight: 700;">Recent Invoices</h3>
            <span class="rs-badge rs-badge--default"><?= count($invoices) ?> Invoices</span>
        </div>
        <div class="rs-table-wrapper" style="overflow-x: auto;">
            <table class="rs-table rs-table--hover rs-table--modern" style="width: 100%; margin-bottom: 0;">
                <thead>
                    <tr>
                        <th style="width: 120px;">Invoice #</th>
                        <th>Workspace / Account</th>
                        <th>Total Amount</th>
                        <th>Invoice Date</th>
                        <th>Payment Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($invoices)): ?>
                        <tr>
                            <td colspan="5" class="rs-text-center rs-p-6 rs-text-muted" style="padding: 32px 16px;">
                                No invoice history available.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($invoices as $inv): ?>
                            <tr>
                                <td class="rs-text-monospace rs-font-semibold"><?= Html::encode($inv->getInvoiceNumber() ?? '#' . $inv->getId()) ?></td>
                                <td>Account #<?= $inv->getAccountId() ?></td>
                                <td class="rs-font-semibold">$<?= number_format(((float) $inv->getAmount()) / 100, 2) ?></td>
                                <td class="rs-text-muted"><?= Html::encode($inv->getInvoiceDate() ?? '—') ?></td>
                                <td>
                                    <span class="rs-badge <?= $inv->getStatus() === 'paid' ? 'rs-badge--success' : 'rs-badge--warning' ?>">
                                        <?= Html::encode(ucfirst((string) $inv->getStatus())) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
