<?php

declare(strict_types=1);

/**
 * @var string $section
 * @var array<string, ?string> $settings
 * @var App\Domain\User\User $currentUser
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 */

$this->setTitle('Administrator Global Settings');

$sections = [
    'general' => ['label' => 'General & Platform', 'icon' => 'bi-sliders'],
    'security' => ['label' => 'Security & Auth', 'icon' => 'bi-shield-lock'],
    'reviews' => ['label' => 'Reviews & Feedback', 'icon' => 'bi-star'],
    'media' => ['label' => 'Media & Storage', 'icon' => 'bi-image'],
    'subscriptions' => ['label' => 'Stripe & Billing', 'icon' => 'bi-credit-card'],
    'roles' => ['label' => 'Role Permissions', 'icon' => 'bi-people'],
    'queue' => ['label' => 'Queues & Daemons', 'icon' => 'bi-cpu'],
];
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="h3 font-weight-bold text-gray-800 mb-1">
                        <i class="bi bi-gear-wide-connected text-primary me-2"></i>Global Platform Settings
                    </h2>
                    <p class="text-muted mb-0">Platform-wide system configuration, security policies, API secrets, and queue settings.</p>
                </div>
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
            <?php if ($flash->has('info')): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars((string) $flash->get('info')) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3 col-md-4 mb-4">
            <div class="list-group shadow-sm">
                <?php foreach ($sections as $sKey => $sInfo): ?>
                    <a href="/sys-yii3/global-settings?section=<?= $sKey ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between <?= $section === $sKey ? 'active bg-primary text-white' : '' ?>">
                        <span><i class="bi <?= $sInfo['icon'] ?> me-2"></i> <?= htmlspecialchars($sInfo['label']) ?></span>
                        <i class="bi bi-chevron-right small opacity-75"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Settings Content Card -->
        <div class="col-lg-9 col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="m-0 font-weight-bold text-primary"><?= htmlspecialchars($sections[$section]['label'] ?? 'Settings') ?></h6>
                </div>
                <div class="card-body">
                    <form method="post" action="/sys-yii3/global-settings?section=<?= urlencode($section) ?>">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">

                        <?= $this->render('_' . $section, [
                            'settings' => $settings,
                        ]) ?>

                        <div class="pt-3 border-top d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-lg me-1"></i> Save <?= htmlspecialchars($sections[$section]['label'] ?? 'Settings') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
