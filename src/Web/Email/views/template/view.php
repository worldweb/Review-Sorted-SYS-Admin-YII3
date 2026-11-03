<?php

declare(strict_types=1);

use App\Domain\Email\EmailTemplate;
use Yiisoft\Html\Html;

/**
 * @var EmailTemplate $template
 * @var \App\Domain\User\User $currentUser
 * @var \App\Security\TenantContextInterface $context
 */

$this->setTitle('Template: ' . $template->getName());
$activeTab = 'templates';
?>

<div class="rs-page">
    <!-- Sub-navigation tabs -->
    <?php include __DIR__ . '/../_nav.php'; ?>

    <!-- Page Header -->
    <div class="rs-page-header rs-d-flex rs-align-items-center rs-justify-content-between rs-mb-6">
        <div>
            <h1 class="rs-page-title rs-m-0"><?= Html::encode($template->getName()) ?></h1>
            <p class="rs-text-muted rs-m-0">View template metadata and rendered HTML preview.</p>
        </div>
        <div class="rs-d-flex rs-gap-2">
            <a href="/sys-yii3/email/template/update?id=<?= $template->getId() ?>" class="rs-btn rs-btn--primary">
                <i class="fa-solid fa-pen-to-square rs-mr-1"></i> Edit Template
            </a>
            <?php if (!$template->isProtected()): ?>
                <form method="post" action="/sys-yii3/email/template/delete" onsubmit="return confirm('Are you sure you want to delete this template?');" style="display:inline-block; margin:0;">
                    <input type="hidden" name="id" value="<?= $template->getId() ?>">
                    <button type="submit" class="rs-btn rs-btn--outline rs-text-danger">
                        <i class="fa-solid fa-trash rs-mr-1"></i> Delete
                    </button>
                </form>
            <?php endif; ?>
            <a href="/sys-yii3/email/templates" class="rs-btn rs-btn--ghost">
                <i class="fa-solid fa-arrow-left rs-mr-1"></i> Back to Templates
            </a>
        </div>
    </div>

    <!-- Metadata Card -->
    <div class="rs-card rs-mb-6">
        <div class="rs-card__header">
            <h3 class="rs-m-0" style="font-size: 16px;">Template Information</h3>
        </div>
        <div class="rs-card__body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--rs-space-4);">
                <div>
                    <span class="rs-text-muted" style="font-size: 12px; display: block;">Template Name</span>
                    <strong class="rs-font-semibold"><?= Html::encode($template->getName()) ?></strong>
                </div>
                <div>
                    <span class="rs-text-muted" style="font-size: 12px; display: block;">Slug</span>
                    <code><?= Html::encode($template->getSlug()) ?></code>
                </div>
                <div>
                    <span class="rs-text-muted" style="font-size: 12px; display: block;">Status</span>
                    <span class="rs-badge <?= $template->isActive() ? 'rs-badge--success' : 'rs-badge--danger' ?>">
                        <?= $template->isActive() ? 'Active' : 'Disabled' ?>
                    </span>
                </div>
                <div>
                    <span class="rs-text-muted" style="font-size: 12px; display: block;">System Protected</span>
                    <span><?= $template->isProtected() ? 'Yes (Protected System Template)' : 'No (Custom)' ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Card -->
    <div class="rs-card">
        <div class="rs-card__header rs-d-flex rs-align-items-center rs-justify-content-between">
            <h3 class="rs-m-0" style="font-size: 16px;">HTML Preview</h3>
            <span class="rs-text-muted" style="font-size: 12px;">Rendered with standard email styling</span>
        </div>
        <div class="rs-card__body" style="background-color: var(--rs-bg-app, #F8FAFC); min-height: 250px; padding: 24px;">
            <div style="background: white; border: 1px solid var(--rs-border, #E2E8F0); padding: 32px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); color: #333; line-height: 1.6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                <?= $template->getText() ?>
            </div>
        </div>
    </div>
</div>
