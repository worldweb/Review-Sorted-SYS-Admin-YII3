<?php

declare(strict_types=1);

/**
 * @var array<int, App\Domain\SocialPost\SocialPost> $posts
 * @var int $totalCount
 * @var int $page
 * @var int $pageCount
 * @var int $pageSize
 * @var string|null $typeFilter
 * @var App\Domain\User\User $currentUser
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 */

$this->setTitle('Social Publishing Studio');
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h3 font-weight-bold text-gray-800 mb-1">
                        <i class="bi bi-megaphone text-primary me-2"></i>Social Publishing Studio
                    </h2>
                    <p class="text-muted mb-0">Publish and schedule content simultaneously across Facebook, Instagram, and Google Business Profiles.</p>
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

            <?= $this->render('_nav', ['activeTab' => $typeFilter === 'scheduled' ? 'scheduled' : 'index']) ?>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Published &amp; Scheduled Posts (<?= $totalCount ?>)</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($posts)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="bi bi-send display-4 text-secondary mb-3 d-block"></i>
                            <h5>No Posts Found</h5>
                            <p class="small text-muted mb-3">You haven't composed any social media updates yet.</p>
                            <a href="/sys-yii3/social-post/create" class="btn btn-primary btn-sm">
                                <i class="bi bi-pencil-square me-1"></i> Create First Post
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Content Preview</th>
                                        <th>Destinations</th>
                                        <th>Schedule / Mode</th>
                                        <th>Status</th>
                                        <th>Created By</th>
                                        <th class="text-end" style="width: 140px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($posts as $p): ?>
                                        <tr>
                                            <td class="text-muted"><?= $p->getId() ?></td>
                                            <td>
                                                <div class="fw-semibold text-dark text-truncate" style="max-width: 320px;">
                                                    <?= htmlspecialchars(mb_substr($p->getContent(), 0, 90)) . (mb_strlen($p->getContent()) > 90 ? '...' : '') ?>
                                                </div>
                                                <small class="text-muted">
                                                    <?= $p->getCreatedAt() ? date('M d, Y H:i', $p->getCreatedAt()) : '' ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1 flex-wrap">
                                                    <?php foreach ($p->getDestinations() as $d): ?>
                                                        <?php if ($d->getPlatform() === 'facebook'): ?>
                                                            <span class="badge bg-primary"><i class="bi bi-facebook me-1"></i>FB</span>
                                                        <?php elseif ($d->getPlatform() === 'instagram'): ?>
                                                            <span class="badge bg-danger"><i class="bi bi-instagram me-1"></i>IG</span>
                                                        <?php elseif ($d->getPlatform() === 'google'): ?>
                                                            <span class="badge bg-info text-dark"><i class="bi bi-google me-1"></i>Google</span>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                    <?php if (empty($p->getDestinations())): ?>
                                                        <span class="text-muted small">None</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($p->isScheduled()): ?>
                                                    <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Scheduled</span>
                                                    <div class="small text-muted"><?= htmlspecialchars($p->getScheduledAt() ?? '') ?></div>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><i class="bi bi-lightning-charge me-1"></i>Immediate</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php $st = $p->getOverallStatus(); ?>
                                                <?php if ($st === 'published'): ?>
                                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Published</span>
                                                <?php elseif ($st === 'partial_failure'): ?>
                                                    <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>Failed / Partial</span>
                                                <?php elseif ($st === 'scheduled'): ?>
                                                    <span class="badge bg-info text-dark"><i class="bi bi-calendar-check me-1"></i>Queued</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($st)) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small text-muted">
                                                <?= htmlspecialchars($p->getAuthorEmail() ?? 'Admin') ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="/sys-yii3/social-post/view?id=<?= $p->getId() ?>" class="btn btn-outline-secondary" title="View Delivery Log">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <?php if ($p->isScheduled() && $p->getOverallStatus() !== 'published'): ?>
                                                        <a href="/sys-yii3/social-post/update?id=<?= $p->getId() ?>" class="btn btn-outline-primary" title="Edit Post">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <a href="/sys-yii3/social-post/cancel?id=<?= $p->getId() ?>" class="btn btn-outline-warning" title="Cancel" onclick="return confirm('Cancel this scheduled post?');">
                                                            <i class="bi bi-x-circle"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="/sys-yii3/social-post/delete?id=<?= $p->getId() ?>" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Delete this post?');">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($pageCount > 1): ?>
                            <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
                                <span class="text-muted small">Showing <?= count($posts) ?> of <?= $totalCount ?> posts</span>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination pagination-sm mb-0">
                                        <?php for ($pg = 1; $pg <= $pageCount; $pg++): ?>
                                            <li class="page-item <?= $pg === $page ? 'active' : '' ?>">
                                                <a class="page-link" href="/sys-yii3/social-post?page=<?= $pg ?><?= !empty($typeFilter) ? '&type=' . urlencode($typeFilter) : '' ?>"><?= $pg ?></a>
                                            </li>
                                        <?php endfor; ?>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
