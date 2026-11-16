<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Database Migration & Schema Health Tab (Yii 3 Production System)
 *
 * @var array $dbHealth
 * @var array $schemaReport
 * @var array $migrationStatus
 * @var array $syncPlan
 * @var ?array $lastExecutionReport
 * @var string $csrfToken
 */

$dbHealth = $dbHealth ?? [];
$schemaReport = $schemaReport ?? [];
$migrationStatus = $migrationStatus ?? [];
$syncPlan = $syncPlan ?? [];
$lastExecutionReport = $lastExecutionReport ?? null;
$csrfToken = $csrfToken ?? '';

$isConnected = ($dbHealth['connected'] ?? false) || (($dbHealth['status'] ?? '') === 'connected');

// Status Badge for Migrations
$migStatusBadgeClass = 'rs-badge--success';
$migStatusText = 'Up to Date';

if (($migrationStatus['conflictCount'] ?? 0) > 0) {
    $migStatusBadgeClass = 'rs-badge--danger';
    $migStatusText = 'Conflict Detected';
} elseif (($migrationStatus['pendingCount'] ?? 0) > 0) {
    $migStatusBadgeClass = 'rs-badge--warning';
    $migStatusText = 'Pending Migrations (' . (int)$migrationStatus['pendingCount'] . ')';
} elseif (!$isConnected) {
    $migStatusBadgeClass = 'rs-badge--danger';
    $migStatusText = 'Connection Error';
}

// Status Badge for Schema
$schemaStatus = $schemaReport['status'] ?? 'UNKNOWN';
$schemaBadgeClass = 'rs-badge--success';
$schemaText = 'Schema Synchronized';

if ($schemaStatus === 'CRITICAL' || ($schemaReport['missingTableCount'] ?? 0) > 0) {
    $schemaBadgeClass = 'rs-badge--danger';
    $schemaText = 'Critical Gaps Detected';
} elseif ($schemaStatus === 'WARNING' || ($schemaReport['columnMismatchCount'] ?? 0) > 0 || ($schemaReport['missingColumnCount'] ?? 0) > 0) {
    $schemaBadgeClass = 'rs-badge--warning';
    $schemaText = 'Discrepancies Detected';
} elseif (!$isConnected) {
    $schemaBadgeClass = 'rs-badge--danger';
    $schemaText = 'Unable to Validate';
}

$canSync = !empty($syncPlan['canAutoSynchronize']);
$isSynced = !empty($syncPlan['isSynchronized']) || (($syncPlan['counts']['total'] ?? 0) === 0);
?>

<div class="rs-database-migration-settings">

    <!-- TOP CONTROLS BAR -->
    <div class="rs-d-flex rs-align-items-center rs-justify-content-between rs-mb-4" style="margin-bottom: 1.25rem; flex-wrap: wrap; gap: 12px;">
        <div>
            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #0f172a;">Database Health &amp; Schema Synchronization</h3>
            <p style="margin: 4px 0 0; font-size: 0.875rem; color: #64748b;">
                Real-time inspection of database connectivity, schema integrity, and migration version synchronization.
            </p>
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <a href="<?= \App\Environment::url('/global-settings?section=database_migration') ?>" class="rs-btn rs-btn--ghost" style="border: 1px solid #cbd5e1; font-weight: 600; padding: 8px 16px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; background: #fff;">
                🔄 Check Status Again
            </a>

            <?php if ($canSync): ?>
                <button
                    type="button"
                    class="rs-btn rs-btn--primary"
                    style="background: #2563eb; color: #fff; font-weight: 600; padding: 8px 18px; border-radius: 6px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 1px 3px rgba(37,99,235,0.2);"
                    onclick="document.getElementById('syncConfirmModal').style.display='flex'"
                >
                    ⚡ Synchronize Database
                </button>
            <?php elseif ($isConnected && $isSynced): ?>
                <span style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 8px 14px; border-radius: 6px; font-weight: 600; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px;">
                    ✓ Schema Synchronized
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- CARD 1: DATABASE CONNECTION HEALTH -->
    <div class="rs-card rs-mb-5" style="border: 1px solid var(--rs-border, #e2e8f0); margin-bottom: 1.5rem; border-radius: 8px; background: #fff;">
        <div class="rs-card__header rs-d-flex rs-align-items-center rs-justify-content-between" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #e2e8f0);">
            <h4 class="rs-card__title rs-m-0" style="font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0;">1. Database Connection Health</h4>
            <?php if ($isConnected): ?>
                <span class="rs-badge rs-badge--success" style="background: #ecfdf5; color: #065f46; font-weight: 600; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem;">● Connected</span>
            <?php else: ?>
                <span class="rs-badge rs-badge--danger" style="background: #fef2f2; color: #991b1b; font-weight: 600; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem;">✖ Connection Failed</span>
            <?php endif; ?>
        </div>
        <div class="rs-card__body rs-p-5" style="padding: 20px;">
            <?php if (!empty($dbHealth['error'])): ?>
                <div class="rs-alert rs-alert--danger rs-mb-4" style="background: #fef2f2; border: 1px solid #f87171; color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 1rem;">
                    <strong>Connection Error:</strong> <?= Html::encode((string) $dbHealth['error']) ?>
                </div>
            <?php endif; ?>

            <div class="rs-grid rs-grid--cols-2 rs-grid--cols-md-4 rs-gap-4">
                <div>
                    <label class="rs-text-muted rs-font-size-xs rs-text-uppercase rs-d-block" style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Database Name</label>
                    <strong class="rs-font-size-md" style="font-size: 0.95rem; color: #0f172a;"><?= Html::encode($dbHealth['database'] ?? 'Unknown') ?></strong>
                </div>
                <div>
                    <label class="rs-text-muted rs-font-size-xs rs-text-uppercase rs-d-block" style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Driver / Engine</label>
                    <strong class="rs-font-size-md" style="font-size: 0.95rem; color: #0f172a;"><?= Html::encode(strtoupper($dbHealth['driver'] ?? 'MYSQL')) ?></strong>
                </div>
                <div>
                    <label class="rs-text-muted rs-font-size-xs rs-text-uppercase rs-d-block" style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Host &amp; Port</label>
                    <strong class="rs-font-size-md" style="font-size: 0.95rem; color: #0f172a;"><?= Html::encode(($dbHealth['host'] ?? '127.0.0.1') . ':' . ($dbHealth['port'] ?? '3306')) ?></strong>
                </div>
                <div>
                    <label class="rs-text-muted rs-font-size-xs rs-text-uppercase rs-d-block" style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Server Version</label>
                    <span class="rs-font-size-md" style="font-size: 0.95rem; color: #0f172a;"><?= Html::encode($dbHealth['serverVersion'] ?? 'Unknown') ?></span>
                </div>
                <div>
                    <label class="rs-text-muted rs-font-size-xs rs-text-uppercase rs-d-block" style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Character Set</label>
                    <span class="rs-font-size-md" style="font-size: 0.95rem; color: #0f172a;"><?= Html::encode($dbHealth['charset'] ?? 'utf8mb4') ?></span>
                </div>
                <div>
                    <label class="rs-text-muted rs-font-size-xs rs-text-uppercase rs-d-block" style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Collation</label>
                    <span class="rs-font-size-md" style="font-size: 0.95rem; color: #0f172a;"><?= Html::encode($dbHealth['collation'] ?? 'utf8mb4_general_ci') ?></span>
                </div>
                <div>
                    <label class="rs-text-muted rs-font-size-xs rs-text-uppercase rs-d-block" style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Active Tables in DB</label>
                    <span class="rs-font-size-md" style="font-size: 0.95rem; font-weight: 700; color: #0f172a;"><?= (int)($dbHealth['tableCount'] ?? 0) ?></span>
                </div>
                <div>
                    <label class="rs-text-muted rs-font-size-xs rs-text-uppercase rs-d-block" style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Connection Mode</label>
                    <span class="rs-font-size-md" style="font-size: 0.95rem; color: #059669; font-weight: 600;">Active / Verified</span>
                </div>
            </div>
        </div>
    </div>

    <!-- CARD 2: MIGRATION PREFLIGHT & SYNCHRONIZATION -->
    <div class="rs-card rs-mb-5" style="border: 1px solid var(--rs-border, #e2e8f0); margin-bottom: 1.5rem; border-radius: 8px; background: #fff;">
        <div class="rs-card__header rs-d-flex rs-align-items-center rs-justify-content-between" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #e2e8f0);">
            <h4 class="rs-card__title rs-m-0" style="font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0;">2. Migration History &amp; Synchronization</h4>
            <span class="rs-badge <?= $migStatusBadgeClass ?>" style="padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">
                <?= Html::encode($migStatusText) ?>
            </span>
        </div>
        <div class="rs-card__body rs-p-5" style="padding: 20px;">
            <p class="rs-text-muted rs-mb-4" style="color: #64748b; margin-bottom: 1rem;">
                <?= Html::encode($migrationStatus['statusMessage'] ?? 'Live migration status retrieved from database.') ?>
            </p>

            <div class="rs-grid rs-grid--cols-2 rs-grid--cols-md-4 rs-gap-4 rs-mb-4" style="background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 1.25rem;">
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
                    <label class="rs-text-muted rs-font-size-xs rs-text-uppercase rs-d-block" style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Historical Records</label>
                    <span class="rs-font-size-xl" style="font-size: 1.5rem; font-weight: 700; color: #0284c7;">
                        <?= (int)($migrationStatus['missingFileCount'] ?? 0) ?>
                    </span>
                </div>
                <div>
                    <label class="rs-text-muted rs-font-size-xs rs-text-uppercase rs-d-block" style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Conflicts / Duplicate</label>
                    <span class="rs-font-size-xl" style="font-size: 1.5rem; font-weight: 700; color: <?= ($migrationStatus['conflictCount'] ?? 0) > 0 ? '#dc2626' : '#16a34a' ?>;">
                        <?= (int)($migrationStatus['conflictCount'] ?? 0) ?>
                    </span>
                </div>
            </div>

            <!-- PENDING MIGRATIONS TABLE -->
            <?php if (!empty($migrationStatus['pendingMigrations'])): ?>
                <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 16px; margin-bottom: 1.25rem;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                        <span style="font-size: 1.2rem;">⚠️</span>
                        <strong style="color: #92400e; font-size: 0.95rem;">Pending Database Migration Detected</strong>
                    </div>
                    <p style="color: #78350f; font-size: 0.875rem; margin-bottom: 12px;">
                        The current codebase contains migration files that have not yet been executed on the connected database.
                    </p>

                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; background: #fff; border-radius: 6px; overflow: hidden; border: 1px solid #fef3c7;">
                            <thead>
                                <tr style="background: #fef3c7; color: #92400e; text-align: left;">
                                    <th style="padding: 10px 12px;">Migration Name / Class</th>
                                    <th style="padding: 10px 12px;">Detected Date</th>
                                    <th style="padding: 10px 12px;">Description</th>
                                    <th style="padding: 10px 12px;">Risk Level</th>
                                    <th style="padding: 10px 12px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($migrationStatus['pendingMigrations'] as $pMig): ?>
                                    <tr style="border-bottom: 1px solid #fef3c7;">
                                        <td style="padding: 10px 12px; font-weight: 600; color: #0f172a;">
                                            <code><?= Html::encode($pMig['version']) ?></code>
                                            <div style="font-size: 0.75rem; color: #64748b; font-weight: normal; margin-top: 2px;">
                                                <?= Html::encode($pMig['filePath']) ?>
                                            </div>
                                        </td>
                                        <td style="padding: 10px 12px; color: #475569;"><?= Html::encode($pMig['formattedDate']) ?></td>
                                        <td style="padding: 10px 12px; color: #334155;"><?= Html::encode($pMig['description']) ?></td>
                                        <td style="padding: 10px 12px;">
                                            <?php if ($pMig['riskLevel'] === 'POTENTIALLY_DESTRUCTIVE'): ?>
                                                <span style="background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">Destructive</span>
                                            <?php else: ?>
                                                <span style="background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">Standard</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 10px 12px;">
                                            <span style="background: #fef3c7; color: #b45309; padding: 2px 8px; border-radius: 4px; font-weight: 600; font-size: 0.75rem;">Pending Execution</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top: 16px; padding-top: 14px; border-top: 1px solid #fde68a; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                        <div style="font-size: 0.8rem; color: #92400e;">
                            <span>Alternative CLI execution:</span>
                            <code style="background: #fef3c7; padding: 4px 10px; border-radius: 4px; border: 1px solid #fde68a; font-weight: 600; margin-left: 4px;">php ./yii migrate/up</code>
                        </div>
                        <div>
                            <button
                                type="button"
                                style="background: #0284c7; color: #fff; border: none; font-weight: 600; padding: 8px 18px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 0.875rem; box-shadow: 0 1px 2px rgba(0,0,0,0.08); transition: background 0.2s;"
                                onmouseover="this.style.background='#0369a1';"
                                onmouseout="this.style.background='#0284c7';"
                                onclick="document.getElementById('migrationConfirmModal').style.display='flex';"
                            >
                                <span style="font-size: 0.85rem;">▶</span>
                                <span>Run Migration (<?= count($migrationStatus['pendingMigrations']) ?>)</span>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- CARD 3: DATABASE SCHEMA SYNCHRONIZATION PLAN & PREVIEW CHANGES -->
    <div class="rs-card rs-mb-5" style="border: 1px solid var(--rs-border, #e2e8f0); margin-bottom: 1.5rem; border-radius: 8px; background: #fff;">
        <div class="rs-card__header rs-d-flex rs-align-items-center rs-justify-content-between" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #e2e8f0);">
            <div style="display: flex; align-items: center; gap: 8px;">
                <h4 class="rs-card__title rs-m-0" style="font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0;">3. Schema Synchronization Plan &amp; Preview</h4>
                <span style="font-size: 0.75rem; background: #eff6ff; color: #1d4ed8; padding: 2px 8px; border-radius: 12px; font-weight: 600;">Risk-Classified</span>
            </div>
            <span class="rs-badge <?= $schemaBadgeClass ?>" style="padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">
                <?= Html::encode($schemaText) ?>
            </span>
        </div>
        <div class="rs-card__body rs-p-5" style="padding: 20px;">
            <p class="rs-text-muted rs-mb-4" style="color: #64748b; margin-bottom: 1rem;">
                Comparing live database structures against the canonical <code>ExpectedSchemaManifest</code> (36 tables, 352 columns, 147 indexes, 44 foreign keys). Differences are categorized strictly by execution risk:
            </p>

            <!-- 4-TILE RISK SUMMARY -->
            <div class="rs-grid rs-grid--cols-2 rs-grid--cols-md-4 rs-gap-4 rs-mb-4" style="margin-bottom: 1.25rem;">
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 14px 16px; border-radius: 8px;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 0.75rem; font-weight: 700; color: #166534; text-transform: uppercase;">Safe Additions</span>
                        <span style="font-size: 1.1rem;">🟢</span>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: #15803d; margin-top: 4px;">
                        <?= (int)($syncPlan['counts']['safeAdditions'] ?? 0) ?>
                    </div>
                    <div style="font-size: 0.75rem; color: #166534; margin-top: 2px;">
                        Missing tables, columns, indexes
                    </div>
                </div>

                <div style="background: #fffbeb; border: 1px solid #fde68a; padding: 14px 16px; border-radius: 8px;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 0.75rem; font-weight: 700; color: #92400e; text-transform: uppercase;">Modifications</span>
                        <span style="font-size: 1.1rem;">🟡</span>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: #b45309; margin-top: 4px;">
                        <?= (int)($syncPlan['counts']['modifications'] ?? 0) ?>
                    </div>
                    <div style="font-size: 0.75rem; color: #92400e; margin-top: 2px;">
                        Type/nullability adjustments
                    </div>
                </div>

                <div style="background: #faf5ff; border: 1px solid #e9d5ff; padding: 14px 16px; border-radius: 8px;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 0.75rem; font-weight: 700; color: #6b21a8; text-transform: uppercase;">Manual Review</span>
                        <span style="font-size: 1.1rem;">🟣</span>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: #7e22ce; margin-top: 4px;">
                        <?= (int)($syncPlan['counts']['manualReview'] ?? 0) ?>
                    </div>
                    <div style="font-size: 0.75rem; color: #6b21a8; margin-top: 2px;">
                        Data truncation / integrity risk
                    </div>
                </div>

                <div style="background: #fef2f2; border: 1px solid #fecaca; padding: 14px 16px; border-radius: 8px;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 0.75rem; font-weight: 700; color: #991b1b; text-transform: uppercase;">Destructive</span>
                        <span style="font-size: 1.1rem;">🔴</span>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: #dc2626; margin-top: 4px;">
                        <?= (int)($syncPlan['counts']['destructive'] ?? 0) ?>
                    </div>
                    <div style="font-size: 0.75rem; color: #991b1b; margin-top: 2px;">
                        Drops protected (Never auto-run)
                    </div>
                </div>
            </div>

            <!-- SYNCHRONIZED STATE NOTICE -->
            <?php if ($isSynced): ?>
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px; display: flex; align-items: center; gap: 12px; margin-bottom: 1.25rem;">
                    <div style="background: #22c55e; color: #fff; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">✓</div>
                    <div>
                        <strong style="color: #166534; font-size: 0.95rem;">Database Schema is 100% Compliant</strong>
                        <div style="color: #15803d; font-size: 0.85rem; margin-top: 2px;">
                            All 36 domain tables, 352 columns, 147 indexes, and 44 foreign keys in <code><?= Html::encode($dbHealth['database'] ?? 'sortedreviews_sys') ?></code> exactly match the canonical manifest. No synchronization actions are required.
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- SAFE ADDITIONS TABLE (IF ANY) -->
            <?php if (!empty($syncPlan['safeAdditions'])): ?>
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px; margin-bottom: 1.25rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 1.1rem;">🟢</span>
                            <strong style="color: #166534; font-size: 0.95rem;">Safe Additions (<?= count($syncPlan['safeAdditions']) ?>)</strong>
                        </div>
                        <span style="background: #dcfce7; color: #15803d; font-size: 0.75rem; font-weight: 600; padding: 2px 8px; border-radius: 4px;">Approved for Auto-Sync</span>
                    </div>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; background: #fff; border-radius: 6px; overflow: hidden; border: 1px solid #dcfce7;">
                            <thead>
                                <tr style="background: #dcfce7; color: #166534; text-align: left;">
                                    <th style="padding: 8px 12px;">Table</th>
                                    <th style="padding: 8px 12px;">Object</th>
                                    <th style="padding: 8px 12px;">Operation</th>
                                    <th style="padding: 8px 12px;">Description</th>
                                    <th style="padding: 8px 12px;">SQL Preview</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($syncPlan['safeAdditions'] as $item): ?>
                                    <tr style="border-bottom: 1px solid #dcfce7;">
                                        <td style="padding: 8px 12px; font-weight: 600; color: #0f172a;"><code><?= Html::encode($item['tableName']) ?></code></td>
                                        <td style="padding: 8px 12px; color: #475569;"><?= Html::encode($item['objectType']) ?> (<?= Html::encode($item['objectName']) ?>)</td>
                                        <td style="padding: 8px 12px;"><span style="background: #dcfce7; color: #15803d; padding: 2px 6px; border-radius: 4px; font-weight: 600; font-size: 0.75rem;"><?= Html::encode($item['operation']) ?></span></td>
                                        <td style="padding: 8px 12px; color: #334155;"><?= Html::encode($item['description']) ?></td>
                                        <td style="padding: 8px 12px;"><code style="font-size: 0.75rem; color: #0f172a; background: #f8fafc; padding: 2px 6px; border-radius: 3px; display: inline-block; max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= Html::encode($item['sql']) ?></code></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- SAFE MODIFICATIONS TABLE (IF ANY) -->
            <?php if (!empty($syncPlan['modifications'])): ?>
                <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 16px; margin-bottom: 1.25rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 1.1rem;">🟡</span>
                            <strong style="color: #92400e; font-size: 0.95rem;">Safe Modifications (<?= count($syncPlan['modifications']) ?>)</strong>
                        </div>
                        <span style="background: #fef3c7; color: #b45309; font-size: 0.75rem; font-weight: 600; padding: 2px 8px; border-radius: 4px;">Approved for Auto-Sync</span>
                    </div>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; background: #fff; border-radius: 6px; overflow: hidden; border: 1px solid #fef3c7;">
                            <thead>
                                <tr style="background: #fef3c7; color: #92400e; text-align: left;">
                                    <th style="padding: 8px 12px;">Table</th>
                                    <th style="padding: 8px 12px;">Column</th>
                                    <th style="padding: 8px 12px;">Operation</th>
                                    <th style="padding: 8px 12px;">Description</th>
                                    <th style="padding: 8px 12px;">SQL Preview</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($syncPlan['modifications'] as $item): ?>
                                    <tr style="border-bottom: 1px solid #fef3c7;">
                                        <td style="padding: 8px 12px; font-weight: 600; color: #0f172a;"><code><?= Html::encode($item['tableName']) ?></code></td>
                                        <td style="padding: 8px 12px; color: #475569;"><code><?= Html::encode($item['objectName']) ?></code></td>
                                        <td style="padding: 8px 12px;"><span style="background: #fef3c7; color: #b45309; padding: 2px 6px; border-radius: 4px; font-weight: 600; font-size: 0.75rem;"><?= Html::encode($item['operation']) ?></span></td>
                                        <td style="padding: 8px 12px; color: #334155;"><?= Html::encode($item['description']) ?></td>
                                        <td style="padding: 8px 12px;"><code style="font-size: 0.75rem; color: #0f172a; background: #f8fafc; padding: 2px 6px; border-radius: 3px; display: inline-block; max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= Html::encode($item['sql']) ?></code></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- MANUAL REVIEW REQUIRED TABLE (IF ANY) -->
            <?php if (!empty($syncPlan['manualReviewRequired'])): ?>
                <div style="background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 8px; padding: 16px; margin-bottom: 1.25rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 1.1rem;">🟣</span>
                            <strong style="color: #6b21a8; font-size: 0.95rem;">Manual Review Required (<?= count($syncPlan['manualReviewRequired']) ?>)</strong>
                        </div>
                        <span style="background: #f3e8ff; color: #7e22ce; font-size: 0.75rem; font-weight: 600; padding: 2px 8px; border-radius: 4px;">Caution: Data Safety</span>
                    </div>
                    <p style="color: #6b21a8; font-size: 0.85rem; margin-bottom: 10px;">
                        These operations involve populated tables where column modifications or constraints could cause data truncation or integrity violations. They are excluded from automated synchronization.
                    </p>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; background: #fff; border-radius: 6px; overflow: hidden; border: 1px solid #f3e8ff;">
                            <thead>
                                <tr style="background: #f3e8ff; color: #6b21a8; text-align: left;">
                                    <th style="padding: 8px 12px;">Table</th>
                                    <th style="padding: 8px 12px;">Object</th>
                                    <th style="padding: 8px 12px;">Operation</th>
                                    <th style="padding: 8px 12px;">Risk Reason</th>
                                    <th style="padding: 8px 12px;">Recommended Manual Procedure</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($syncPlan['manualReviewRequired'] as $item): ?>
                                    <tr style="border-bottom: 1px solid #f3e8ff;">
                                        <td style="padding: 8px 12px; font-weight: 600; color: #0f172a;"><code><?= Html::encode($item['tableName']) ?></code></td>
                                        <td style="padding: 8px 12px; color: #475569;"><?= Html::encode($item['objectName']) ?></td>
                                        <td style="padding: 8px 12px;"><span style="background: #f3e8ff; color: #7e22ce; padding: 2px 6px; border-radius: 4px; font-weight: 600; font-size: 0.75rem;"><?= Html::encode($item['operation']) ?></span></td>
                                        <td style="padding: 8px 12px; color: #991b1b;"><?= Html::encode($item['riskReason'] ?? 'Potential data truncation') ?></td>
                                        <td style="padding: 8px 12px; color: #334155;"><?= Html::encode($item['manualAction'] ?? 'Backup table data before applying schema alteration.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- DESTRUCTIVE CHANGES TABLE (IF ANY) -->
            <?php if (!empty($syncPlan['destructiveChanges'])): ?>
                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px; margin-bottom: 1.25rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 1.1rem;">🔴</span>
                            <strong style="color: #991b1b; font-size: 0.95rem;">Destructive Changes Detected (<?= count($syncPlan['destructiveChanges']) ?>)</strong>
                        </div>
                        <span style="background: #fee2e2; color: #991b1b; font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: 4px; border: 1px solid #f87171;">PROTECTED — NEVER AUTO-EXECUTED</span>
                    </div>
                    <p style="color: #991b1b; font-size: 0.85rem; margin-bottom: 10px;">
                        The database contains objects not present in the ExpectedSchemaManifest. To safeguard your production data, <strong>the schema synchronizer will never drop any table, column, or index automatically</strong>. If these objects are obsolete, a database administrator must drop them manually after verifying backups.
                    </p>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; background: #fff; border-radius: 6px; overflow: hidden; border: 1px solid #fee2e2;">
                            <thead>
                                <tr style="background: #fee2e2; color: #991b1b; text-align: left;">
                                    <th style="padding: 8px 12px;">Object Type</th>
                                    <th style="padding: 8px 12px;">Object Name</th>
                                    <th style="padding: 8px 12px;">Operation</th>
                                    <th style="padding: 8px 12px;">Safety Policy</th>
                                    <th style="padding: 8px 12px;">Manual DDL (DBA Only)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($syncPlan['destructiveChanges'] as $item): ?>
                                    <tr style="border-bottom: 1px solid #fee2e2;">
                                        <td style="padding: 8px 12px; color: #475569;"><?= Html::encode($item['objectType']) ?></td>
                                        <td style="padding: 8px 12px; font-weight: 600; color: #0f172a;"><code><?= Html::encode($item['objectName']) ?></code></td>
                                        <td style="padding: 8px 12px;"><span style="background: #fee2e2; color: #991b1b; padding: 2px 6px; border-radius: 4px; font-weight: 600; font-size: 0.75rem;"><?= Html::encode($item['operation']) ?></span></td>
                                        <td style="padding: 8px 12px; color: #991b1b; font-weight: 600;">Skipped by safe execution engine</td>
                                        <td style="padding: 8px 12px;"><code style="font-size: 0.75rem; color: #991b1b; background: #fef2f2; padding: 2px 6px; border-radius: 3px;"><?= Html::encode($item['sql']) ?></code></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ACTION EXECUTION BAR (IF CHANGES CAN BE APPLIED) -->
            <?php if ($canSync): ?>
                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 16px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-top: 1rem;">
                    <div>
                        <strong style="color: #0f172a; font-size: 0.95rem;">Ready to Apply Safe Synchronization</strong>
                        <div style="color: #64748b; font-size: 0.85rem; margin-top: 2px;">
                            Applies <?= (int)($syncPlan['counts']['safeAdditions'] ?? 0) ?> safe additions and <?= (int)($syncPlan['counts']['modifications'] ?? 0) ?> non-destructive modifications. Destructive changes are skipped.
                        </div>
                    </div>
                    <button
                        type="button"
                        class="rs-btn rs-btn--primary"
                        style="background: #2563eb; color: #fff; font-weight: 600; padding: 10px 22px; border-radius: 6px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 0.95rem;"
                        onclick="document.getElementById('syncConfirmModal').style.display='flex'"
                    >
                        ⚡ Apply Safe Synchronization
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- CARD 4: RECENT SYNCHRONIZATION EXECUTION REPORT (IF RECORDED) -->
    <?php if ($lastExecutionReport !== null): ?>
        <div class="rs-card rs-mb-5" style="border: 1px solid var(--rs-border, #e2e8f0); margin-bottom: 1.5rem; border-radius: 8px; background: #fff;">
            <div class="rs-card__header rs-d-flex rs-align-items-center rs-justify-content-between" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #e2e8f0);">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <h4 class="rs-card__title rs-m-0" style="font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0;">4. Recent Synchronization Execution Report</h4>
                    <span style="font-size: 0.75rem; background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 12px; font-weight: 600;">
                        ID: <?= Html::encode($lastExecutionReport['executionId'] ?? '') ?>
                    </span>
                </div>
                <div>
                    <?php if (($lastExecutionReport['failedCount'] ?? 0) > 0): ?>
                        <span class="rs-badge rs-badge--danger" style="background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">Partial Errors</span>
                    <?php else: ?>
                        <span class="rs-badge rs-badge--success" style="background: #ecfdf5; color: #065f46; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">All Operations Succeeded</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="rs-card__body rs-p-5" style="padding: 20px;">
                <div class="rs-grid rs-grid--cols-2 rs-grid--cols-md-4 rs-gap-4 rs-mb-4" style="background: #f8fafc; padding: 14px 16px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 1.25rem;">
                    <div>
                        <label style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; display: block;">Execution Time</label>
                        <span style="font-size: 0.95rem; font-weight: 600; color: #0f172a;"><?= Html::encode($lastExecutionReport['formattedDate'] ?? '') ?></span>
                    </div>
                    <div>
                        <label style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; display: block;">Succeeded</label>
                        <span style="font-size: 1.25rem; font-weight: 700; color: #16a34a;"><?= (int)($lastExecutionReport['successCount'] ?? 0) ?></span>
                    </div>
                    <div>
                        <label style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; display: block;">Failed</label>
                        <span style="font-size: 1.25rem; font-weight: 700; color: <?= ($lastExecutionReport['failedCount'] ?? 0) > 0 ? '#dc2626' : '#64748b' ?>;">
                            <?= (int)($lastExecutionReport['failedCount'] ?? 0) ?>
                        </span>
                    </div>
                    <div>
                        <label style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; display: block;">Skipped</label>
                        <span style="font-size: 1.25rem; font-weight: 700; color: #0284c7;"><?= (int)($lastExecutionReport['skippedCount'] ?? 0) ?></span>
                    </div>
                </div>

                <?php if (!empty($lastExecutionReport['operations'])): ?>
                    <div style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 6px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                            <thead>
                                <tr style="background: #f8fafc; color: #475569; text-align: left; border-bottom: 1px solid #e2e8f0;">
                                    <th style="padding: 8px 12px;">Status</th>
                                    <th style="padding: 8px 12px;">Table</th>
                                    <th style="padding: 8px 12px;">Object</th>
                                    <th style="padding: 8px 12px;">Operation</th>
                                    <th style="padding: 8px 12px;">DDL Executed</th>
                                    <th style="padding: 8px 12px; text-align: right;">Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lastExecutionReport['operations'] as $op): ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td style="padding: 8px 12px;">
                                            <?php if ($op['status'] === 'SUCCESS'): ?>
                                                <span style="background: #dcfce7; color: #15803d; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">✓ SUCCESS</span>
                                            <?php elseif ($op['status'] === 'FAILED'): ?>
                                                <span style="background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">✖ FAILED</span>
                                            <?php else: ?>
                                                <span style="background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">SKIPPED</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 8px 12px; font-weight: 600; color: #0f172a;"><code><?= Html::encode($op['tableName']) ?></code></td>
                                        <td style="padding: 8px 12px; color: #475569;"><?= Html::encode($op['objectName']) ?></td>
                                        <td style="padding: 8px 12px; color: #334155;"><?= Html::encode($op['operation']) ?></td>
                                        <td style="padding: 8px 12px;">
                                            <code style="font-size: 0.75rem; color: #0f172a; background: #f8fafc; padding: 2px 6px; border-radius: 3px; display: inline-block; max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= Html::encode($op['sql']) ?></code>
                                            <?php if (!empty($op['error'])): ?>
                                                <div style="color: #dc2626; font-size: 0.75rem; margin-top: 2px;"><?= Html::encode($op['error']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 8px 12px; text-align: right; color: #64748b; font-family: monospace; font-size: 0.8rem;">
                                            <?= number_format((float)($op['durationMs'] ?? 0), 2) ?> ms
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- CARD 5: DATABASE SCHEMA COMPATIBILITY & TABLE INVENTORY -->
    <div class="rs-card rs-mb-5" style="border: 1px solid var(--rs-border, #e2e8f0); margin-bottom: 1.5rem; border-radius: 8px; background: #fff;">
        <div class="rs-card__header rs-d-flex rs-align-items-center rs-justify-content-between" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #e2e8f0);">
            <h4 class="rs-card__title rs-m-0" style="font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0;">5. Application Table Inventory &amp; Categorization</h4>
            <span class="rs-badge rs-badge--info" style="background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">
                <?= (int)($schemaReport['totalExpectedTables'] ?? 35) ?> Expected Domain Tables
            </span>
        </div>
        <div class="rs-card__body rs-p-5" style="padding: 20px;">
            <p class="rs-text-muted rs-mb-4" style="color: #64748b; margin-bottom: 1rem;">
                Breakdown of all application domain tables grouped across 11 core functional categories (migration infrastructure table isolated).
            </p>

            <div class="rs-grid rs-grid--cols-2 rs-grid--cols-md-4 rs-gap-4 rs-mb-4" style="background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 1.25rem;">
                <div>
                    <label class="rs-text-muted rs-font-size-xs rs-text-uppercase rs-d-block" style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Expected Tables</label>
                    <span class="rs-font-size-xl" style="font-size: 1.5rem; font-weight: 700; color: #0f172a;"><?= (int)($schemaReport['totalExpectedTables'] ?? 35) ?></span>
                </div>
                <div>
                    <label class="rs-text-muted rs-font-size-xs rs-text-uppercase rs-d-block" style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Missing Tables</label>
                    <span class="rs-font-size-xl" style="font-size: 1.5rem; font-weight: 700; color: <?= ($schemaReport['missingTableCount'] ?? 0) > 0 ? '#dc2626' : '#16a34a' ?>;">
                        <?= (int)($schemaReport['missingTableCount'] ?? 0) ?>
                    </span>
                </div>
                <div>
                    <label class="rs-text-muted rs-font-size-xs rs-text-uppercase rs-d-block" style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Expected Columns</label>
                    <span class="rs-font-size-xl" style="font-size: 1.5rem; font-weight: 700; color: #0f172a;"><?= (int)($schemaReport['totalExpectedColumns'] ?? 352) ?></span>
                </div>
                <div>
                    <label class="rs-text-muted rs-font-size-xs rs-text-uppercase rs-d-block" style="font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Schema Discrepancies</label>
                    <span class="rs-font-size-xl" style="font-size: 1.5rem; font-weight: 700; color: <?= (!empty($schemaReport['differences'])) ? '#dc2626' : '#16a34a' ?>;">
                        <?= count($schemaReport['differences'] ?? []) ?>
                    </span>
                </div>
            </div>

            <!-- TABLE INVENTORY BREAKDOWN -->
            <?php if (!empty($schemaReport['tables'])): ?>
                <div style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 6px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                        <thead>
                            <tr style="background: #f8fafc; color: #475569; text-align: left; border-bottom: 1px solid #e2e8f0;">
                                <th style="padding: 8px 12px;">Table Name</th>
                                <th style="padding: 8px 12px;">Category / Domain</th>
                                <th style="padding: 8px 12px; text-align: center;">Columns</th>
                                <th style="padding: 8px 12px; text-align: center;">Required</th>
                                <th style="padding: 8px 12px; text-align: right;">Schema Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schemaReport['tables'] as $tName => $tInfo): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 8px 12px; font-weight: 600; color: #0f172a;">
                                        <code><?= Html::encode($tName) ?></code>
                                        <span style="font-size: 0.75rem; color: #64748b; margin-left: 6px;">(<?= Html::encode($tInfo['label']) ?>)</span>
                                    </td>
                                    <td style="padding: 8px 12px; color: #475569; text-transform: capitalize;">
                                        <?= Html::encode(str_replace('_', ' ', $tInfo['category'])) ?>
                                    </td>
                                    <td style="padding: 8px 12px; text-align: center; color: #334155;">
                                        <?= (int)$tInfo['columnCountActual'] ?> / <?= (int)$tInfo['columnCountExpected'] ?>
                                    </td>
                                    <td style="padding: 8px 12px; text-align: center;">
                                        <?php if (!empty($tInfo['required'])): ?>
                                            <span style="color: #0369a1; font-weight: 600;">Core</span>
                                        <?php else: ?>
                                            <span style="color: #64748b;">Optional</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 8px 12px; text-align: right;">
                                        <?php if ($tInfo['status'] === 'HEALTHY'): ?>
                                            <span style="background: #ecfdf5; color: #065f46; font-weight: 600; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem;">● Healthy</span>
                                        <?php elseif ($tInfo['status'] === 'MISSING'): ?>
                                            <span style="background: #fee2e2; color: #991b1b; font-weight: 600; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem;">✖ Missing Table</span>
                                        <?php else: ?>
                                            <span style="background: #fef3c7; color: #b45309; font-weight: 600; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem;">▲ Incomplete</span>
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

<!-- CSRF-PROTECTED SYNCHRONIZATION FORM (SUBMITTED VIA MODAL) -->
<form id="dbSyncForm" method="post" action="<?= \App\Environment::url('/global-settings?section=database_migration') ?>" style="display: none;">
    <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
    <input type="hidden" name="action" value="sync_database">
    <input type="hidden" name="confirm_backup" id="formBackupConfirmed" value="0">
</form>

<!-- CSRF-PROTECTED MIGRATION EXECUTION FORM (SUBMITTED VIA MODAL) -->
<form id="dbMigrateForm" method="post" action="<?= \App\Environment::url('/global-settings?section=database_migration') ?>" style="display: none;">
    <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
    <input type="hidden" name="action" value="run_migration">
</form>

<!-- RUN MIGRATION CONFIRMATION MODAL -->
<div id="migrationConfirmModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.65); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(2px);">
    <div style="background: #fff; border-radius: 12px; max-width: 580px; width: 92%; padding: 26px; box-shadow: 0 25px 35px -5px rgba(0, 0, 0, 0.25); animation: fadeIn 0.2s ease-out;">
        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px;">
            <div style="width: 46px; height: 46px; border-radius: 50%; background: #e0f2fe; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #0284c7; flex-shrink: 0;">
                ▶
            </div>
            <div>
                <h4 style="margin: 0; font-size: 1.2rem; font-weight: 700; color: #0f172a;">Execute Database Migration</h4>
                <p style="margin: 2px 0 0; font-size: 0.85rem; color: #64748b;">Apply pending code-based migrations to the connected database.</p>
            </div>
        </div>

        <!-- MIGRATION SUMMARY -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 16px; margin-bottom: 16px; font-size: 0.875rem;">
            <div style="margin-bottom: 6px;"><strong>Target Database:</strong> <code><?= Html::encode($dbHealth['database'] ?? 'sortedreviews_sys') ?></code></div>
            <div style="margin-bottom: 6px;"><strong>Pending Migrations to Apply:</strong> <?= count($migrationStatus['pendingMigrations'] ?? []) ?></div>
            <div style="max-height: 140px; overflow-y: auto; margin-top: 8px; border: 1px solid #e2e8f0; border-radius: 6px; background: #fff; padding: 8px;">
                <?php foreach (($migrationStatus['pendingMigrations'] ?? []) as $pMig): ?>
                    <div style="padding: 6px 8px; border-bottom: 1px solid #f1f5f9; font-size: 0.8rem; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <code style="font-weight: 600; color: #0369a1;"><?= Html::encode($pMig['version']) ?></code>
                            <div style="color: #64748b; font-size: 0.75rem; margin-top: 2px;"><?= Html::encode($pMig['description']) ?></div>
                        </div>
                        <span style="background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: 600;">
                            <?= Html::encode($pMig['riskLevel']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                <span style="color: #16a34a; font-weight: bold;">✓</span>
                <strong style="color: #166534; font-size: 0.85rem;">Safety Protections Active</strong>
            </div>
            <ul style="margin: 0; padding-left: 18px; color: #166534; font-size: 0.8rem; line-height: 1.45;">
                <li>Concurrent execution lock acquired automatically before applying changes.</li>
                <li>Each migration runs in its defined transaction (<code>safeUp</code>) and records to the <code>migration</code> table upon completion.</li>
            </ul>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button
                type="button"
                style="background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; font-weight: 600; padding: 9px 18px; border-radius: 6px; cursor: pointer;"
                onclick="document.getElementById('migrationConfirmModal').style.display='none'"
            >
                Cancel
            </button>
            <button
                type="button"
                id="confirmMigrationBtn"
                style="background: #0284c7; color: #fff; border: none; font-weight: 600; padding: 9px 22px; border-radius: 6px; cursor: pointer; transition: opacity 0.2s;"
                onclick="document.getElementById('dbMigrateForm').submit(); this.disabled=true; this.innerText='Executing Migration...';"
            >
                Confirm &amp; Run Migration
            </button>
        </div>
    </div>
</div>

<!-- CONFIRMATION MODAL -->
<div id="syncConfirmModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.65); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(2px);">
    <div style="background: #fff; border-radius: 12px; max-width: 580px; width: 92%; padding: 26px; box-shadow: 0 25px 35px -5px rgba(0, 0, 0, 0.25); animation: fadeIn 0.2s ease-out;">
        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px;">
            <div style="width: 46px; height: 46px; border-radius: 50%; background: #eff6ff; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #2563eb; flex-shrink: 0;">
                ⚡
            </div>
            <div>
                <h4 style="margin: 0; font-size: 1.2rem; font-weight: 700; color: #0f172a;">Confirm Database Schema Synchronization</h4>
                <p style="margin: 2px 0 0; font-size: 0.85rem; color: #64748b;">Review safety parameters and recovery considerations before execution.</p>
            </div>
        </div>

        <!-- SUMMARY TILES -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 16px; margin-bottom: 16px; font-size: 0.875rem;">
            <div style="margin-bottom: 6px;"><strong>Target Database:</strong> <code><?= Html::encode($dbHealth['database'] ?? 'sortedreviews_sys') ?></code> (<?= Html::encode($dbHealth['collation'] ?? 'utf8mb4_general_ci') ?>)</div>
            <div style="margin-bottom: 6px;"><strong>Approved Operations to Apply:</strong> <?= (int)($syncPlan['counts']['safeAdditions'] ?? 0) ?> Additions, <?= (int)($syncPlan['counts']['modifications'] ?? 0) ?> Modifications</div>
            <div style="color: #166534; font-size: 0.8rem; margin-top: 4px;">✓ Destructive operations (DROP TABLE / DROP COLUMN) are strictly protected and will NOT be executed.</div>
            <div style="color: #166534; font-size: 0.8rem; margin-top: 2px;">✓ Migration history table and pending migrations remain completely isolated.</div>
        </div>

        <!-- RS-SYNC-005: CRITICAL BACKUP & DDL RECOVERY WARNING -->
        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 14px 16px; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                <span style="font-size: 1.1rem;">⚠️</span>
                <strong style="color: #92400e; font-size: 0.875rem;">Mandatory Pre-Sync Backup &amp; DDL Recovery Notice</strong>
            </div>
            <ul style="margin: 0; padding-left: 18px; color: #78350f; font-size: 0.825rem; line-height: 1.45;">
                <li style="margin-bottom: 4px;">
                    <strong>DDL Implicit Commits:</strong> In MySQL/MariaDB, DDL statements (<code>CREATE TABLE</code>, <code>ALTER TABLE</code>) execute implicit commits and <strong>cannot be rolled back transactionally</strong>.
                </li>
                <li style="margin-bottom: 4px;">
                    <strong>Partial Execution Risk:</strong> If an operation fails midway through synchronization, previously executed statements remain permanently applied in the database.
                </li>
                <li>
                    <strong>Independent Backup Required:</strong> Automated backup verification is not performed by this system. You must independently ensure that a recent, verified database backup is available before proceeding.
                </li>
            </ul>
        </div>

        <!-- CONFIRMATION CHECKBOX -->
        <div style="margin-bottom: 18px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 14px;">
            <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; font-size: 0.85rem; color: #1e293b; user-select: none;">
                <input
                    type="checkbox"
                    id="syncBackupConfirmed"
                    style="margin-top: 3px; cursor: pointer;"
                    onchange="document.getElementById('formBackupConfirmed').value = this.checked ? '1' : '0'; document.getElementById('confirmSyncBtn').disabled = !this.checked;"
                >
                <span>I confirm that a verified database backup exists and acknowledge that MySQL DDL changes cannot be rolled back.</span>
            </label>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button
                type="button"
                style="background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; font-weight: 600; padding: 9px 18px; border-radius: 6px; cursor: pointer;"
                onclick="document.getElementById('syncConfirmModal').style.display='none'"
            >
                Cancel
            </button>
            <button
                type="button"
                id="confirmSyncBtn"
                disabled
                style="background: #2563eb; color: #fff; border: none; font-weight: 600; padding: 9px 22px; border-radius: 6px; cursor: pointer; transition: opacity 0.2s;"
                onclick="document.getElementById('dbSyncForm').submit(); this.disabled=true; this.innerText='Executing Safe Synchronization...';"
            >
                Confirm &amp; Synchronize
            </button>
        </div>
    </div>
</div>
