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
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="#1877F2" class="rs-mr-2" style="margin-right: 0.5rem;"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
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
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="#ffffff"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
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

        <!-- Company Facebook Authorization Card -->
        <div class="rs-section rs-mb-5">
            <div class="rs-card rs-card--shadow rs-p-5" style="background:#fff; border-radius:8px; border:1px solid #e2e8f0; padding: 1.5rem; margin-bottom: 1.5rem;">
                <div class="rs-d-flex rs-justify-content-between rs-align-items-start md:rs-align-items-center rs-flex-column md:rs-flex-row" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1.5rem;">
                    <div class="rs-mb-4 md:rs-mb-0 md:rs-mr-6" style="flex: 1 1 500px;">
                        <div class="rs-d-flex rs-align-items-center rs-mb-3" style="display: flex; align-items: center; margin-bottom: 0.75rem;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="#1877F2" class="rs-mr-2" style="margin-right: 0.5rem;"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            <h3 class="rs-card__title rs-m-0" style="font-size: 1.25rem; font-weight: 700; margin: 0;">Facebook Authorization</h3>
                        </div>
                        
                        <div class="rs-mb-4">
                            <p class="rs-text-main rs-mb-3" style="color: #334155; margin-bottom: 0.75rem;">To manage your Facebook Pages reviews and chats in our platform, you need first to authorize them by clicking on the button below.</p>
                            
                            <p class="rs-text-sm rs-text-muted rs-mb-3" style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.75rem;">It will request permission to manage pages and chats for the pages you choose. These permissions are only used by our platform to load the page's chat conversations and reviews, and send the chat messages and review replies you input here.</p>
                            
                            <ul class="rs-text-sm rs-text-muted rs-pl-4 rs-m-0" style="font-size: 0.875rem; color: #64748b; padding-left: 1.25rem; margin: 0; list-style-type: disc;">
                                <li class="rs-mb-1" style="margin-bottom: 0.25rem;">You can revoke those permissions any time you want here or on Facebook (Settings &rarr; Business Integrations).</li>
                                <li class="rs-mb-1">No automatic messages, comments, posts, or replies will ever be sent.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="rs-flex-shrink-0 rs-d-flex rs-flex-column rs-align-items-end" style="flex-shrink: 0; display: flex; flex-direction: column; align-items: flex-end;">
                        <a href="<?= \App\Environment::url('/settings/facebook/setup') ?>" class="rs-btn rs-btn--primary rs-mb-2" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.65rem 1.25rem; font-weight: 600; text-decoration: none; border-radius: 6px; margin-bottom: 0.5rem;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="#ffffff"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            Continue with Facebook
                        </a>
                        <div class="rs-d-flex rs-align-items-center rs-mt-1" style="display: flex; align-items: center; margin-top: 0.25rem;">
                            <?php if ($isFbAuth): ?>
                                <span class="rs-badge rs-badge--success rs-badge--soft rs-d-flex rs-align-items-center rs-mr-2" style="margin-right: 0.5rem;"><span class="rs-mr-2" style="margin-right: 0.25rem;">●</span> Connected</span>
                                <span class="rs-text-muted rs-text-xs" style="font-size: 0.75rem; color: #64748b;">Your account is successfully linked.</span>
                            <?php else: ?>
                                <span class="rs-badge rs-badge--danger rs-badge--soft rs-d-flex rs-align-items-center rs-mr-2" style="margin-right: 0.5rem;"><span class="rs-mr-2" style="margin-right: 0.25rem;">●</span> Not Connected</span>
                                <span class="rs-text-muted rs-text-xs" style="font-size: 0.75rem; color: #64748b;">Awaiting authorization.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>

    <!-- Connected Pages Table Section -->
    <div class="rs-section">
        <h2 class="rs-page-title rs-mb-4" style="font-size: 1.5rem; font-weight: 700; color: #0f172a; margin-bottom: 1rem;">Connected Pages</h2>
        <?php if (empty($configs)): ?>
            <?= $this->render('../partials/_empty_state', [
                'message' => 'No Facebook Pages connected yet.',
                'actionLabel' => null,
                'actionUrl' => null
            ]) ?>
        <?php else: ?>
            <div class="rs-card rs-card--shadow" style="background:#fff; border-radius:8px; border:1px solid #e2e8f0; overflow:hidden;">
                <div class="rs-table-wrapper rs-p-0" style="overflow-x: auto;">
                    <table class="rs-table rs-table--hover rs-table--sm rs-w-100" style="width: 100%; min-width: 780px; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                <?php if ($isPlatformAdmin): ?>
                                    <th style="padding: 12px 16px; width: 22%; min-width: 150px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Company</th>
                                <?php endif; ?>
                                <th style="padding: 12px 16px; width: <?= $isPlatformAdmin ? '30%' : '45%' ?>; min-width: 180px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Page Name</th>
                                <th style="padding: 12px 16px; width: 15%; min-width: 100px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Status</th>
                                <th style="padding: 12px 16px; width: 18%; min-width: 120px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Last Sync</th>
                                <th style="padding: 12px 16px; width: 15%; min-width: 160px; text-align: right; font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($configs as $cfg): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <?php if ($isPlatformAdmin): ?>
                                        <td style="padding: 14px 16px;">
                                            <?= !empty($cfg->getAccountBusinessName()) ? Html::encode($cfg->getAccountBusinessName()) : '<span class="rs-text-muted">Unknown Company</span>' ?>
                                        </td>
                                    <?php endif; ?>
                                    <td style="padding: 14px 16px;">
                                        <div style="font-weight: 600; color: #0f172a; font-size: 0.95rem;"><?= Html::encode($cfg->getDisplayName()) ?></div>
                                        <?php if (!empty($cfg->getPageId())): ?>
                                            <div class="rs-text-xs rs-text-muted" style="font-size: 0.75rem; color: #64748b; margin-top: 2px;">
                                                <?= Html::encode($cfg->getPageId()) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <?= $this->render('../partials/_status_badge', [
                                            'status' => $cfg->isLinked() ? 'Linked' : 'Disconnected',
                                            'label' => $cfg->isLinked() ? 'Linked' : 'Disconnected',
                                        ]) ?>
                                    </td>
                                    <td style="padding: 14px 16px; font-size: 0.85rem; color: #64748b;" class="rs-text-muted">
                                        <?= $cfg->getUpdatedAt() ? date('Y-m-d H:i', $cfg->getUpdatedAt()) : ($cfg->getCreatedAt() ? date('Y-m-d H:i', $cfg->getCreatedAt()) : '-') ?>
                                    </td>
                                    <td style="padding: 14px 16px; text-align: right; white-space: nowrap;">
                                        <div style="display: inline-flex; gap: 8px; justify-content: flex-end;">
                                            <a href="<?= \App\Environment::url('/settings/facebook/view?id=' . $cfg->getId()) ?>" class="rs-btn rs-btn--sm rs-btn--outline rs-d-inline-flex rs-align-items-center" style="display: inline-flex; align-items: center; gap: 4px;">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                View
                                            </a>
                                            <a href="<?= \App\Environment::url('/settings/facebook/revoke?id=' . $cfg->getId()) ?>" class="rs-btn rs-btn--sm rs-btn--danger rs-d-inline-flex rs-align-items-center" onclick="return confirm('Are you sure you want to revoke access?');" style="display: inline-flex; align-items: center; gap: 4px;">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg>
                                                Disconnect
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="rs-p-4 rs-d-flex rs-flex-column md:rs-flex-row rs-align-items-center rs-justify-content-between rs-gap-3" style="padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e2e8f0; flex-wrap: wrap; gap: 0.75rem;">
                    <div style="font-size: 0.875rem; color: #64748b;">
                        Showing <?= count($configs) ?> of <?= $totalCount ?> Facebook pages
                    </div>
                    <?php if ($pageCount > 1): ?>
                        <div style="display: flex; gap: 4px;">
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
