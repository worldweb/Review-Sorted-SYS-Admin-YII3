<?php

declare(strict_types=1);

/**
 * @var array<int, App\Domain\Subscription\Plan> $plans
 * @var array<int, App\Domain\Subscription\Subscription> $subscriptions
 * @var array<int, App\Domain\Subscription\AccountInvoice> $invoices
 * @var App\Domain\User\User $currentUser
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 */

$this->setTitle('Subscriptions & Billing');
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h3 font-weight-bold text-gray-800 mb-1">
                        <i class="bi bi-credit-card text-primary me-2"></i>Subscriptions &amp; Billing
                    </h2>
                    <p class="text-muted mb-0">Manage platform subscription plans, active recurring memberships, and billing invoices.</p>
                </div>
                <?php if ($currentUser->isAdmin()): ?>
                    <div>
                        <a href="/sys-yii3/subscription/create" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-1"></i> Add New Plan
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($flash->has('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars((string) $flash->get('success')) ?>
                </div>
            <?php endif; ?>
            <?php if ($flash->has('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars((string) $flash->get('error')) ?>
                </div>
            <?php endif; ?>

            <!-- Available Plans Pricing Grid -->
            <div class="row row-cols-1 row-cols-md-3 mb-4 text-center">
                <?php foreach ($plans as $p): ?>
                    <div class="col mb-4">
                        <div class="card shadow-sm border-0 h-100 <?= $p->isActive() ? 'border-primary' : 'opacity-75' ?>">
                            <div class="card-header py-3 bg-white border-bottom">
                                <h4 class="my-0 fw-normal text-primary"><?= htmlspecialchars($p->getPlanName()) ?></h4>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h1 class="card-title pricing-card-title"><?= $p->getFormattedPrice() ?><small class="text-muted fw-light">/mo</small></h1>
                                <p class="text-muted small">Up to <strong><?= htmlspecialchars($p->getPlanLimit()) ?></strong> locations</p>
                                <ul class="list-unstyled mt-3 mb-4 text-start small">
                                    <?php foreach ($p->getFeaturesList() as $feat): ?>
                                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><?= htmlspecialchars($feat) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php if ($currentUser->isAdmin()): ?>
                                    <div class="mt-auto d-flex justify-content-center gap-2">
                                        <a href="/sys-yii3/subscription/update?id=<?= $p->getId() ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-pencil me-1"></i> Edit Plan
                                        </a>
                                        <a href="/sys-yii3/subscription/delete?id=<?= $p->getId() ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this plan?');">
                                            <i class="bi bi-trash me-1"></i> Delete
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Active Subscriptions Table -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Active Subscriptions</h6>
                    <span class="badge bg-secondary"><?= count($subscriptions) ?> Memberships</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($subscriptions)): ?>
                        <div class="p-4 text-center text-muted">
                            <p class="mb-0">No active customer subscriptions on record.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Customer</th>
                                        <th>Email</th>
                                        <th>Stripe Subscription ID</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Renews / Expires</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subscriptions as $sub): ?>
                                        <tr>
                                            <td class="text-muted"><?= $sub->getId() ?></td>
                                            <td class="fw-bold"><?= htmlspecialchars($sub->getCustomerName() ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($sub->getCustomerEmail() ?? 'N/A') ?></td>
                                            <td><code><?= htmlspecialchars($sub->getStripeSubscriptionId() ?? 'N/A') ?></code></td>
                                            <td><?= !empty($sub->getPaidAmount()) ? '$' . number_format((float) $sub->getPaidAmount(), 2) : 'Free' ?></td>
                                            <td>
                                                <?php if ($sub->isActive()): ?>
                                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($sub->getStatus() ?? 'Inactive') ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small text-muted"><?= htmlspecialchars($sub->getPlanPeriodEnd() ?? 'N/A') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Invoices Table -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="m-0 font-weight-bold text-primary">Invoices &amp; Receipts</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($invoices)): ?>
                        <div class="p-4 text-center text-muted">
                            <p class="mb-0">No invoices generated yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Amount</th>
                                        <th>Currency</th>
                                        <th>Status</th>
                                        <th>Paid Date</th>
                                        <th class="text-end">Receipt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($invoices as $inv): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($inv->getInvoiceNumber() ?? $inv->getStripeInvoiceId()) ?></code></td>
                                            <td class="fw-bold"><?= $inv->getFormattedAmount() ?></td>
                                            <td><?= $inv->getCurrency() ?></td>
                                            <td><span class="badge bg-success"><?= htmlspecialchars(strtoupper($inv->getStatus())) ?></span></td>
                                            <td class="small text-muted"><?= $inv->getPaidAt() ? date('M d, Y', $inv->getPaidAt()) : 'N/A' ?></td>
                                            <td class="text-end">
                                                <?php if (!empty($inv->getHostedInvoiceUrl())): ?>
                                                    <a href="<?= htmlspecialchars($inv->getHostedInvoiceUrl()) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-box-arrow-up-right me-1"></i> View Receipt
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
