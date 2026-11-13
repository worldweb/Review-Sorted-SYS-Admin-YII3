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
 * @var App\Domain\SocialConnection\Api|null $api
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 * @var Yiisoft\View\WebView $this
 */

$this->setTitle('Facebook Pages');

$isPlatformAdmin = $currentUser->isAdmin();
$isFbAuth = !empty($configs);
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
        // KPI Cards setup matching Yii2
        $connectedCompanies = count(array_unique(array_filter(array_map(static fn($c) => $c->getAccountId(), $configs))));
        $connectedPages = $totalCount;
        $activeConnections = count(array_filter($configs, static fn($c) => $c->isLinked()));

        $kpiCards = [
            [
                'label' => 'Connected Companies',
                'value' => max(1, $connectedCompanies),
                'helper' => 'Companies Connected',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M9 8h1M9 12h1M9 16h1M14 8h1M14 12h1M14 16h1M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"></path></svg>'
            ],
            [
                'label' => 'Connected Pages',
                'value' => $connectedPages,
                'helper' => 'Facebook Pages Managed',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>'
            ],
            [
                'label' => 'Active Connections',
                'value' => $activeConnections,
                'helper' => 'Currently Linked',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>'
            ],
        ];
        echo $this->render('../partials/_kpi_cards', ['cards' => $kpiCards]);
        ?>

        <!-- Application Configuration Card (Admin Only) -->
        <div class="rs-section rs-mb-5">
            <div class="rs-card rs-card--shadow">
                <?= $this->render('../partials/_card_header', [
                    'title' => 'Facebook Application',
                    'subtitle' => 'Configure the Facebook application used for integration.'
                ]) ?>

                <div class="rs-card__body rs-p-0">
                    <!-- Application Information -->
                    <div class="rs-p-4 md:rs-p-5">
                        <div class="rs-d-grid rs-grid-cols-1 md:rs-grid-cols-3 rs-gap-4" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
                            <div>
                                <div class="rs-text-xs rs-text-muted rs-text-uppercase rs-mb-1" style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Application Name</div>
                                <div class="rs-font-bold rs-font-size-lg" style="font-size: 1.125rem; font-weight: 700;"><?= Html::encode($api?->getLabel() ?? 'Reviews Sorted') ?></div>
                            </div>
                            <div>
                                <div class="rs-text-xs rs-text-muted rs-text-uppercase rs-mb-1" style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Client ID</div>
                                <div class="rs-font-bold rs-font-size-lg" style="font-size: 1.125rem; font-weight: 700;">
                                    <?php
                                    $clientId = $api?->getClientId() ?? '118837169123456';
                                    echo Html::encode(substr($clientId, 0, 4) . str_repeat('*', max(0, strlen($clientId) - 4)));
                                    ?>
                                </div>
                            </div>
                            <div>
                                <div class="rs-text-xs rs-text-muted rs-text-uppercase rs-mb-1" style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Client Secret</div>
                                <div class="rs-font-bold rs-font-size-lg" style="font-size: 1.125rem; font-weight: 700;"><?= str_repeat('*', 12) ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="rs-border-top" style="border-top: 1px solid var(--rs-border, #e2e8f0);"></div>

                    <!-- Facebook Authorization -->
                    <div class="rs-p-4 md:rs-p-5" style="padding: 1.5rem;">
                        <div class="rs-d-flex rs-align-items-center rs-mb-3" style="display: flex; align-items: center; margin-bottom: 0.75rem;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="#1877F2" class="rs-mr-2" style="margin-right: 0.5rem;">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                            </svg>
                            <h3 class="rs-card__title rs-m-0" style="font-size: 1.25rem; font-weight: 700; margin: 0;">Facebook Authorization</h3>
                        </div>

                        <div class="rs-mb-4" style="margin-bottom: 1.25rem;">
                            <p class="rs-text-main rs-mb-3" style="color: #334155; margin-bottom: 0.75rem;">To manage your Facebook Pages reviews and chats in our platform, you need first to authorize them by clicking on the button below.</p>

                            <p class="rs-text-sm rs-text-muted rs-mb-3" style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.75rem;">It will request permission to manage pages and chats for the pages you choose. These permissions are only used by our platform to load the page's chat conversations and reviews, and send the chat messages and review replies you input here.</p>

                            <ul class="rs-text-sm rs-text-muted rs-pl-4 rs-m-0" style="font-size: 0.875rem; color: #64748b; padding-left: 1.25rem; margin: 0; list-style-type: disc;">
                                <li class="rs-mb-1" style="margin-bottom: 0.25rem;">You can revoke those permissions any time you want here or on Facebook (Settings &rarr; Business Integrations).</li>
                                <li class="rs-mb-1">No automatic messages, comments, posts, or replies will ever be sent.</li>
                            </ul>
                        </div>

                        <div class="rs-mb-4" style="margin-bottom: 1.25rem;">
                            <a href="<?= \App\Environment::url('/settings/facebook/setup') ?>" class="rs-btn rs-btn--primary" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.65rem 1.25rem; font-weight: 600; text-decoration: none; border-radius: 6px;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="#ffffff">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                </svg>
                                Continue with Facebook
                            </a>
                        </div>

                        <div class="rs-border-top rs-pt-4" style="border-top: 1px solid var(--rs-border, #e2e8f0); padding-top: 1rem;">
                            <h4 class="rs-font-bold rs-text-uppercase rs-text-xs rs-text-muted rs-mb-2" style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; margin-bottom: 0.5rem;">Connection Status</h4>
                            <div class="rs-d-flex rs-align-items-center rs-justify-content-between rs-flex-wrap rs-gap-3" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                                <?php if ($isFbAuth): ?>
                                    <div class="rs-d-flex rs-align-items-center" style="display: flex; align-items: center;">
                                        <span class="rs-badge rs-badge--success rs-badge--soft rs-d-flex rs-align-items-center rs-mr-3" style="margin-right: 0.75rem;"><span class="rs-mr-2" style="margin-right: 0.5rem;">●</span> Connected</span>
                                        <span class="rs-text-muted rs-text-xs" style="font-size: 0.75rem; color: #64748b;">Your account is successfully linked.</span>
                                    </div>
                                    <a href="<?= \App\Environment::url('/settings/facebook/revoke') ?>" class="rs-btn rs-btn--outline rs-btn--danger rs-btn--sm" onclick="return confirm('Are you sure you want to disconnect?');">Disconnect</a>
                                <?php else: ?>
                                    <div class="rs-d-flex rs-align-items-center" style="display: flex; align-items: center;">
                                        <span class="rs-badge rs-badge--danger rs-badge--soft rs-d-flex rs-align-items-center rs-mr-3" style="margin-right: 0.75rem;"><span class="rs-mr-2" style="margin-right: 0.5rem;">●</span> Not Connected</span>
                                        <span class="rs-text-muted rs-text-xs" style="font-size: 0.75rem; color: #64748b;">Awaiting authorization.</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>

        <?php
        // Tenant KPI Cards setup for Account Admin
        $connectedPages = $totalCount;
        $activeConnections = count(array_filter($configs, static fn($c) => $c->isLinked()));

        $kpiCards = [
            [
                'label' => 'Connected Pages',
                'value' => $connectedPages,
                'helper' => 'Pages In This Workspace',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>'
            ],
            [
                'label' => 'Active Connections',
                'value' => $activeConnections,
                'helper' => 'Currently Linked',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>'
            ],
            [
                'label' => 'Authorization Status',
                'value' => $isFbAuth ? 'Connected' : 'Pending',
                'helper' => $isFbAuth ? 'Ready to sync reviews' : 'Authorization required',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>'
            ],
        ];
        echo $this->render('../partials/_kpi_cards', ['cards' => $kpiCards]);
        ?>

        <!-- Company Facebook Authorization Card -->
        <div class="rs-section rs-mb-5">
            <div class="rs-card rs-card--shadow">
                <?= $this->render('../partials/_card_header', [
                    'title' => 'Facebook Authorization',
                    'subtitle' => 'Authorize Facebook Pages to sync reviews and reply to customer inquiries.'
                ]) ?>
                <div class="rs-card__body rs-p-4 md:rs-p-5">
                    <div class="rs-d-flex rs-justify-content-between rs-align-items-start md:rs-align-items-center rs-flex-column md:rs-flex-row rs-gap-4">
                        <div class="rs-flex-1">
                            <p class="rs-text-main rs-mb-3">To manage your Facebook Pages reviews and interactions on our platform, authorize them using your Facebook account.</p>
                            <p class="rs-text-sm rs-text-muted rs-mb-3">Our platform requests permission only to load reviews, conversations, and send your approved replies.</p>
                            <ul class="rs-text-sm rs-text-muted rs-pl-4 rs-m-0" style="list-style-type: disc;">
                                <li class="rs-mb-1">You can revoke permissions at any time here or in your Facebook Business Integrations settings.</li>
                                <li>No automated comments or unauthorized messages are ever posted.</li>
                            </ul>
                        </div>
                        <div class="rs-flex-shrink-0 rs-d-flex rs-flex-column rs-align-items-start md:rs-align-items-end rs-gap-2">
                            <a href="<?= \App\Environment::url('/settings/facebook/setup') ?>" class="rs-btn rs-btn--primary rs-d-inline-flex rs-align-items-center rs-gap-2" style="background-color: #1877F2; border-color: #1877F2;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="#ffffff">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                </svg>
                                <span><?= $isFbAuth ? 'Reconnect Facebook' : 'Continue with Facebook' ?></span>
                            </a>
                            <div class="rs-d-flex rs-align-items-center rs-gap-2">
                                <?php if ($isFbAuth): ?>
                                    <span class="rs-badge rs-badge--success rs-badge--soft">● Connected</span>
                                    <span class="rs-text-muted rs-font-size-xs">Your workspace is linked.</span>
                                <?php else: ?>
                                    <span class="rs-badge rs-badge--warning rs-badge--soft">● Not Connected</span>
                                    <span class="rs-text-muted rs-font-size-xs">Authorization required.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>

    <!-- Connected Pages Table Section -->
    <div class="rs-section">
        <?php if (empty($configs)): ?>
            <?= $this->render('../partials/_empty_state', [
                'message' => 'No Facebook Pages connected yet.',
                'actionLabel' => 'Connect Facebook Page',
                'actionUrl' => \App\Environment::url('/settings/facebook/setup')
            ]) ?>
        <?php else: ?>
            <div class="rs-card rs-card--shadow">
                <?= $this->render('../partials/_card_header', [
                    'title' => 'Connected Pages',
                    'subtitle' => $isPlatformAdmin ? 'Facebook Pages connected across all company accounts' : 'Facebook Pages currently managed in this workspace'
                ]) ?>
                <div class="rs-table-wrapper rs-p-0" style="overflow-x: auto;">
                    <table class="rs-table rs-table--hover rs-table--sm rs-w-100">
                        <thead>
                            <tr>
                                <?php if ($isPlatformAdmin): ?>
                                    <th style="width: 22%;">Company</th>
                                <?php endif; ?>
                                <th style="width: <?= $isPlatformAdmin ? '30%' : '45%' ?>;">Page Name</th>
                                <th style="width: 15%;">Status</th>
                                <th style="width: 18%;">Last Sync</th>
                                <th style="width: 15%; text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($configs as $cfg): ?>
                                <tr>
                                    <?php if ($isPlatformAdmin): ?>
                                        <td>
                                            <?= !empty($cfg->getAccountBusinessName()) ? Html::encode($cfg->getAccountBusinessName()) : '<span class="rs-text-muted">Unknown Company</span>' ?>
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <div style="font-weight: 600; color: var(--rs-text-main);"><?= Html::encode($cfg->getDisplayName()) ?></div>
                                        <?php if (!empty($cfg->getPageId())): ?>
                                            <div class="rs-font-size-xs rs-text-muted" style="margin-top: 2px;">
                                                ID: <?= Html::encode($cfg->getPageId()) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $this->render('../partials/_status_badge', [
                                            'status' => $cfg->isLinked() ? 'Linked' : 'Disconnected',
                                            'label' => $cfg->isLinked() ? 'Linked' : 'Disconnected',
                                        ]) ?>
                                    </td>
                                    <td class="rs-font-size-xs rs-text-muted">
                                        <?= $cfg->getUpdatedAt() ? date('Y-m-d H:i', $cfg->getUpdatedAt()) : ($cfg->getCreatedAt() ? date('Y-m-d H:i', $cfg->getCreatedAt()) : '-') ?>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <div class="rs-d-inline-flex rs-gap-2">
                                            <a href="<?= \App\Environment::url('/settings/facebook/view?id=' . $cfg->getId()) ?>" class="rs-btn rs-btn--sm rs-btn--secondary" title="View details">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                                <span>View</span>
                                            </a>
                                            <a href="<?= \App\Environment::url('/settings/facebook/revoke?id=' . $cfg->getId()) ?>" class="rs-btn rs-btn--sm rs-btn--danger" onclick="return confirm('Are you sure you want to disconnect this Facebook page?');" title="Disconnect">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path>
                                                    <line x1="12" y1="2" x2="12" y2="12"></line>
                                                </svg>
                                                <span>Disconnect</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="rs-p-4 rs-d-flex rs-flex-column md:rs-flex-row rs-align-items-center rs-justify-content-between rs-gap-3" style="border-top: 1px solid var(--rs-border);">
                    <div class="rs-font-size-sm rs-text-muted">
                        Showing <?= count($configs) ?> of <?= $totalCount ?> Facebook pages
                    </div>
                    <?php if ($pageCount > 1): ?>
                        <div class="rs-d-flex rs-gap-1">
                            <?php for ($p = 1; $p <= $pageCount; $p++): ?>
                                <a href="<?= \App\Environment::url('/settings/facebook?page=' . $p) ?>" class="rs-btn rs-btn--sm <?= $p === $page ? 'rs-btn--primary' : 'rs-btn--secondary' ?>" style="min-width: 32px; text-align: center;">
                                    <?= $p ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>