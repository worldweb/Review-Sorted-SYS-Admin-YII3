<?php

declare(strict_types=1);

/**
 * @var array<int, App\Domain\SocialPost\SocialPost> $posts
 * @var int $totalCount
 * @var int $page
 * @var int $pageCount
 * @var int $pageSize
 * @var App\Domain\User\User $currentUser
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 */

$this->setTitle('Publishing History');
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h3 font-weight-bold text-gray-800 mb-1">
                        <i class="bi bi-clock-history text-primary me-2"></i>Publishing History &amp; Delivery Log
                    </h2>
                    <p class="text-muted mb-0">Audit trail of all dispatched posts across connected social channels.</p>
                </div>
            </div>

            <?= $this->render('_nav', ['activeTab' => 'history']) ?>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="m-0 font-weight-bold text-primary">All Dispatches (<?= $totalCount ?>)</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($posts)): ?>
                        <div class="p-5 text-center text-muted">
                            <h5>No Publishing Records Yet</h5>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Content</th>
                                        <th>Target Channels</th>
                                        <th>Created Date</th>
                                        <th>Dispatched Date</th>
                                        <th>Result</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($posts as $p): ?>
                                        <tr>
                                            <td class="text-muted"><?= $p->getId() ?></td>
                                            <td>
                                                <div class="fw-semibold text-dark text-truncate" style="max-width: 350px;">
                                                    <?= htmlspecialchars($p->getContent()) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php foreach ($p->getDestinations() as $d): ?>
                                                    <span class="badge bg-secondary mb-1">
                                                        <?= htmlspecialchars(strtoupper($d->getPlatform())) ?>: <?= htmlspecialchars($d->getStatus()) ?>
                                                    </span><br/>
                                                <?php endforeach; ?>
                                            </td>
                                            <td class="small text-muted"><?= $p->getCreatedAt() ? date('Y-m-d H:i', $p->getCreatedAt()) : 'N/A' ?></td>
                                            <td class="small text-muted"><?= htmlspecialchars($p->getScheduledAt() ?? 'Immediate') ?></td>
                                            <td>
                                                <?php if ($p->getOverallStatus() === 'published'): ?>
                                                    <span class="badge bg-success"><i class="bi bi-check2-all me-1"></i>Delivered</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info text-dark"><?= htmlspecialchars(ucfirst($p->getOverallStatus())) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="/sys-yii3/social-post/view?id=<?= $p->getId() ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-eye me-1"></i> Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
