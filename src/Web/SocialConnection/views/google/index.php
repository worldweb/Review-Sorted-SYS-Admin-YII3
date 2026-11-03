<?php

declare(strict_types=1);

/**
 * @var array<int, App\Domain\SocialConnection\ServiceConfig> $configs
 * @var int $totalCount
 * @var int $page
 * @var int $pageCount
 * @var int $pageSize
 * @var App\Domain\User\User $currentUser
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 */

$this->setTitle('Google Business Profile Settings');
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="h3 font-weight-bold text-gray-800 mb-1">
                        <i class="bi bi-google text-danger me-2"></i>Google Business Profile Settings
                    </h2>
                    <p class="text-muted mb-0">Manage connected Google Business Profile locations and review synchronization.</p>
                </div>
                <div>
                    <a href="/sys-yii3/settings/google/setup" class="btn btn-danger">
                        <i class="bi bi-link-45deg me-1"></i> Connect Google Location
                    </a>
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
        <div class="col-lg-3 col-md-4 mb-4">
            <?= $this->render('../_nav', ['activeTab' => 'google']) ?>
        </div>

        <div class="col-lg-9 col-md-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-danger">Connected Google Business Locations (<?= $totalCount ?>)</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($configs)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="bi bi-google display-4 text-secondary mb-3 d-block"></i>
                            <h5>No Google Business Locations Connected</h5>
                            <p class="small text-muted mb-3">Connect your Google Business Profile to pull customer reviews and publish social updates directly to Maps and Search.</p>
                            <a href="/sys-yii3/settings/google/setup" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-plus-lg me-1"></i> Connect Google Location
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Location Name / Label</th>
                                        <th>Google Location ID</th>
                                        <th>Status</th>
                                        <th>Connected On</th>
                                        <th class="text-end" style="width: 150px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($configs as $cfg): ?>
                                        <tr>
                                            <td class="text-muted"><?= $cfg->getId() ?></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($cfg->getDisplayName()) ?></div>
                                                <?php if (!empty($cfg->getEmailLowReview())): ?>
                                                    <small class="text-muted"><i class="bi bi-envelope me-1"></i>Alert: <?= htmlspecialchars($cfg->getEmailLowReview()) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><code class="text-secondary"><?= htmlspecialchars($cfg->getPageId() ?? 'N/A') ?></code></td>
                                            <td>
                                                <?php if ($cfg->isLinked()): ?>
                                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Unlinked</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small text-muted">
                                                <?= $cfg->getCreatedAt() ? date('M d, Y', $cfg->getCreatedAt()) : 'N/A' ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="/sys-yii3/settings/google/view?id=<?= $cfg->getId() ?>" class="btn btn-outline-secondary" title="View Details">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="/sys-yii3/settings/google/update?id=<?= $cfg->getId() ?>" class="btn btn-outline-primary" title="Edit Settings">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="/sys-yii3/settings/google/revoke?id=<?= $cfg->getId() ?>" class="btn btn-outline-danger" title="Unlink" onclick="return confirm('Are you sure you want to unlink this Google location?');">
                                                        <i class="bi bi-slash-circle"></i>
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
                                <span class="text-muted small">Showing <?= count($configs) ?> of <?= $totalCount ?> Google locations</span>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination pagination-sm mb-0">
                                        <?php for ($p = 1; $p <= $pageCount; $p++): ?>
                                            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                                <a class="page-link" href="/sys-yii3/settings/google?page=<?= $p ?>"><?= $p ?></a>
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
