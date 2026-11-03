<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var App\Domain\User\User $currentUser
 * @var array<int, App\Domain\SocialConnection\ServiceConfig> $fbConfigs
 * @var array<int, App\Domain\SocialConnection\ServiceConfig> $googleConfigs
 * @var array<int, App\Domain\LocationGroup\LocationGroup> $groups
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 */

$this->setTitle('Create Social Post');

$totalFb = count($fbConfigs);
$totalGoogle = count($googleConfigs);
$totalDestinations = $totalFb + $totalGoogle;
?>

<div class="rs-social-post-create">
    <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mb-5">
        <h1 class="rs-h1 rs-m-0" style="font-size: 1.75rem; font-weight: 700; color: #0f172a; margin: 0;">Create Social Post</h1>
        <div class="rs-d-flex rs-gap-2">
            <a href="/sys-yii3/social-post" class="rs-btn rs-btn--secondary">
                Back to Publishing History
            </a>
        </div>
    </div>

    <?php if ($flash->has('error')): ?>
        <div class="rs-alert rs-alert--danger rs-mb-4" role="alert">
            <?= Html::encode((string) $flash->get('error')) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="/sys-yii3/social-post/create" id="social-post-create-form" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">

        <!-- Card 1: Post Content & Media -->
        <div class="rs-card rs-mb-5">
            <div class="rs-card__header">
                <h3 class="rs-card__title">Post Content &amp; Media</h3>
            </div>
            <div class="rs-card__body rs-p-6">
                <div class="rs-form-group rs-mb-4">
                    <label for="post-content-textarea" class="rs-label rs-font-semibold rs-mb-2">
                        Post Message <span class="rs-text-danger">*</span>
                    </label>
                    <textarea id="post-content-textarea" name="content" class="rs-input rs-textarea-input rs-w-100" rows="5" placeholder="Write your social post content here..." required></textarea>

                    <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mt-2">
                        <span id="char-counter-display" class="rs-text-sm rs-text-muted"><span id="char-count" style="font-weight: 600;">0</span> / 63,206 characters</span>
                        <span class="rs-text-sm rs-text-muted">Supports hashtags &amp; emojis</span>
                    </div>
                </div>

                <div class="rs-form-group rs-mb-0">
                    <label for="social-media-file-input" class="rs-label rs-font-semibold rs-mb-2">Image or Video Attachment</label>
                    <input type="file" name="media" id="social-media-file-input" class="rs-input rs-w-100" accept="image/*,video/*">
                    <div class="rs-text-muted rs-mt-1" style="font-size: 0.8rem;">
                        Supported formats: JPG, PNG, MP4, MOV (Max 50MB). Instagram requires media.
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Target Locations & Location Groups -->
        <div class="rs-card rs-mb-5" id="target-locations-card">
            <div class="rs-card__header rs-d-flex rs-justify-content-between rs-align-items-center">
                <h3 class="rs-card__title">Target Locations &amp; Groups</h3>
                <div class="rs-d-flex rs-gap-2">
                    <button type="button" class="rs-btn rs-btn--sm rs-btn--secondary" onclick="toggleAllDestinations(true)">Select All</button>
                    <button type="button" class="rs-btn rs-btn--sm rs-btn--secondary" onclick="toggleAllDestinations(false)">Deselect All</button>
                </div>
            </div>
            <div class="rs-card__body rs-p-6">
                <!-- Location Groups Chips -->
                <?php if (!empty($groups)): ?>
                    <div class="rs-mb-5">
                        <label class="rs-label rs-font-semibold rs-mb-2">Location Groups</label>
                        <div class="rs-d-flex rs-flex-wrap rs-gap-2">
                            <?php foreach ($groups as $grp): ?>
                                <label class="rs-badge rs-badge--default" style="cursor: pointer; padding: 6px 12px; font-size: 0.85rem; user-select: none;">
                                    <input type="checkbox" name="groups[]" value="<?= $grp->getId() ?>" style="margin-right: 6px;">
                                    <?= Html::encode($grp->getName()) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Individual Target Destinations -->
                <div>
                    <label class="rs-label rs-font-semibold rs-mb-3">
                        Individual Channels &amp; Destinations (<?= $totalDestinations ?> Available)
                    </label>

                    <div style="max-height: 320px; overflow-y: auto; border: 1px solid var(--rs-border, #e2e8f0); border-radius: 6px; padding: 12px; background: #f8fafc;">
                        <!-- Facebook Pages -->
                        <?php if (!empty($fbConfigs)): ?>
                            <div class="rs-text-uppercase rs-font-bold rs-mb-2" style="font-size: 0.75rem; color: #1877F2; letter-spacing: 0.5px;">Facebook Pages &amp; Instagram</div>
                            <?php foreach ($fbConfigs as $fb): ?>
                                <label class="rs-d-flex rs-align-items-center rs-gap-2 rs-p-2 rs-mb-2" style="background: #fff; border: 1px solid var(--rs-border, #e2e8f0); border-radius: 6px; cursor: pointer;">
                                    <input type="checkbox" name="destinations[]" class="dest-checkbox" value="facebook:<?= $fb->getId() ?>">
                                    <span class="rs-font-semibold rs-text-main" style="font-size: 0.875rem;"><?= Html::encode($fb->getDisplayName()) ?></span>
                                    <span class="rs-badge rs-badge--default rs-ml-auto" style="font-size: 0.75rem;">Facebook</span>
                                </label>
                                <?php foreach ($fb->getSubServices() as $sub): ?>
                                    <label class="rs-d-flex rs-align-items-center rs-gap-2 rs-p-2 rs-mb-2 rs-ml-4" style="background: #fdf2f8; border: 1px solid #fbcfe8; border-radius: 6px; cursor: pointer;">
                                        <input type="checkbox" name="destinations[]" class="dest-checkbox" value="instagram:<?= $sub->getId() ?>">
                                        <span class="rs-font-semibold" style="font-size: 0.875rem; color: #be185d;">📷 <?= Html::encode($sub->getName()) ?></span>
                                        <span class="rs-badge rs-badge--default rs-ml-auto" style="font-size: 0.75rem; color: #be185d;">Instagram</span>
                                    </label>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Google Locations -->
                        <?php if (!empty($googleConfigs)): ?>
                            <div class="rs-text-uppercase rs-font-bold rs-mt-3 rs-mb-2" style="font-size: 0.75rem; color: #ea4335; letter-spacing: 0.5px;">Google Business Profiles</div>
                            <?php foreach ($googleConfigs as $gc): ?>
                                <label class="rs-d-flex rs-align-items-center rs-gap-2 rs-p-2 rs-mb-2" style="background: #fff; border: 1px solid var(--rs-border, #e2e8f0); border-radius: 6px; cursor: pointer;">
                                    <input type="checkbox" name="destinations[]" class="dest-checkbox" value="google:<?= $gc->getId() ?>">
                                    <span class="rs-font-semibold rs-text-main" style="font-size: 0.875rem;"><?= Html::encode($gc->getDisplayName()) ?></span>
                                    <span class="rs-badge rs-badge--default rs-ml-auto" style="font-size: 0.75rem;">Google</span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (empty($fbConfigs) && empty($googleConfigs)): ?>
                            <div class="rs-text-muted rs-text-center rs-p-4">
                                No social channels connected yet. Please connect Facebook Pages or Google Business Profiles first.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Publishing Schedule & Actions -->
        <div class="rs-card rs-mb-6">
            <div class="rs-card__header">
                <h3 class="rs-card__title">Publishing Schedule &amp; Actions</h3>
            </div>
            <div class="rs-card__body rs-p-6">
                <div class="rs-d-flex rs-gap-6 rs-mb-4">
                    <label class="rs-d-flex rs-align-items-center rs-gap-2 rs-font-semibold" style="cursor: pointer;">
                        <input type="radio" name="post_type" value="immediate" checked onchange="toggleSchedule(false)">
                        <span>⚡ Publish Immediately</span>
                    </label>
                    <label class="rs-d-flex rs-align-items-center rs-gap-2 rs-font-semibold" style="cursor: pointer;">
                        <input type="radio" name="post_type" value="scheduled" onchange="toggleSchedule(true)">
                        <span>⏰ Schedule for Later</span>
                    </label>
                </div>

                <div id="schedule-box" style="display: none; background: #f8fafc; border: 1px solid var(--rs-border, #e2e8f0); border-radius: 6px; padding: 16px; margin-top: 16px;">
                    <label class="rs-label rs-font-semibold rs-mb-2" for="scheduled_at">Target Delivery Date &amp; Time</label>
                    <input type="datetime-local" id="scheduled_at" name="scheduled_at" class="rs-input" value="<?= date('Y-m-d\TH:i', strtotime('+1 hour')) ?>" style="max-width: 320px;">
                    <div class="rs-text-muted rs-mt-1" style="font-size: 0.8rem;">The background scheduler will automatically dispatch this post at the configured time.</div>
                </div>

                <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mt-6 rs-pt-4 rs-border-top" style="border-top: 1px solid var(--rs-border, #E2E8F0);">
                    <a href="/sys-yii3/social-post" class="rs-btn rs-btn--secondary">Cancel</a>
                    <button type="submit" class="rs-btn rs-btn--primary rs-px-6">Submit Post &rarr;</button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var textarea = document.getElementById('post-content-textarea');
    var charCount = document.getElementById('char-count');

    textarea.addEventListener('input', function() {
        charCount.innerText = textarea.value.length;
    });
});

function toggleSchedule(show) {
    document.getElementById('schedule-box').style.display = show ? 'block' : 'none';
}

function toggleAllDestinations(checked) {
    var cbs = document.querySelectorAll('.dest-checkbox');
    cbs.forEach(function(cb) {
        cb.checked = checked;
    });
}
</script>
