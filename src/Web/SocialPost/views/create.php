<?php

declare(strict_types=1);

/**
 * @var App\Domain\User\User $currentUser
 * @var array<int, App\Domain\SocialConnection\ServiceConfig> $fbConfigs
 * @var array<int, App\Domain\SocialConnection\ServiceConfig> $googleConfigs
 * @var array<int, App\Domain\LocationGroup\LocationGroup> $groups
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 */

$this->setTitle('Create Social Post');
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h3 font-weight-bold text-gray-800 mb-1">
                        <i class="bi bi-pencil-square text-success me-2"></i>Compose New Social Post
                    </h2>
                    <p class="text-muted mb-0">Craft your content and broadcast it across multiple connected social networks simultaneously.</p>
                </div>
                <div>
                    <a href="/sys-yii3/social-post" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to Posts
                    </a>
                </div>
            </div>

            <?php if ($flash->has('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars((string) $flash->get('error')) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="/sys-yii3/social-post/create" id="postComposerForm">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="row">
                    <!-- Left: Composer Form -->
                    <div class="col-lg-7 mb-4">
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h6 class="m-0 font-weight-bold text-primary">1. Post Content</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="postContent" class="form-label fw-bold">Message Text <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="postContent" name="content" rows="6" placeholder="What's happening? Share updates, promotions, announcements..." required></textarea>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <small class="text-muted">Character count: <span id="charCount">0</span></small>
                                        <small class="text-muted">Supports hashtags and emojis</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Destination Selection -->
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">2. Select Social Destinations</h6>
                                <span class="text-muted small">Choose accounts or location groups</span>
                            </div>
                            <div class="card-body">
                                <!-- Location Groups -->
                                <?php if (!empty($groups)): ?>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-secondary text-uppercase small">Location Groups (Clusters)</label>
                                        <div class="list-group">
                                            <?php foreach ($groups as $grp): ?>
                                                <label class="list-group-item d-flex align-items-center cursor-pointer">
                                                    <input class="form-check-input me-3" type="checkbox" name="destinations[]" value="group:<?= $grp->getId() ?>">
                                                    <div>
                                                        <strong><i class="bi bi-diagram-3 text-info me-1"></i><?= htmlspecialchars($grp->getName()) ?></strong>
                                                        <span class="badge bg-secondary ms-2"><?= $grp->getDestinationCount() ?? count($grp->getDestinations()) ?> destinations</span>
                                                        <?php if (!empty($grp->getDescription())): ?>
                                                            <div class="small text-muted"><?= htmlspecialchars($grp->getDescription()) ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Facebook Pages -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold text-secondary text-uppercase small"><i class="bi bi-facebook text-primary me-1"></i>Facebook Pages</label>
                                    <?php if (empty($fbConfigs)): ?>
                                        <p class="text-muted small mb-0">No Facebook pages connected. <a href="/sys-yii3/settings/facebook">Connect one now</a>.</p>
                                    <?php else: ?>
                                        <div class="list-group">
                                            <?php foreach ($fbConfigs as $fb): ?>
                                                <label class="list-group-item d-flex align-items-center">
                                                    <input class="form-check-input me-3" type="checkbox" name="destinations[]" value="facebook:<?= $fb->getId() ?>">
                                                    <div>
                                                        <strong><?= htmlspecialchars($fb->getDisplayName()) ?></strong>
                                                        <code class="small text-muted ms-1">(Page ID: <?= htmlspecialchars($fb->getPageId() ?? '') ?>)</code>
                                                    </div>
                                                </label>

                                                <?php foreach ($fb->getSubServices() as $sub): ?>
                                                    <label class="list-group-item d-flex align-items-center ps-5 bg-light">
                                                        <input class="form-check-input me-3" type="checkbox" name="destinations[]" value="instagram:<?= $sub->getId() ?>">
                                                        <div>
                                                            <i class="bi bi-instagram text-danger me-1"></i>
                                                            <strong><?= htmlspecialchars($sub->getName()) ?></strong>
                                                            <span class="badge bg-light text-dark border ms-1">Instagram</span>
                                                        </div>
                                                    </label>
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Google Business Profiles -->
                                <div class="mb-2">
                                    <label class="form-label fw-bold text-secondary text-uppercase small"><i class="bi bi-google text-danger me-1"></i>Google Business Profiles</label>
                                    <?php if (empty($googleConfigs)): ?>
                                        <p class="text-muted small mb-0">No Google locations connected. <a href="/sys-yii3/settings/google">Connect one now</a>.</p>
                                    <?php else: ?>
                                        <div class="list-group">
                                            <?php foreach ($googleConfigs as $gc): ?>
                                                <label class="list-group-item d-flex align-items-center">
                                                    <input class="form-check-input me-3" type="checkbox" name="destinations[]" value="google:<?= $gc->getId() ?>">
                                                    <div>
                                                        <strong><?= htmlspecialchars($gc->getDisplayName()) ?></strong>
                                                        <code class="small text-muted ms-1">(Location ID: <?= htmlspecialchars($gc->getPageId() ?? '') ?>)</code>
                                                    </div>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Scheduling & Publish -->
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h6 class="m-0 font-weight-bold text-primary">3. Publishing Schedule</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="post_type" id="typeImmediate" value="immediate" checked onchange="toggleScheduleBox(false)">
                                    <label class="form-check-label fw-bold" for="typeImmediate">
                                        <i class="bi bi-lightning-charge text-warning me-1"></i> Publish Immediately
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="post_type" id="typeScheduled" value="scheduled" onchange="toggleScheduleBox(true)">
                                    <label class="form-check-label fw-bold" for="typeScheduled">
                                        <i class="bi bi-clock text-primary me-1"></i> Schedule for Later
                                    </label>
                                </div>

                                <div id="scheduleBox" class="mt-3 p-3 bg-light rounded border d-none">
                                    <label for="scheduled_at" class="form-label fw-bold">Select Date &amp; Time</label>
                                    <input type="datetime-local" class="form-control" id="scheduled_at" name="scheduled_at" value="<?= date('Y-m-d\TH:i', strtotime('+1 hour')) ?>">
                                    <div class="form-text">Scheduled posts are dispatched automatically by the background worker.</div>
                                </div>

                                <div class="pt-3 border-top d-flex justify-content-end">
                                    <button type="submit" class="btn btn-success px-5 py-2">
                                        <i class="bi bi-send me-1"></i> Submit Post
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Live Social Media Previews -->
                    <div class="col-lg-5">
                        <div class="card shadow-sm border-0 mb-4 sticky-top" style="top: 20px;">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-eye me-1"></i>Live Platform Previews</h6>
                            </div>
                            <div class="card-body">
                                <ul class="nav nav-tabs mb-3" id="previewTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="fb-tab" data-bs-toggle="tab" data-bs-target="#fb-prev" type="button" role="tab"><i class="bi bi-facebook text-primary me-1"></i>Facebook</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="ig-tab" data-bs-toggle="tab" data-bs-target="#ig-prev" type="button" role="tab"><i class="bi bi-instagram text-danger me-1"></i>Instagram</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="google-tab" data-bs-toggle="tab" data-bs-target="#google-prev" type="button" role="tab"><i class="bi bi-google text-danger me-1"></i>Google</button>
                                    </li>
                                </ul>

                                <div class="tab-content" id="previewTabsContent">
                                    <!-- Facebook Card Preview -->
                                    <div class="tab-pane fade show active" id="fb-prev" role="tabpanel">
                                        <div class="border rounded p-3 bg-white shadow-sm">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                                    <i class="bi bi-facebook"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold small">Your Business Page</div>
                                                    <div class="text-muted" style="font-size: 11px;">Just now &bull; <i class="bi bi-globe"></i></div>
                                                </div>
                                            </div>
                                            <div id="fbPreviewContent" class="text-break py-2 text-dark" style="font-size: 14px; white-space: pre-wrap;">Your post content will appear here...</div>
                                        </div>
                                    </div>

                                    <!-- Instagram Preview -->
                                    <div class="tab-pane fade" id="ig-prev" role="tabpanel">
                                        <div class="border rounded p-3 bg-white shadow-sm">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 38px; height: 38px;">
                                                    <i class="bi bi-instagram"></i>
                                                </div>
                                                <div class="fw-bold small">@your_handle</div>
                                            </div>
                                            <div class="bg-light text-center py-4 rounded mb-2 text-muted small">
                                                <i class="bi bi-image display-6 d-block mb-1"></i>(Instagram Post Media)
                                            </div>
                                            <div class="small">
                                                <strong>your_handle</strong> <span id="igPreviewContent" class="text-muted">Your caption preview will display here...</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Google Business Profile Preview -->
                                    <div class="tab-pane fade" id="google-prev" role="tabpanel">
                                        <div class="border rounded p-3 bg-white shadow-sm">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="bg-white border rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 38px; height: 38px;">
                                                    <i class="bi bi-google text-danger"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold small">Google Search &amp; Maps Post</div>
                                                    <div class="text-muted" style="font-size: 11px;">Update from store</div>
                                                </div>
                                            </div>
                                            <div id="googlePreviewContent" class="text-break py-2 text-dark" style="font-size: 13px; white-space: pre-wrap;">Your Google Business update will appear here...</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var contentArea = document.getElementById('postContent');
    var charCount = document.getElementById('charCount');
    var fbPrev = document.getElementById('fbPreviewContent');
    var igPrev = document.getElementById('igPreviewContent');
    var googlePrev = document.getElementById('googlePreviewContent');

    contentArea.addEventListener('input', function() {
        var text = contentArea.value;
        charCount.innerText = text.length;
        var placeholder = 'Your post content will appear here...';
        fbPrev.innerText = text || placeholder;
        igPrev.innerText = text || placeholder;
        googlePrev.innerText = text || placeholder;
    });
});

function toggleScheduleBox(show) {
    var box = document.getElementById('scheduleBox');
    if (show) {
        box.classList.remove('d-none');
    } else {
        box.classList.add('d-none');
    }
}
</script>
