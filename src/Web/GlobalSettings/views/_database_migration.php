<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Database Migration & Schema Health Tab (Yii 3 Parity View)
 *
 * @var array $dbHealth
 * @var array $migrationStatus
 */

$dbHealth = $dbHealth ?? [
    'status' => 'connected',
    'database' => 'reviews_sorted',
    'driver' => 'MYSQL',
    'host' => '127.0.0.1',
    'serverVersion' => '10.4.32-MariaDB',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];

$migrationStatus = $migrationStatus ?? [
    'status' => 'up_to_date',
    'statusMessage' => 'All Yii migrations are reconciled and up to date with the database schema.',
    'appliedCount' => 38,
    'pendingCount' => 0,
    'reconcilableCount' => 0,
    'conflictCount' => 0,
    'pendingMigrations' => [],
    'reconcilableMigrations' => [],
    'conflictMigrations' => [],
];

$statusBadgeClass = 'rs-badge--success';
$statusText = 'Up to Date';

if (($migrationStatus['conflictCount'] ?? 0) > 0) {
    $statusBadgeClass = 'rs-badge--danger';
    $statusText = 'Conflict Detected';
} elseif (($migrationStatus['reconcilableCount'] ?? 0) > 0) {
    $statusBadgeClass = 'rs-badge--warning';
    $statusText = 'Reconciliation Needed';
} elseif (($migrationStatus['pendingCount'] ?? 0) > 0) {
    $statusBadgeClass = 'rs-badge--warning';
    $statusText = 'Pending Migrations';
}
?>

<div class="rs-database-migration-settings">
    <!-- CARD 1: DATABASE CONNECTION HEALTH -->
    <div class="rs-card rs-mb-5" style="border: 1px solid var(--rs-border, #e2e8f0); margin-bottom: 1.5rem; border-radius: 8px;">
        <div class="rs-card__header rs-d-flex rs-align-items-center rs-justify-content-between" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #e2e8f0);">
            <h4 class="rs-card__title rs-m-0" style="font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0;">Database Connection</h4>
            <?php if (($dbHealth['status'] ?? '') === 'connected'): ?>
                <span class="rs-badge rs-badge--success">● Connected</span>
            <?php else: ?>
                <span class="rs-badge rs-badge--danger">✖ Connection Failed</span>
            <?php endif; ?>
        </div>
        <div class="rs-card__body rs-p-5" style="padding: 20px;">
            <div class="rs-grid rs-grid--cols-1 rs-grid--cols-md-3 rs-gap-4">
                <div>
                    <label class="rs-text-muted rs-font-size-xs rs-text-uppercase rs-d-block" style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Database Name</label>
                    <strong class="rs-font-size-md" style="font-size: 0.95rem; color: #0f172a;"><?= Html::encode($dbHealth['database'] ?? 'reviews_sorted') ?></strong>
                </div>
                <div>
                    <label class="rs-text-muted rs-font-size-xs rs-text-uppercase rs-d-block" style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Driver / Engine</label>
                    <strong class="rs-font-size-md" style="font-size: 0.95rem; color: #0f172a;"><?= Html::encode(strtoupper($dbHealth['driver'] ?? 'MYSQL')) ?></strong>
                </div>
                <div>
                    <label class="rs-text-muted rs-font-size-xs rs-text-uppercase rs-d-block" style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Database Host</label>
                    <strong class="rs-font-size-md" style="font-size: 0.95rem; color: #0f172a;"><?= Html::encode($dbHealth['host'] ?? '127.0.0.1') ?></strong>
                </div>
                <div>
                    <label class="rs-text-muted rs-font-size-xs rs-text-uppercase rs-d-block" style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Server Version</label>
                    <span class="rs-font-size-md" style="font-size: 0.95rem; color: #0f172a;"><?= Html::encode($dbHealth['serverVersion'] ?? '10.4.32-MariaDB') ?></span>
                </div>
                <div>
                    <label class="rs-text-muted rs-font-size-xs rs-text-uppercase rs-d-block" style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Character Set</label>
                    <span class="rs-font-size-md" style="font-size: 0.95rem; color: #0f172a;"><?= Html::encode($dbHealth['charset'] ?? 'utf8mb4') ?></span>
                </div>
                <div>
                    <label class="rs-text-muted rs-font-size-xs rs-text-uppercase rs-d-block" style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Collation</label>
                    <span class="rs-font-size-md" style="font-size: 0.95rem; color: #0f172a;"><?= Html::encode($dbHealth['collation'] ?? 'utf8mb4_unicode_ci') ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- CARD 2: MIGRATION STATUS & HEALTH -->
    <div class="rs-card rs-mb-5" style="border: 1px solid var(--rs-border, #e2e8f0); border-radius: 8px;">
        <div class="rs-card__header rs-d-flex rs-align-items-center rs-justify-content-between" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #e2e8f0);">
            <h4 class="rs-card__title rs-m-0" style="font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0;">Migration Preflight &amp; Status</h4>
            <span class="rs-badge <?= $statusBadgeClass ?>"><?= Html::encode($statusText) ?></span>
        </div>
        <div class="rs-card__body rs-p-5" style="padding: 20px;">
            <p class="rs-text-muted rs-mb-4" style="color: #64748b; margin-bottom: 1rem;"><?= Html::encode($migrationStatus['statusMessage'] ?? '') ?></p>

            <div class="rs-grid rs-grid--cols-2 rs-grid--cols-md-4 rs-gap-4 rs-mb-4" style="background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 1rem;">
                <div>
                    <label class="rs-text-muted rs-font-size-xs rs-text-uppercase rs-d-block" style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Applied Migrations</label>
                    <span class="rs-font-size-xl" style="font-size: 1.5rem; font-weight: 700; color: #0f172a;"><?= (int)($migrationStatus['appliedCount'] ?? 0) ?></span>
                </div>
                <div>
                    <label class="rs-text-muted rs-font-size-xs rs-text-uppercase rs-d-block" style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Pending Migrations</label>
                    <span class="rs-font-size-xl" style="font-size: 1.5rem; font-weight: 700; color: <?= ($migrationStatus['pendingCount'] ?? 0) > 0 ? '#d97706' : '#16a34a' ?>;">
                        <?= (int)($migrationStatus['pendingCount'] ?? 0) ?>
                    </span>
                </div>
                <div>
                    <label class="rs-text-muted rs-font-size-xs rs-text-uppercase rs-d-block" style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase;">History Missing</label>
                    <span class="rs-font-size-xl" style="font-size: 1.5rem; font-weight: 700; color: <?= ($migrationStatus['reconcilableCount'] ?? 0) > 0 ? '#0284c7' : '#64748b' ?>;">
                        <?= (int)($migrationStatus['reconcilableCount'] ?? 0) ?>
                    </span>
                </div>
                <div>
                    <label class="rs-text-muted rs-font-size-xs rs-text-uppercase rs-d-block" style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Conflicts / Partial</label>
                    <span class="rs-font-size-xl" style="font-size: 1.5rem; font-weight: 700; color: <?= ($migrationStatus['conflictCount'] ?? 0) > 0 ? '#dc2626' : '#16a34a' ?>;">
                        <?= (int)($migrationStatus['conflictCount'] ?? 0) ?>
                    </span>
                </div>
            </div>

            <div class="rs-d-flex rs-justify-content-end">
                <a href="/sys-yii3/global-settings?section=database_migration" class="rs-btn rs-btn--ghost" style="border: 1px solid #cbd5e1;">
                    🔄 Check Status Again
                </a>
            </div>
        </div>
    </div>
</div>
