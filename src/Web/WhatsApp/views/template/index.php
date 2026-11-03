<?php

declare(strict_types=1);

use App\Domain\WhatsApp\WhatsAppTemplate;
use Yiisoft\Html\Html;

/**
 * @var array<int, WhatsAppTemplate> $templates
 * @var string $search
 * @var int|null $accountId
 * @var \App\Domain\User\User $currentUser
 * @var \App\Security\TenantContextInterface $context
 * @var \Yiisoft\Session\Flash\Flash $flash
 */

$this->setTitle('WhatsApp Templates');
$activeTab = 'templates';
?>

<div class="rs-page">
    <!-- Sub-navigation tabs -->
    <?php include __DIR__ . '/../_nav.php'; ?>

    <!-- Flash notifications -->
    <?php foreach ($flash->getAll() as $type => $messages): ?>
        <?php foreach ($messages as $message): ?>
            <div class="rs-alert rs-alert--<?= $type === 'error' ? 'danger' : Html::encode($type) ?> rs-mb-4" role="alert">
                <?= Html::encode($message) ?>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>

    <!-- Page Header -->
    <div class="rs-page-header rs-d-flex rs-align-items-center rs-justify-content-between rs-mb-6">
        <div>
            <h1 class="rs-page-title rs-m-0">WhatsApp Templates</h1>
            <p class="rs-text-muted rs-m-0">Manage reusable plain-text templates for WhatsApp messages and review follow-ups.</p>
        </div>
        <div>
            <a href="/sys-yii3/whatsapp/template/create" class="rs-btn rs-btn--primary">
                <i class="fa-solid fa-plus rs-mr-1"></i> Create Template
            </a>
        </div>
    </div>

    <!-- Search Toolbar -->
    <div class="rs-card rs-mb-6">
        <div class="rs-card__body rs-p-4">
            <form method="get" action="/sys-yii3/whatsapp/templates" class="rs-d-flex rs-align-items-center rs-gap-3">
                <div class="rs-input-group rs-flex-grow-1" style="max-width: 400px;">
                    <input
                        type="text"
                        name="q"
                        value="<?= Html::encode($search) ?>"
                        class="rs-input"
                        placeholder="Search templates by name or slug..."
                    />
                </div>
                <button type="submit" class="rs-btn rs-btn--outline">
                    <i class="fa-solid fa-magnifying-glass rs-mr-1"></i> Filter
                </button>
                <?php if ($search !== ''): ?>
                    <a href="/sys-yii3/whatsapp/templates" class="rs-btn rs-btn--ghost rs-text-muted">Clear</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Templates Table Card -->
    <div class="rs-card">
        <div class="rs-table-wrapper rs-p-0">
            <table class="rs-table rs-table--hover">
                <thead>
                    <tr>
                        <th style="min-width: 250px;">Template Name</th>
                        <th style="width: 180px;">System Slug</th>
                        <th style="width: 140px;">Type</th>
                        <th style="width: 120px;">Status</th>
                        <th style="width: 160px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($templates)): ?>
                        <tr>
                            <td colspan="5" class="rs-text-center rs-p-8 rs-text-muted">
                                <i class="fa-regular fa-comment-dots rs-mb-2" style="font-size: 2rem; display: block; opacity: 0.4;"></i>
                                No WhatsApp templates found. Click <strong>Create Template</strong> to build your first message template.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($templates as $tpl): ?>
                            <tr>
                                <td>
                                    <a href="/sys-yii3/whatsapp/template/view?id=<?= $tpl->getId() ?>" class="rs-font-semibold rs-text-main" style="text-decoration: none;">
                                        <?= Html::encode($tpl->getName()) ?>
                                    </a>
                                </td>
                                <td>
                                    <code class="rs-text-muted" style="font-size: 12px;"><?= Html::encode($tpl->getSlug()) ?></code>
                                </td>
                                <td>
                                    <?php if ($tpl->isProtected()): ?>
                                        <span class="rs-badge rs-badge--info">
                                            <i class="fa-solid fa-shield-halved rs-mr-1"></i> System Protected
                                        </span>
                                    <?php else: ?>
                                        <span class="rs-badge rs-badge--ghost">Custom</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="rs-badge <?= $tpl->isActive() ? 'rs-badge--success' : 'rs-badge--danger' ?>">
                                        <?= $tpl->isActive() ? 'Active' : 'Disabled' ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div class="rs-d-inline-flex rs-align-items-center rs-gap-1">
                                        <a href="/sys-yii3/whatsapp/template/view?id=<?= $tpl->getId() ?>" class="rs-btn rs-btn--sm rs-btn--ghost" title="View details">
                                            View
                                        </a>
                                        <a href="/sys-yii3/whatsapp/template/update?id=<?= $tpl->getId() ?>" class="rs-btn rs-btn--sm rs-btn--outline" title="Edit template">
                                            Edit
                                        </a>
                                        <?php if (!$tpl->isProtected()): ?>
                                            <form method="post" action="/sys-yii3/whatsapp/template/delete" onsubmit="return confirm('Are you sure you want to delete this custom template?');" style="display: inline-block; margin: 0;">
                                                <input type="hidden" name="id" value="<?= $tpl->getId() ?>">
                                                <button type="submit" class="rs-btn rs-btn--sm rs-btn--ghost rs-text-danger" title="Delete template">
                                                    Delete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
