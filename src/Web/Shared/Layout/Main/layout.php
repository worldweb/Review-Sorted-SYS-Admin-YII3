<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Main Authenticated Application Layout (Yii 3 Modern Theme)
 * Matches Reviews Sorted Modern Enterprise UI/UX design system.
 *
 * @var \App\Shared\ApplicationParams $applicationParams
 * @var Yiisoft\Aliases\Aliases $aliases
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var string $content
 * @var string|null $csrf
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var \App\Web\Shared\Navigation\NavigationMenuProvider $navigationMenuProvider
 * @var \App\Security\TenantContextProviderInterface $tenantContextProvider
 * @var \App\Domain\User\UserRepositoryInterface $userRepo
 * @var \App\Domain\User\User|null $currentUser
 * @var array<int, array{label: string, url?: string}|string>|null $breadcrumbs
 * @var array<string, string|array<string>>|null $alerts
 */

// 1. Resolve User and Tenant State
$user = $currentUser ?? null;
if ($user === null && isset($tenantContextProvider, $userRepo)) {
    $context = $tenantContextProvider->get();
    if ($context->isAuthenticated()) {
        $user = $userRepo->findById($context->getUserId(), $context);
    }
}

// 2. Resolve Environment URLs
$baseUrl = isset($aliases) ? (string) $aliases->get('@baseUrl') : '/';
$base = ($baseUrl !== '' && $baseUrl !== '/') ? rtrim($baseUrl, '/') : '';
$themeUrl = $base . '/themes/modern';

// 3. Resolve Navigation and Workspaces
$menuItems = [];
$switchableAccounts = [];
if ($user !== null && isset($navigationMenuProvider)) {
    $currentPath = $_SERVER['REQUEST_URI'] ?? ($base ?: '/');
    if (str_contains($currentPath, '?')) {
        $currentPath = explode('?', $currentPath, 2)[0];
    }
    $menuItems = $navigationMenuProvider->getMenuItems($user, $currentPath);
    $switchableAccounts = $navigationMenuProvider->getSwitchableAccounts($user->getId());
}

$pageTitle = $this->getTitle();
$fullTitle = !empty($pageTitle)
    ? $pageTitle . ' — ' . $applicationParams->name
    : $applicationParams->name;

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Html::encode($applicationParams->locale) ?>">
<head>
    <meta charset="<?= Html::encode($applicationParams->charset) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="<?= $base ?>/favicon.ico" type="image/x-icon">
    <?php if (!empty($csrf)): ?>
        <meta name="csrf-token" content="<?= Html::encode($csrf) ?>">
    <?php endif; ?>
    <title><?= Html::encode($fullTitle) ?></title>

    <!-- Enterprise Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;600;700;900&display=swap" rel="stylesheet">

    <!-- Modern Theme 31 Modular CSS Stylesheets in Exact Dependency Order -->
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/variables.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/base.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/typography.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/layout.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/header.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/sidebar.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/footer.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/navigation.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/breadcrumbs.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/buttons.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/forms.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/activeform.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/tables.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/gridview.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/cards.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/dropdowns.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/modals.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/alerts.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/badges.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/pagination.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/tabs.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/tooltips.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/loading.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/animations.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/empty-states.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/dashboard.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/components.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/utilities.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/responsive.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/legacy-overrides.css">
    <link rel="stylesheet" href="<?= $themeUrl ?>/css/theme.css">

    <!-- Vendor Dependencies: jQuery & Select2 (Head-loaded for synchronous view script availability) -->
    <script src="<?= $themeUrl ?>/js/vendor/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>

    <?php $this->head() ?>
</head>
<body class="rs-app-wrapper rs-layout-app">
<?php $this->beginBody() ?>

<?php if ($user === null): ?>
    <!-- Fallback Guest Layout View Container -->
    <div class="rs-auth-container rs-w-100">
        <?= $content ?>
    </div>
<?php else: ?>

    <!-- RS Enterprise Header -->
    <header class="rs-header">
        <div class="rs-d-flex rs-align-items-center rs-gap-3">
            <button type="button" class="rs-header__action-btn rs-header__action-btn--toggle-sidebar" aria-label="Toggle Sidebar">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            <a href="<?= $base ?>/reviews" class="rs-header__brand">
                <?= Html::encode($applicationParams->name) ?>
            </a>
        </div>

        <!-- Global Search / Command Palette Trigger -->
        <div class="rs-header__search rs-d-none rs-d-md-flex" tabindex="0" role="button" aria-label="Open Command Palette" data-toggle="modal" data-target="rs-command-palette-modal">
            <svg class="rs-header__search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <div class="rs-header__search-input rs-text-muted">Search accounts, users, or settings...</div>
            <div class="rs-header__search-shortcut">
                <span class="rs-header__search-key">⌘</span>
                <span class="rs-header__search-key">K</span>
            </div>
        </div>

        <!-- Header Actions -->
        <div class="rs-header__actions">
            <!-- Notifications Dropdown -->
            <div class="rs-dropdown" id="rs-notifications-dropdown"
                 data-url-latest="<?= $base ?>/notification/latest"
                 data-url-mark-read="<?= $base ?>/notification/mark-read"
                 data-url-mark-all-read="<?= $base ?>/notification/mark-all-read">
                <button type="button" class="rs-header__action-btn" id="rs-notification-bell-btn" aria-label="Notifications" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="position: relative;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span class="rs-badge rs-badge--danger rs-d-none" id="rs-notification-badge" style="position: absolute; top: 4px; right: 4px; font-size: 0.65rem; padding: 2px 5px; border-radius: 999px; line-height: 1; display: none;"></span>
                </button>
                <div class="rs-dropdown__menu" id="rs-notification-menu" role="menu" style="min-width: 320px; max-width: 380px; padding: 0;">
                    <div class="rs-p-3 rs-text-center rs-text-muted">
                        <p class="rs-m-0">No new notifications</p>
                    </div>
                </div>
            </div>

            <!-- Profile Dropdown -->
            <div class="rs-dropdown">
                <div class="rs-header__profile" tabindex="0" role="button" aria-label="User Menu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <div class="rs-header__avatar">
                        <?= Html::encode(strtoupper(substr($user->getName(), 0, 1))) ?>
                    </div>
                    <div class="rs-header__user-info rs-d-none rs-d-sm-flex">
                        <span class="rs-header__user-name"><?= Html::encode($user->getName()) ?></span>
                    </div>
                    <svg class="rs-ml-1" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </div>
                <div class="rs-dropdown__menu" role="menu">
                    <a href="<?= \App\Environment::url('/user/update?id=' . $user->getId()) ?>" class="rs-dropdown__item" role="menuitem">My Profile</a>
                    <?php if ($user->isAdmin()): ?>
                        <a href="<?= $base ?>/global-settings/index" class="rs-dropdown__item" role="menuitem">Global Settings</a>
                    <?php endif; ?>
                    <div class="rs-dropdown__divider"></div>
                    <a href="#" class="rs-dropdown__item rs-text-danger" role="menuitem" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
                </div>
            </div>

            <!-- Hidden Logout Form -->
            <form id="logout-form" action="<?= $base ?>/logout" method="post" class="rs-d-none">
                <?php if (!empty($csrf)): ?>
                    <input type="hidden" name="_csrf" value="<?= Html::encode($csrf) ?>">
                <?php endif; ?>
            </form>
        </div>
    </header>

    <div class="rs-layout-body">
        <!-- RS Sidebar -->
        <aside class="rs-sidebar rs-sidebar--expanded">
            <!-- Workspace Selector -->
            <?php if (!empty($switchableAccounts) && count($switchableAccounts) > 1): ?>
                <div class="rs-sidebar__workspace">
                    <form action="<?= $base ?>/user/switch" method="get">
                        <select name="id" onchange="this.form.submit()" class="rs-sidebar__workspace-selector" aria-label="Switch Workspace">
                            <?php foreach ($switchableAccounts as $accountId => $accountName): ?>
                                <option value="<?= $accountId ?>" <?= ($user->getAccountId() === $accountId) ? 'selected="selected"' : '' ?>>
                                    <?= Html::encode($accountName) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <noscript>
                            <button type="submit" class="rs-btn rs-btn--primary rs-mt-2">Switch Workspace</button>
                        </noscript>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Navigation Links -->
            <nav class="rs-sidebar__nav" aria-label="Main Navigation">
                <ul class="rs-nav-menu">
                    <?php foreach ($menuItems as $item): ?>
                        <?php $itemType = $item['type'] ?? 'item'; ?>
                        <?php if ($itemType === 'header'): ?>
                            <li class="rs-sidebar-group-header"><?= Html::encode($item['label']) ?></li>
                        <?php elseif ($itemType === 'divider'): ?>
                            <li class="rs-sidebar-divider-wrapper"><div class="rs-sidebar-divider"></div></li>
                        <?php elseif ($itemType === 'subitem'): ?>
                            <li class="rs-sidebar-subitem <?= !empty($item['active']) ? 'active' : '' ?>">
                                <a href="<?= Html::encode($item['url']) ?>">
                                    <span><?= Html::encode($item['label']) ?></span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="<?= !empty($item['active']) ? 'active' : '' ?>">
                                <a href="<?= Html::encode($item['url']) ?>" class="rs-sidebar__item <?= !empty($item['active']) ? 'rs-sidebar__item--active' : '' ?>">
                                    <span class="rs-sidebar__icon"><?= $item['icon'] ?? '' ?></span>
                                    <span class="rs-sidebar__item-label"><?= Html::encode($item['label']) ?></span>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </aside>

        <!-- Sidebar Mobile Overlay Backdrop -->
        <div class="rs-sidebar-overlay" aria-hidden="true"></div>

        <!-- RS Main Content Wrapper -->
        <main class="rs-layout-main">
            <!-- Breadcrumbs & Alerts Section -->
            <?php if (!empty($breadcrumbs) || !empty($alerts)): ?>
                <div class="rs-mb-4">
                    <?php
                    if (!empty($breadcrumbs)) {
                        include __DIR__ . '/../../Components/_breadcrumbs.php';
                    }
                    if (!empty($alerts)) {
                        include __DIR__ . '/../../Components/_alerts.php';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <!-- Page Content -->
            <div class="rs-legacy-view">
                <?= $content ?>
            </div>

            <!-- Footer -->
            <footer class="rs-footer">
                <span class="rs-footer__copyright">&copy; <?= Html::encode($applicationParams->name) ?> 2020 - 2026.</span>
                <span class="rs-footer__powered">Powered by <a href="https://www.eurisko.com.au" target="_blank" rel="noopener" class="rs-footer__link">Eurisko</a></span>
            </footer>
        </main>
    </div>

    <!-- Command Palette Modal Scaffold -->
    <div id="rs-command-palette-modal" class="rs-modal-backdrop" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Global Search" data-search-url="<?= $base ?>/site/search">
        <div class="rs-modal rs-command-palette">
            <div class="rs-command-palette__header">
                <svg class="rs-command-palette__icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" class="rs-command-palette__input" placeholder="Search accounts, users, settings, or reviews..." autocomplete="off" spellcheck="false" aria-label="Search query" aria-autocomplete="list">
                <button type="button" class="rs-btn rs-btn--sm rs-btn--ghost rs-command-palette__close-badge" data-dismiss="modal" aria-label="Close Global Search">ESC</button>
            </div>
            <div class="rs-command-palette__body rs-command-palette__body--empty">
                <div class="rs-command-palette__empty-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </div>
                <p class="rs-command-palette__status-title">Quick Search & Navigation</p>
                <p class="rs-command-palette__status-desc">Type to search accounts, users, settings, or reviews across the system.</p>
            </div>
            <div class="rs-command-palette__footer">
                <div class="rs-command-palette__footer-shortcuts">
                    <span class="rs-command-palette__shortcut-item"><kbd class="rs-header__search-key">↑</kbd><kbd class="rs-header__search-key">↓</kbd> Navigate</span>
                    <span class="rs-command-palette__shortcut-item"><kbd class="rs-header__search-key">↵</kbd> Select</span>
                </div>
                <span><kbd class="rs-header__search-key">ESC</kbd> Close</span>
            </div>
        </div>
    </div>

    <!-- Global PJAX / Action Loader Overlay -->
    <div id="rs-global-loader" class="rs-loader-overlay rs-loader-overlay--transparent rs-d-none">
        <div class="rs-spinner rs-spinner--lg"></div>
    </div>

<?php endif; ?>

<!-- Modern Theme Vanilla JS Controllers -->
<script src="<?= $themeUrl ?>/js/app.layout.js"></script>
<script src="<?= $themeUrl ?>/js/app.notifications.js"></script>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage(); ?>
