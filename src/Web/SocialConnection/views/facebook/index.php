<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var array<int, App\Domain\SocialConnection\ServiceConfig> $configs
 * @var int $totalCount
 * @var int $page
 * @var int $pageCount
 * @var int $pageSize
 * @var App\Domain\User\User $currentUser
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 */

$this->setTitle('Facebook Pages');

$isPlatformAdmin = $currentUser->isAdmin();
?>

<div class="rs-container">
    <?= $this->render('../partials/_header', [
        'title' => 'Facebook Pages',
        'subtitle' => $isPlatformAdmin ? 'Platform Administrator. Manage global application settings.' : 'Company Settings. Manage connected Facebook Pages for your company.'
    ]) ?>

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
    <?php if ($flash->has('info')): ?>
        <div class="rs-alert rs-alert--info rs-mb-4" role="alert">
            <?= Html::encode((string) $flash->get('info')) ?>
        </div>
    <?php endif; ?>

    <?php if ($isPlatformAdmin): ?>
        <?php
        $activeCount = 0;
        foreach ($configs as $cfg) {
            if ($cfg->isLinked()) {
                $activeCount++;
            }
        }
        $kpiCards = [
            ['label' => 'Total Pages', 'value' => $totalCount, 'helper' => 'Facebook Pages Managed', 'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>'],
            ['label' => 'Active Connections', 'value' => $activeCount, 'helper' => 'Currently Linked & Operational', 'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>'],
        ];
        echo $this->render('../partials/_kpi_cards', ['cards' => $kpiCards]);
        ?>

        <!-- Application Configuration Card (Admin Only) -->
        <div class="rs-section rs-mb-5">
            <div class="rs-card rs-card--shadow" style="background:#fff; border-radius:8px; border:1px solid #e2e8f0; margin-bottom: 1.5rem;">
                <?= $this->render('../partials/_card_header', [
                    'title' => 'Facebook Application',
                    'subtitle' => 'Configure the Facebook application credentials used for integration.'
                ]) ?>
                <div class="rs-card__body rs-p-4" style="padding: 1.5rem;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                        <div>
                            <div class="rs-text-xs rs-text-muted rs-text-uppercase rs-mb-1" style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Application Name</div>
                            <div class="rs-font-bold rs-font-size-lg" style="font-size: 1.125rem; font-weight: 700;">Reviews Sorted App</div>
                        </div>
                        <div>
                            <div class="rs-text-xs rs-text-muted rs-text-uppercase rs-mb-1" style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Integration Mode</div>
                            <div class="rs-font-bold rs-font-size-lg" style="font-size: 1.125rem; font-weight: 700;">Webhook &amp; Graph API v19.0</div>
                        </div>
                        <div>
                            <div class="rs-text-xs rs-text-muted rs-text-uppercase rs-mb-1" style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Platform Security</div>
                            <div class="rs-font-bold rs-font-size-lg" style="font-size: 1.125rem; font-weight: 700;">Tenant-Isolated Secrets</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Facebook Authorization Card -->
    <div class="rs-section rs-mb-5">
        <div class="rs-card rs-card--shadow" style="background:#fff; border-radius:8px; border:1px solid #e2e8f0; padding: 1.5rem; margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1.5rem;">
                <div style="flex: 1 1 500px;">
                    <div style="display: flex; align-items: center; margin-bottom: 0.75rem;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="#1877F2" style="margin-right: 0.5rem;"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        <h3 class="rs-card__title rs-m-0" style="font-size: 1.25rem; font-weight: 700; margin: 0;">Facebook Authorization</h3>
                    </div>
                    <p class="rs-text-main rs-mb-3" style="color: #334155; margin-bottom: 0.75rem;">
                        To manage your Facebook Pages reviews and social publishing in our platform, you need first to authorize them by clicking on the button.
                    </p>
                    <p class="rs-text-sm rs-text-muted rs-mb-3" style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.75rem;">
                        It will request permission to manage pages for the profiles you choose. These permissions are only used by our platform to load the page's reviews and publish social posts and review replies you compose here.
                    </p>
                    <ul class="rs-text-sm rs-text-muted" style="font-size: 0.875rem; color: #64748b; padding-left: 1.25rem; margin: 0;">
                        <li style="margin-bottom: 0.25rem;">You can revoke those permissions any time you want here or on Facebook (Settings &rarr; Business Integrations).</li>
                        <li>No automatic messages, comments, posts, or replies will ever be sent without explicit initiation.</li>
                    </ul>
                </div>
                <div style="flex-shrink: 0; display: flex; flex-direction: column; align-items: flex-end;">
                    <a href="/sys-yii3/settings/facebook/setup" class="rs-btn rs-btn--primary" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.65rem 1.25rem; font-weight: 600; text-decoration: none; border-radius: 6px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="#ffffff"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        Continue with Facebook
                    </a>
                    <div style="display: flex; align-items: center; margin-top: 0.75rem;">
                        <span class="rs-badge rs-badge--success rs-badge--soft" style="margin-right: 0.5rem;">● Connected</span>
                        <span class="rs-text-muted rs-text-xs" style="font-size: 0.75rem; color: #64748b;">Account integration active</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Connected Pages Table Section -->
    <div class="rs-section">
        <?php if (empty($configs)): ?>
            <?= $this->render('../partials/_empty_state', [
                'message' => 'No Facebook Pages connected yet. Authorize your Facebook account to manage pages.',
                'actionUrl' => '/sys-yii3/settings/facebook/setup',
                'actionLabel' => 'Connect Facebook Page'
            ]) ?>
        <?php else: ?>
            <div class="rs-card rs-card--shadow" style="background:#fff; border-radius:8px; border:1px solid #e2e8f0; overflow:hidden;">
                <?= $this->render('../partials/_card_header', [
                    'title' => 'Connected Facebook Pages',
                    'subtitle' => 'Pages and linked Instagram accounts currently managed by your account'
                ]) ?>
                <div class="rs-table-wrapper" style="overflow-x: auto;">
                    <table class="rs-table rs-table--hover" style="width: 100%; min-width: 780px; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                <th style="padding: 12px 16px; width: 28%; min-width: 180px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b;">Label / Page Name</th>
                                <th style="padding: 12px 16px; width: 20%; min-width: 140px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b;">Page ID</th>
                                <th style="padding: 12px 16px; width: 22%; min-width: 150px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b;">Instagram Accounts</th>
                                <th style="padding: 12px 16px; width: 10%; min-width: 90px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b;">Status</th>
                                <th style="padding: 12px 16px; width: 10%; min-width: 95px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b;">Connected On</th>
                                <th style="padding: 12px 16px; width: 18%; min-width: 170px; text-align: right; font-size: 0.8rem; text-transform: uppercase; color: #64748b;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($configs as $cfg): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 14px 16px;">
                                        <div style="font-weight: 600; color: #0f172a; font-size: 0.95rem;"><?= Html::encode($cfg->getDisplayName()) ?></div>
                                        <?php if (!empty($cfg->getEmailLowReview())): ?>
                                            <div style="font-size: 0.75rem; color: #64748b; margin-top: 2px;">
                                                Alert Email: <?= Html::encode($cfg->getEmailLowReview()) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 14px 16px; font-family: monospace; font-size: 0.85rem; color: #475569;">
                                        <?= Html::encode($cfg->getPageId() ?? 'N/A') ?>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <?php if (!empty($cfg->getSubServices())): ?>
                                            <?php foreach ($cfg->getSubServices() as $sub): ?>
                                                <span class="rs-badge rs-badge--default" style="display: inline-block; margin-bottom: 4px; background: #fdf2f8; color: #be185d; border: 1px solid #fbcfe8;">
                                                    📷 <?= Html::encode($sub->getName()) ?>
                                                </span><br/>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span style="font-size: 0.85rem; color: #94a3b8;">None connected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <?= $this->render('../partials/_status_badge', [
                                            'status' => $cfg->isLinked() ? 'Connected' : 'Not Linked'
                                        ]) ?>
                                    </td>
                                    <td style="padding: 14px 16px; font-size: 0.85rem; color: #64748b;">
                                        <?= $cfg->getCreatedAt() ? date('M d, Y', $cfg->getCreatedAt()) : 'N/A' ?>
                                    </td>
                                    <td style="padding: 14px 16px; text-align: right; white-space: nowrap;">
                                        <div style="display: inline-flex; gap: 6px; justify-content: flex-end;">
                                            <a href="/sys-yii3/settings/facebook/view?id=<?= $cfg->getId() ?>" class="rs-btn rs-btn--sm rs-btn--secondary" title="View Details">
                                                View
                                            </a>
                                            <a href="/sys-yii3/settings/facebook/update?id=<?= $cfg->getId() ?>" class="rs-btn rs-btn--sm rs-btn--outline" title="Edit Settings">
                                                Edit
                                            </a>
                                            <a href="/sys-yii3/settings/facebook/revoke?id=<?= $cfg->getId() ?>" class="rs-btn rs-btn--sm rs-btn--danger" onclick="return confirm('Are you sure you want to disconnect this page?');" title="Disconnect">
                                                Disconnect
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($pageCount > 1): ?>
                    <div style="padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e2e8f0;">
                        <span style="font-size: 0.875rem; color: #64748b;">Showing <?= count($configs) ?> of <?= $totalCount ?> Facebook pages</span>
                        <div style="display: flex; gap: 4px;">
                            <?php for ($p = 1; $p <= $pageCount; $p++): ?>
                                <a href="/sys-yii3/settings/facebook?page=<?= $p ?>" class="rs-btn rs-btn--sm <?= $p === $page ? 'rs-btn--primary' : 'rs-btn--secondary' ?>" style="min-width: 32px; text-align: center;">
                                    <?= $p ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
