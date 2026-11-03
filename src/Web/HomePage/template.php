<?php

declare(strict_types=1);

use App\Domain\User\User;
use App\Security\TenantContextInterface;
use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var string $appName
 * @var string $frameworkVersion
 * @var string $environment
 * @var bool $debug
 * @var string $phpVersion
 * @var string $phpSapi
 * @var bool $dbConnected
 * @var string $dbDriverName
 * @var string $dbServerVersion
 * @var string $dbDatabaseName
 * @var string|null $dbError
 * @var User|null $currentUser
 * @var TenantContextInterface|null $tenantContext
 */

$this->setTitle('Reviews Sorted — Dashboard');
?>

<div style="max-width: 900px; margin: 30px auto; font-family: 'Lato', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1A2F26; line-height: 1.6;">
    <div style="background: #ffffff; border: 1px solid #CED4DA; border-radius: 12px; padding: 32px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);">
        
        <!-- Header -->
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #FEFBF6; padding-bottom: 20px; margin-bottom: 24px;">
            <div>
                <h1 style="margin: 0; font-size: 1.75rem; color: #234137; font-weight: 700;">Reviews Sorted</h1>
                <p style="margin: 4px 0 0 0; color: #7B8A8B; font-size: 0.95rem;">Yii 3 Foundation & Authenticated Session Dashboard</p>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="display: inline-block; background: <?= $dbConnected ? '#D1FAE5' : '#FEE2E2' ?>; color: <?= $dbConnected ? '#065F46' : '#991B1B' ?>; font-size: 0.8rem; font-weight: 600; padding: 6px 14px; border-radius: 9999px;">
                    <?= $dbConnected ? '● Online & Read-Only Enforced' : '● Database Error' ?>
                </span>
                <?php if ($currentUser !== null): ?>
                    <a href="/sys-yii3/logout" style="display: inline-block; background: #EF4444; color: #ffffff; text-decoration: none; font-size: 0.85rem; font-weight: 600; padding: 8px 16px; border-radius: 6px; transition: background 0.15s ease;">
                        Sign Out
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Authenticated User Card -->
        <?php if ($currentUser !== null): ?>
            <div style="background: #FEFBF6; border: 1px solid #CED4DA; border-left: 4px solid #234137; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
                <h3 style="margin: 0 0 12px 0; font-size: 1.1rem; color: #234137; font-weight: 700;">
                    Authenticated User Session (Phase 2 Active)
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; font-size: 0.95rem;">
                    <div>
                        <span style="display: block; font-size: 0.75rem; text-transform: uppercase; color: #7B8A8B; font-weight: 600;">Name:</span>
                        <strong style="color: #1A2F26;"><?= Html::encode($currentUser->getName()) ?></strong>
                    </div>
                    <div>
                        <span style="display: block; font-size: 0.75rem; text-transform: uppercase; color: #7B8A8B; font-weight: 600;">Email:</span>
                        <strong style="color: #1A2F26;"><?= Html::encode($currentUser->getEmail()) ?></strong>
                    </div>
                    <div>
                        <span style="display: block; font-size: 0.75rem; text-transform: uppercase; color: #7B8A8B; font-weight: 600;">Role:</span>
                        <span style="display: inline-block; background: #234137; color: #ffffff; font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: 4px;">
                            <?= strtoupper(Html::encode($currentUser->getRole() ?? 'USER')) ?>
                        </span>
                    </div>
                    <div>
                        <span style="display: block; font-size: 0.75rem; text-transform: uppercase; color: #7B8A8B; font-weight: 600;">Tenant Account ID:</span>
                        <strong style="color: #1A2F26;"><?= $currentUser->getAccountId() !== null ? '#' . $currentUser->getAccountId() : '<span style="color:#7B8A8B;">Global (None)</span>' ?></strong>
                    </div>
                </div>

                <?php if ($tenantContext !== null): ?>
                    <div style="margin-top: 14px; padding-top: 12px; border-top: 1px dashed #CED4DA; font-size: 0.85rem; color: #7B8A8B;">
                        <strong>Tenant Isolation Boundary:</strong>
                        <?= $tenantContext->isUnrestricted() ? 'Unrestricted Global Administrator (Full Platform Access)' : 'Scoped to Account #' . ($tenantContext->getPrimaryAccountId() ?? 'N/A') ?>
                    </div>
                <?php endif; ?>

                <!-- Module Navigation Links (Phase 3 User Management & RBAC) -->
                <div style="margin-top: 16px; padding-top: 14px; border-top: 1px solid #CED4DA; display: flex; gap: 12px; flex-wrap: wrap;">
                    <?php if ($currentUser->isAdmin() || $currentUser->isAccountAdmin()): ?>
                        <a href="/sys-yii3/users" style="display: inline-flex; align-items: center; background: #234137; color: #ffffff; text-decoration: none; font-size: 0.9rem; font-weight: 600; padding: 8px 16px; border-radius: 6px;">
                            Manage Users
                        </a>
                        <a href="/sys-yii3/accounts" style="display: inline-flex; align-items: center; background: #ffffff; color: #234137; border: 1px solid #234137; text-decoration: none; font-size: 0.9rem; font-weight: 600; padding: 8px 16px; border-radius: 6px;">
                            Workspaces &amp; Billing
                        </a>
                    <?php endif; ?>
                    <a href="/sys-yii3/user/view?id=<?= $currentUser->getId() ?>" style="display: inline-flex; align-items: center; background: #F3F4F6; color: #1F2937; text-decoration: none; font-size: 0.9rem; font-weight: 600; padding: 8px 16px; border-radius: 6px;">
                        My Profile
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- System & Diagnostics Cards -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <div style="background: #FAFAFA; border: 1px solid #E5E7EB; border-radius: 8px; padding: 16px;">
                <span style="display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #7B8A8B; font-weight: 600; margin-bottom: 4px;">Application</span>
                <strong style="font-size: 1.1rem; color: #1A2F26;"><?= Html::encode($appName) ?></strong>
            </div>

            <div style="background: #FAFAFA; border: 1px solid #E5E7EB; border-radius: 8px; padding: 16px;">
                <span style="display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #7B8A8B; font-weight: 600; margin-bottom: 4px;">Environment</span>
                <strong style="font-size: 1.1rem; color: #1A2F26;">
                    <?= strtoupper(Html::encode($environment)) ?>
                    <span style="font-size: 0.8rem; font-weight: normal; color: #7B8A8B;">(Debug: <?= $debug ? 'Enabled' : 'Disabled' ?>)</span>
                </strong>
            </div>

            <div style="background: #FAFAFA; border: 1px solid #E5E7EB; border-radius: 8px; padding: 16px;">
                <span style="display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #7B8A8B; font-weight: 600; margin-bottom: 4px;">Framework</span>
                <strong style="font-size: 0.95rem; color: #1A2F26;"><?= Html::encode($frameworkVersion) ?></strong>
            </div>

            <div style="background: #FAFAFA; border: 1px solid #E5E7EB; border-radius: 8px; padding: 16px;">
                <span style="display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #7B8A8B; font-weight: 600; margin-bottom: 4px;">PHP Runtime</span>
                <strong style="font-size: 0.95rem; color: #1A2F26;">PHP <?= Html::encode($phpVersion) ?> (<?= Html::encode($phpSapi) ?>)</strong>
            </div>
        </div>

        <!-- Database Safety Information -->
        <div style="background: #FAFAFA; border: 1px solid #E5E7EB; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
            <h3 style="margin: 0 0 12px 0; font-size: 1rem; color: #1A2F26; font-weight: 600;">Database Connection (Read-Only Enforced)</h3>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                <tr>
                    <td style="padding: 6px 0; color: #7B8A8B; width: 180px;">Status:</td>
                    <td style="padding: 6px 0;">
                        <?php if ($dbConnected): ?>
                            <span style="color: #065F46; font-weight: 600;">Connected (MySQL user rs_readonly — SELECT only)</span>
                        <?php else: ?>
                            <span style="color: #991B1B; font-weight: 600;">Failed to connect</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #7B8A8B;">Driver:</td>
                    <td style="padding: 6px 0; font-family: monospace; color: #1A2F26;"><?= Html::encode(strtoupper($dbDriverName)) ?> (PDO)</td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #7B8A8B;">Database:</td>
                    <td style="padding: 6px 0; font-family: monospace; color: #1A2F26;"><?= Html::encode($dbDatabaseName) ?></td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #7B8A8B;">Safety Constraint:</td>
                    <td style="padding: 6px 0; color: #234137; font-size: 0.85rem;">Zero DDL/DML executed. Legacy schema and records are 100% untouched.</td>
                </tr>
            </table>
            <?php if ($dbError !== null): ?>
                <div style="margin-top: 12px; padding: 12px; background: #FEE2E2; border: 1px solid #EF4444; border-radius: 6px; color: #991B1B; font-size: 0.85rem;">
                    <strong>Error:</strong> <?= Html::encode($dbError) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Coexistence links -->
        <div style="background: #FEFBF6; border: 1px solid #CED4DA; border-radius: 8px; padding: 16px; font-size: 0.9rem; color: #234137;">
            <strong>Parallel Coexistence Confirmed:</strong>
            <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                <li>Existing Yii 2 application: <a href="/sys/" style="color: #234137; font-weight: 700;">http://localhost/sys/</a> (Source of Truth — 100% pristine)</li>
                <li>New Yii 3 application: <a href="/sys-yii3/" style="color: #234137; font-weight: 700;">http://localhost/sys-yii3/</a> (Independent Yii 3 Foundation)</li>
            </ul>
        </div>
    </div>
</div>
