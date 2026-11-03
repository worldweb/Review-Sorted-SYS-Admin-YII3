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
    $actions[] = '<a href="/sys-yii3/subscription/create" class="rs-btn rs-btn--primary rs-d-flex rs-align-items-center rs-gap-2">'
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

    <!-- Pricing Comparison Cards Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 28px;">
        <?php foreach ($plans as $p): ?>
            <div class="rs-card" style="margin-bottom: 0; display: flex; flex-direction: column; <?= $p->isActive() ? 'border-top: 3px solid var(--rs-primary, #059669);' : 'opacity: 0.8;' ?>">
                <div class="rs-card__header rs-d-flex rs-justify-content-between rs-align-items-center" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                    <h3 class="rs-card__title" style="margin: 0; font-size: 1.05rem; font-weight: 700; color: var(--rs-text-main, #0F172A);">
                        <?= Html::encode($p->getPlanName()) ?>
                    </h3>
                    <span class="rs-badge <?= $p->isActive() ? 'rs-badge--success' : 'rs-badge--default' ?>">
                        <?= $p->isActive() ? 'Active' : 'Inactive' ?>
                    </span>
                </div>
                <div class="rs-card__body rs-p-5" style="padding: 24px; flex-grow: 1;">
                    <div style="font-size: 2rem; font-weight: 700; color: var(--rs-primary, #059669); line-height: 1; margin-bottom: 8px;">
                        <?= Html::encode($p->getFormattedPrice()) ?><span style="font-size: 0.875rem; font-weight: 500; color: var(--rs-text-muted, #64748B);">/mo</span>
                    </div>
                    <div style="font-size: 0.85rem; color: var(--rs-text-muted, #64748B); margin-bottom: 16px;">
                        Up to <strong><?= Html::encode($p->getPlanLimit()) ?></strong> location slots
                    </div>
                    <ul style="list-style: none; padding: 0; margin: 0 0 20px 0; font-size: 0.875rem;">
                        <?php foreach ($p->getFeaturesList() as $feat): ?>
                            <li style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; color: var(--rs-text-main, #0F172A);">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="rs-text-success"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                <span><?= Html::encode($feat) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php if ($currentUser->isAdmin()): ?>
                    <div class="rs-card__footer rs-d-flex rs-justify-content-end rs-gap-2" style="padding: 12px 20px; border-top: 1px solid var(--rs-border, #E2E8F0); background: #f8fafc;">
                        <a href="/sys-yii3/subscription/update?id=<?= $p->getId() ?>" class="rs-btn rs-btn--sm rs-btn--outline">Edit</a>
                        <a href="/sys-yii3/subscription/delete?id=<?= $p->getId() ?>" class="rs-btn rs-btn--sm rs-btn--ghost rs-text-danger" onclick="return confirm('Delete this subscription plan?');">Delete</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
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
