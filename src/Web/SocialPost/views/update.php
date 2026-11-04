<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var App\Domain\SocialPost\SocialPost $model
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 */

$this->setTitle('Edit Post #' . $model->getId());
?>

<div class="rs-social-post-update">
    <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mb-5">
        <div>
            <h1 class="rs-h1 rs-m-0" style="font-size: 1.75rem; font-weight: 700; color: #0f172a; margin: 0;">Edit Post #<?= $model->getId() ?></h1>
            <div class="rs-text-muted rs-mt-1">Modify content and scheduling options for this dispatch.</div>
        </div>
        <div class="rs-d-flex rs-gap-2">
            <a href="<?= \App\Environment::url('/social-post/view?id=' . $model->getId()) ?>" class="rs-btn rs-btn--secondary">
                Cancel
            </a>
        </div>
    </div>

    <?php if ($flash->has('error')): ?>
        <div class="rs-alert rs-alert--danger rs-mb-4" role="alert">
            <?= Html::encode((string) $flash->get('error')) ?>
        </div>
    <?php endif; ?>

    <div class="rs-card rs-form-card">
        <div class="rs-card__header">
            <h3 class="rs-card__title">Post Content &amp; Details</h3>
        </div>
        <div class="rs-card__body rs-p-6">
            <form method="post" action="<?= \App\Environment::url('/social-post/update?id=' . $model->getId()) ?>" class="rs-form">
                <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">

                <div class="rs-form-group rs-mb-5">
                    <label for="content" class="rs-label rs-font-semibold rs-mb-2">Post Content <span class="rs-text-danger">*</span></label>
                    <textarea class="rs-input rs-textarea-input rs-w-100" id="content" name="content" rows="6" required placeholder="Write your post content here..."><?= Html::encode($model->getContent()) ?></textarea>
                </div>

                <div class="rs-form-group rs-mb-5">
                    <label for="scheduled_at" class="rs-label rs-font-semibold rs-mb-2">Scheduled Datetime</label>
                    <input type="datetime-local" class="rs-input rs-w-100" id="scheduled_at" name="scheduled_at" value="<?= Html::encode(!empty($model->getScheduledAt()) ? date('Y-m-d\TH:i', strtotime($model->getScheduledAt())) : '') ?>">
                    <div class="rs-text-muted rs-mt-1" style="font-size: 0.8rem;">Leave blank to dispatch immediately.</div>
                </div>

                <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mt-6 rs-pt-4 rs-border-top" style="border-top: 1px solid var(--rs-border, #E2E8F0);">
                    <a href="<?= \App\Environment::url('/social-post/view?id=' . $model->getId()) ?>" class="rs-btn rs-btn--secondary">Cancel</a>
                    <button type="submit" class="rs-btn rs-btn--primary rs-px-6">Update Post</button>
                </div>
            </form>
        </div>
    </div>
</div>
