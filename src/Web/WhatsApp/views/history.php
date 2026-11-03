<?php

declare(strict_types=1);

use App\Domain\WhatsApp\WhatsAppQueue;
use Yiisoft\Html\Html;

/**
 * @var array<int, WhatsAppQueue> $historyItems
 * @var int $totalItems
 * @var int $page
 * @var int $pageSize
 * @var int $sentCount
 * @var int $queuedCount
 * @var int $failedCount
 * @var int|null $accountId
 * @var \App\Domain\User\User $currentUser
 * @var \App\Security\TenantContextInterface $context
 * @var \Yiisoft\Session\Flash\Flash $flash
 */

$this->setTitle('WhatsApp Message History');
$totalPages = max(1, (int) ceil($totalItems / $pageSize));
?>

<div class="rs-page">

    <!-- Flash notifications -->
    <?php foreach ($flash->getAll() as $type => $messages): ?>
        <?php foreach ($messages as $message): ?>
            <div class="rs-alert rs-alert--<?= $type === 'error' ? 'danger' : Html::encode($type) ?> rs-mb-4" role="alert">
                <?= Html::encode($message) ?>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>

    <div class="rs-page-header rs-mb-6">
        <h1 class="rs-page-title rs-m-0">WhatsApp Message History</h1>
        <p class="rs-text-muted rs-m-0">Monitor outbound WhatsApp message delivery states, logs, and provider statuses.</p>
    </div>

    <!-- KPI Cards Section -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--rs-space-5, 20px); margin-bottom: var(--rs-space-6, 24px);">
        <div class="rs-card" style="margin-bottom:0;">
            <div class="rs-card__body rs-p-4">
                <div style="font-size:0.75rem; font-weight:700; text-transform:uppercase; color:var(--rs-text-muted); letter-spacing:0.05em; margin-bottom:6px;">Messages Sent</div>
                <div style="font-size:1.75rem; font-weight:700; color:#16a34a;"><?= $sentCount ?></div>
            </div>
        </div>
        <div class="rs-card" style="margin-bottom:0;">
            <div class="rs-card__body rs-p-4">
                <div style="font-size:0.75rem; font-weight:700; text-transform:uppercase; color:var(--rs-text-muted); letter-spacing:0.05em; margin-bottom:6px;">Queued / Processing</div>
                <div style="font-size:1.75rem; font-weight:700; color:#ca8a04;"><?= $queuedCount ?></div>
            </div>
        </div>
        <div class="rs-card" style="margin-bottom:0;">
            <div class="rs-card__body rs-p-4">
                <div style="font-size:0.75rem; font-weight:700; text-transform:uppercase; color:var(--rs-text-muted); letter-spacing:0.05em; margin-bottom:6px;">Delivery Failed</div>
                <div style="font-size:1.75rem; font-weight:700; color:#dc2626;"><?= $failedCount ?></div>
            </div>
        </div>
    </div>

    <!-- History Table Card -->
    <div class="rs-card">
        <div class="rs-card__header rs-d-flex rs-align-items-center rs-justify-content-between">
            <h3 class="rs-m-0" style="font-size: 16px;">Message History (Total: <?= $totalItems ?>)</h3>
            <span class="rs-text-muted" style="font-size: 12px;">Page <?= $page ?> of <?= $totalPages ?></span>
        </div>
        <div class="rs-table-wrapper rs-p-0">
            <table class="rs-table rs-table--hover">
                <thead>
                    <tr>
                        <th style="min-width: 180px;">Recipient Phone</th>
                        <th style="min-width: 280px;">Message Content</th>
                        <th style="width: 130px;">Status</th>
                        <th style="width: 170px;">Provider ID</th>
                        <th style="width: 150px; text-align: right;">Queued At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historyItems)): ?>
                        <tr>
                            <td colspan="5" class="rs-text-center rs-p-8 rs-text-muted">
                                <i class="fa-regular fa-clock rs-mb-2" style="font-size: 2rem; display: block; opacity: 0.4;"></i>
                                No message history logged. Outbound WhatsApp message queue is empty.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($historyItems as $item): ?>
                            <?php
                            $badgeClass = match ($item->getStatus()) {
                                WhatsAppQueue::STATUS_SENT => 'rs-badge--success',
                                WhatsAppQueue::STATUS_QUEUED => 'rs-badge--warning',
                                WhatsAppQueue::STATUS_PROCESSING => 'rs-badge--info',
                                default => 'rs-badge--danger',
                            };
                            $statusLabel = WhatsAppQueue::STATUS_LABELS[$item->getStatus()] ?? 'Unknown';
                            ?>
                            <tr>
                                <td>
                                    <code class="rs-font-semibold" style="color: #059669; font-size: 13px;">
                                        <?= Html::encode($item->getPhone()) ?>
                                    </code>
                                </td>
                                <td>
                                    <div class="rs-font-medium" style="word-break: break-word; max-width: 400px; font-size: 13px;">
                                        <?= Html::encode(mb_strimwidth($item->getMessage(), 0, 120, '...')) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="rs-badge <?= $badgeClass ?>">
                                        <?= Html::encode($statusLabel) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($item->getProviderMessageId() !== null): ?>
                                        <code class="rs-text-muted" style="font-size: 11px; word-break: break-all;">
                                            <?= Html::encode(mb_strimwidth($item->getProviderMessageId(), 0, 24, '...')) ?>
                                        </code>
                                    <?php else: ?>
                                        <span class="rs-text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;" class="rs-text-muted">
                                    <?= $item->getCreatedAt() ? date('Y-m-d H:i', $item->getCreatedAt()) : '—' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="rs-card__footer rs-d-flex rs-align-items-center rs-justify-content-between rs-p-4" style="border-top: 1px solid var(--rs-border, #E2E8F0);">
                <div class="rs-text-muted" style="font-size: 13px;">
                    Showing page <?= $page ?> of <?= $totalPages ?> (<?= $totalItems ?> items)
                </div>
                <div class="rs-d-flex rs-gap-2">
                    <?php if ($page > 1): ?>
                        <a href="/sys-yii3/whatsapp/history?page=<?= $page - 1 ?>" class="rs-btn rs-btn--sm rs-btn--outline">Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="/sys-yii3/whatsapp/history?page=<?= $page + 1 ?>" class="rs-btn rs-btn--sm rs-btn--outline">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
