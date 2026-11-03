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
    <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mb-5" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h1 class="rs-h1 rs-m-0" style="font-size: 1.75rem; font-weight: 700; color: #0f172a; margin: 0;">Create Social Post</h1>
        <div style="display: flex; gap: 0.5rem;">
            <a href="/sys-yii3/social-post" class="rs-btn rs-btn--secondary">
                &larr; Back to Publishing History
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

        <div style="display: grid; grid-template-columns: 1fr 420px; gap: 1.5rem; align-items: start;">
            <!-- Left Column: Composer Controls -->
            <div>
                <!-- Card 1: Post Content & Media -->
                <div class="rs-card rs-mb-4" style="background:#fff; border-radius:8px; border:1px solid #e2e8f0; margin-bottom: 1.5rem;">
                    <div class="rs-card__header" style="padding: 1rem 1.5rem; border-bottom: 1px solid #e2e8f0;">
                        <h3 class="rs-card__title" style="font-size: 1.125rem; font-weight: 600; margin: 0; color: #0f172a;">Post Content &amp; Media</h3>
                    </div>
                    <div class="rs-card__body" style="padding: 1.5rem;">
                        <div class="rs-form-group rs-mb-4" style="margin-bottom: 1.25rem;">
                            <label for="post-content-textarea" class="rs-label" style="display:block; font-weight:600; font-size:0.875rem; margin-bottom:0.35rem; color:#1e293b;">
                                Post Message <span style="color:#ef4444;">*</span>
                            </label>
                            <textarea id="post-content-textarea" name="content" class="rs-textarea" rows="5" placeholder="Write your social post content here..." required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; font-family: inherit; line-height: 1.5;"></textarea>

                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem;">
                                <span id="char-counter-display" style="font-size: 0.8rem; font-weight: 600; color: #64748b;"><span id="char-count">0</span> / 63,206 characters</span>
                                <span style="font-size: 0.75rem; color: #94a3b8;">Supports hashtags &amp; emojis</span>
                            </div>
                        </div>

                        <div class="rs-form-group rs-mb-0">
                            <label class="rs-label" style="display:block; font-weight:600; font-size:0.875rem; margin-bottom:0.35rem; color:#1e293b;">
                                Image or Video Attachment (Optional)
                            </label>
                            <input type="file" name="media_file" id="social-media-file-input" class="rs-input" accept="image/png, image/jpeg, video/mp4, video/quicktime" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                            <span class="rs-help-block" style="display:block; font-size: 0.75rem; color: #64748b; margin-top: 0.35rem;">Supported formats: JPG, PNG, MP4, MOV (Max 50MB). Instagram requires media.</span>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Target Locations & Location Groups -->
                <div class="rs-card rs-mb-4" style="background:#fff; border-radius:8px; border:1px solid #e2e8f0; margin-bottom: 1.5rem;">
                    <div class="rs-card__header" style="padding: 1rem 1.5rem; border-bottom: 1px solid #e2e8f0;">
                        <h3 class="rs-card__title" style="font-size: 1.125rem; font-weight: 600; margin: 0; color: #0f172a;">Target Locations &amp; Groups</h3>
                    </div>
                    <div class="rs-card__body" style="padding: 1.5rem;">
                        <?php if (!empty($groups)): ?>
                            <div class="rs-form-group rs-mb-4" style="margin-bottom: 1.5rem;">
                                <label class="rs-label" style="display:block; font-weight:600; font-size:0.875rem; margin-bottom:0.5rem; color:#1e293b;">
                                    📁 Target Location Groups (Optional)
                                </label>
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 0.75rem;">
                                    <?php foreach ($groups as $grp): ?>
                                        <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.65rem 0.85rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer;">
                                            <input type="checkbox" name="destinations[]" value="group:<?= $grp->getId() ?>">
                                            <div>
                                                <div style="font-weight: 600; font-size: 0.875rem; color: #0f172a;"><?= Html::encode($grp->getName()) ?></div>
                                                <div style="font-size: 0.75rem; color: #64748b;"><?= $grp->getDestinationCount() ?? count($grp->getDestinations()) ?> locations</div>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 1.5rem 0;" />
                        <?php endif; ?>

                        <!-- Individual Destinations -->
                        <div class="rs-form-group rs-mb-0">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                <label class="rs-label" style="font-weight:600; font-size:0.875rem; margin:0; color:#1e293b;">
                                    📍 Target Individual Locations (<?= $totalDestinations ?> Available)
                                </label>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button type="button" class="rs-btn rs-btn--sm rs-btn--secondary" onclick="toggleAllDestinations(true)">Select All</button>
                                    <button type="button" class="rs-btn rs-btn--sm rs-btn--secondary" onclick="toggleAllDestinations(false)">Deselect All</button>
                                </div>
                            </div>

                            <div style="max-height: 280px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 6px; padding: 0.75rem; background: #fafafa;">
                                <!-- Facebook Pages -->
                                <?php if (!empty($fbConfigs)): ?>
                                    <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #1877F2; margin-bottom: 0.4rem;">Facebook Pages &amp; Instagram</div>
                                    <?php foreach ($fbConfigs as $fb): ?>
                                        <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.4rem 0.5rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 4px; margin-bottom: 0.4rem; cursor: pointer;">
                                            <input type="checkbox" name="destinations[]" class="dest-checkbox" value="facebook:<?= $fb->getId() ?>">
                                            <span style="font-size: 0.85rem; font-weight: 600; color: #0f172a;"><?= Html::encode($fb->getDisplayName()) ?></span>
                                            <span style="font-size: 0.75rem; color: #64748b; margin-left: auto;">Facebook</span>
                                        </label>
                                        <?php foreach ($fb->getSubServices() as $sub): ?>
                                            <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.4rem 0.5rem 0.4rem 1.5rem; background: #fdf2f8; border: 1px solid #fbcfe8; border-radius: 4px; margin-bottom: 0.4rem; cursor: pointer;">
                                                <input type="checkbox" name="destinations[]" class="dest-checkbox" value="instagram:<?= $sub->getId() ?>">
                                                <span style="font-size: 0.85rem; font-weight: 600; color: #be185d;">📷 <?= Html::encode($sub->getName()) ?></span>
                                                <span style="font-size: 0.75rem; color: #be185d; margin-left: auto;">Instagram</span>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <!-- Google Locations -->
                                <?php if (!empty($googleConfigs)): ?>
                                    <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #ea4335; margin: 0.75rem 0 0.4rem;">Google Business Profiles</div>
                                    <?php foreach ($googleConfigs as $gc): ?>
                                        <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.4rem 0.5rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 4px; margin-bottom: 0.4rem; cursor: pointer;">
                                            <input type="checkbox" name="destinations[]" class="dest-checkbox" value="google:<?= $gc->getId() ?>">
                                            <span style="font-size: 0.85rem; font-weight: 600; color: #0f172a;"><?= Html::encode($gc->getDisplayName()) ?></span>
                                            <span style="font-size: 0.75rem; color: #64748b; margin-left: auto;">Google</span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Publishing Schedule -->
                <div class="rs-card" style="background:#fff; border-radius:8px; border:1px solid #e2e8f0; margin-bottom: 1.5rem;">
                    <div class="rs-card__header" style="padding: 1rem 1.5rem; border-bottom: 1px solid #e2e8f0;">
                        <h3 class="rs-card__title" style="font-size: 1.125rem; font-weight: 600; margin: 0; color: #0f172a;">Publishing Schedule</h3>
                    </div>
                    <div class="rs-card__body" style="padding: 1.5rem;">
                        <div style="display: flex; gap: 2rem; margin-bottom: 1rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; cursor: pointer;">
                                <input type="radio" name="post_type" value="immediate" checked onchange="toggleSchedule(false)">
                                ⚡ Publish Immediately
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; cursor: pointer;">
                                <input type="radio" name="post_type" value="scheduled" onchange="toggleSchedule(true)">
                                ⏰ Schedule for Later
                            </label>
                        </div>

                        <div id="schedule-box" style="display: none; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem; margin-top: 1rem;">
                            <label class="rs-label" style="display:block; font-weight:600; font-size:0.875rem; margin-bottom:0.35rem; color:#1e293b;">Target Delivery Date &amp; Time</label>
                            <input type="datetime-local" name="scheduled_at" class="rs-input" value="<?= date('Y-m-d\TH:i', strtotime('+1 hour')) ?>" style="width:100%; max-width: 320px; padding:0.5rem; border:1px solid #cbd5e1; border-radius:6px;">
                            <div style="font-size:0.75rem; color:#64748b; margin-top:0.3rem;">The background worker will automatically dispatch this post at the configured time.</div>
                        </div>

                        <div style="display: flex; justify-content: flex-end; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
                            <button type="submit" class="rs-btn rs-btn--primary" style="padding: 0.75rem 2rem; font-size: 1rem; font-weight: 600;">
                                Submit Post &rarr;
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Live Previews -->
            <div style="position: sticky; top: 20px;">
                <div class="rs-card" style="background:#fff; border-radius:8px; border:1px solid #e2e8f0; overflow:hidden;">
                    <div class="rs-card__header" style="padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0;">
                        <h3 class="rs-card__title" style="font-size: 1rem; font-weight: 600; margin: 0; color: #0f172a;">Live Platform Previews</h3>
                    </div>
                    <div class="rs-card__body" style="padding: 1.25rem;">
                        <!-- Facebook Preview -->
                        <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; margin-bottom: 1.25rem; background: #ffffff;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                                <div style="width: 36px; height: 36px; border-radius: 50%; background: #1877F2; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">f</div>
                                <div>
                                    <div style="font-weight: 600; font-size: 0.875rem; color: #0f172a;">Your Business Page</div>
                                    <div style="font-size: 0.75rem; color: #64748b;">Just now &bull; 🌐</div>
                                </div>
                            </div>
                            <div id="preview-fb-text" style="font-size: 0.875rem; color: #1e293b; line-height: 1.45; white-space: pre-wrap; min-height: 48px;">
                                Your post preview will display here...
                            </div>
                        </div>

                        <!-- Instagram Preview -->
                        <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; background: #ffffff;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                                <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(45deg, #f09433, #dc2743, #bc1888); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 14px;">📷</div>
                                <div>
                                    <div style="font-weight: 600; font-size: 0.875rem; color: #0f172a;">your_brand</div>
                                </div>
                            </div>
                            <div style="background: #f1f5f9; border-radius: 6px; padding: 2rem; text-align: center; color: #94a3b8; font-size: 0.8rem; margin-bottom: 0.75rem;">
                                Attached Media Preview
                            </div>
                            <div style="font-size: 0.85rem; color: #1e293b; line-height: 1.4;">
                                <strong>your_brand</strong> <span id="preview-ig-text" style="color: #475569;">Caption will appear here...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var textarea = document.getElementById('post-content-textarea');
    var charCount = document.getElementById('char-count');
    var fbText = document.getElementById('preview-fb-text');
    var igText = document.getElementById('preview-ig-text');

    textarea.addEventListener('input', function() {
        var val = textarea.value;
        charCount.innerText = val.length;
        var placeholder = 'Your post preview will display here...';
        fbText.innerText = val || placeholder;
        igText.innerText = val || 'Caption will appear here...';
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
