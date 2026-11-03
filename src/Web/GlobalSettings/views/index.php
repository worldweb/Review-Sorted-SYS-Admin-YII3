<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Global Platform Settings View (Yii 3 Modern Theme)
 *
 * @var string $section
 * @var array<string, ?string> $settings
 * @var App\Domain\User\User $currentUser
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 * @var Yiisoft\View\WebView $this
 */

$this->setTitle('Administrator Global Settings');

$sections = [
    'general' => ['label' => 'General & Platform', 'icon' => '🏷️'],
    'security' => ['label' => 'Security & Auth', 'icon' => '🔒'],
    'reviews' => ['label' => 'Reviews & Feedback', 'icon' => '⭐'],
    'media' => ['label' => 'Media & Storage', 'icon' => '📱'],
    'subscriptions' => ['label' => 'Stripe & Billing', 'icon' => '💳'],
    'roles' => ['label' => 'Role Permissions', 'icon' => '🛡️'],
    'queue' => ['label' => 'Queues & Daemons', 'icon' => '⚡'],
];

$title = 'Global Platform Settings';
$description = 'Configure platform-wide settings, security policies, API integrations, and queue parameters.';
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
    <?php if ($flash->has('info')): ?>
        <div class="rs-alert rs-alert--info rs-mb-4" role="alert">
            <?= Html::encode((string) $flash->get('info')) ?>
        </div>
    <?php endif; ?>

    <!-- Horizontal Modern Settings Navigation Tabs -->
    <div class="rs-settings-nav-tabs rs-d-flex rs-flex-wrap rs-gap-2 rs-mb-6 rs-border-bottom" style="border-bottom: 2px solid var(--rs-border, #E2E8F0); padding-bottom: 4px;">
        <?php foreach ($sections as $sKey => $sInfo): ?>
            <?php $isActive = $section === $sKey; ?>
            <a
                href="/sys-yii3/global-settings?section=<?= urlencode($sKey) ?>"
                class="rs-btn <?= $isActive ? 'rs-btn--primary' : 'rs-btn--ghost rs-text-muted' ?>"
                style="display: inline-flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; text-decoration: none; border-radius: 6px; padding: 8px 16px;"
            >
                <span><?= $sInfo['icon'] ?></span>
                <span><?= Html::encode($sInfo['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Active Settings Tab Card -->
    <div class="rs-card" style="max-width: 900px;">
        <div class="rs-card__header" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #E2E8F0);">
            <h3 class="rs-card__title" style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--rs-text-main, #0F172A);">
                <?= Html::encode($sections[$section]['label'] ?? 'Configuration') ?>
            </h3>
        </div>
        <div class="rs-card__body rs-p-6" style="padding: 24px;">
            <form method="post" action="/sys-yii3/global-settings?section=<?= urlencode($section) ?>" class="rs-form">
                <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">

                <?= $this->render('_' . $section, [
                    'settings' => $settings,
                ]) ?>

                <div class="rs-d-flex rs-justify-content-end rs-gap-3 rs-pt-6 rs-mt-6 rs-border-top" style="border-top: 1px solid var(--rs-border, #E2E8F0);">
                    <button type="submit" class="rs-btn rs-btn--primary">
                        Save <?= Html::encode($sections[$section]['label'] ?? 'Settings') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
