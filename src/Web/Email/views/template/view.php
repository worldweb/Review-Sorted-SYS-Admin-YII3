<?php

declare(strict_types=1);

use App\Domain\Email\EmailTemplate;
use Yiisoft\Html\Html;

/**
 * @var EmailTemplate $template
 * @var \App\Domain\User\User $currentUser
 * @var \App\Security\TenantContextInterface $context
 * @var \Yiisoft\View\WebView $this
 */

$this->setTitle($template->getName());
?>

<div class="rs-page">
    <!-- Page Header matching Yii 2 Modern Theme -->
    <div class="rs-page-header rs-d-flex rs-align-items-center rs-justify-content-between rs-mb-6">
        <div>
            <h1 class="rs-page-title rs-m-0"><?= Html::encode($template->getName()) ?></h1>
            <p class="rs-text-muted rs-m-0">View template metadata and preview content.</p>
        </div>
        <div class="rs-d-flex rs-gap-2">
            <a href="<?= \App\Environment::url('/email/template/update?id=' . $template->getId()) ?>" class="rs-btn rs-btn--primary">
                Edit Template
            </a>
            <?php if (!$template->isProtected()): ?>
                <form method="post" action="<?= \App\Environment::url('/email/template/delete') ?>" onsubmit="return confirm('Are you sure you want to delete this template?');" style="display: inline-block; margin: 0;">
                    <input type="hidden" name="id" value="<?= $template->getId() ?>">
                    <button type="submit" class="rs-btn rs-btn--outline" style="color: var(--rs-danger, #ef4444); border-color: var(--rs-border, #e2e8f0);">
                        Delete
                    </button>
                </form>
            <?php endif; ?>
            <a href="<?= \App\Environment::url('/email/templates') ?>" class="rs-btn rs-btn--ghost">
                Back
            </a>
        </div>
    </div>

    <!-- Template Information Card -->
    <div class="rs-card rs-mb-6">
        <div class="rs-card__header">
            <h3 class="rs-m-0" style="font-size: 16px;">Template Information</h3>
        </div>
        <div class="rs-card__body">
            <div class="mod-meta-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--rs-space-4, 16px);">
                <div class="mod-meta-item">
                    <span class="mod-meta-label rs-text-muted" style="font-size: 12px; display: block; margin-bottom: 4px;">Template Name</span>
                    <span class="mod-meta-value rs-font-semibold"><?= Html::encode($template->getName()) ?></span>
                </div>
                <div class="mod-meta-item">
                    <span class="mod-meta-label rs-text-muted" style="font-size: 12px; display: block; margin-bottom: 4px;">Status</span>
                    <div>
                        <span class="rs-badge <?= $template->isActive() ? 'rs-badge--success' : 'rs-badge--danger' ?>">
                            <?= $template->isActive() ? 'Active' : 'Disabled' ?>
                        </span>
                    </div>
                </div>
                <div class="mod-meta-item">
                    <span class="mod-meta-label rs-text-muted" style="font-size: 12px; display: block; margin-bottom: 4px;">System Protected</span>
                    <span class="mod-meta-value"><?= $template->isProtected() ? 'Yes (Cannot be deleted)' : 'No' ?></span>
                </div>
                <div class="mod-meta-item">
                    <span class="mod-meta-label rs-text-muted" style="font-size: 12px; display: block; margin-bottom: 4px;">Created At</span>
                    <span class="mod-meta-value"><?= $template->getCreatedAt() ? date('M d, Y H:i', $template->getCreatedAt()) : 'N/A' ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Template Content Preview Card -->
    <div class="rs-card">
        <div class="rs-card__header">
            <h3 class="rs-m-0" style="font-size: 16px;">Template Content Preview</h3>
        </div>
        <div class="rs-card__body" style="background-color: var(--rs-bg-app, #F8FAFC); min-height: 250px; padding: 24px;">
            <div class="email-body-preview" style="background: white; border: 1px solid var(--rs-border, #E2E8F0); padding: 32px; border-radius: var(--rs-radius-md, 8px); box-shadow: var(--rs-shadow-sm, 0 1px 3px rgba(0,0,0,0.05)); color: #333; line-height: 1.6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                <?= $template->getText() ?>
            </div>
        </div>
    </div>
</div>
