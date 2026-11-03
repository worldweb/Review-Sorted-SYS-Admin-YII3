<?php

declare(strict_types=1);

/**
 * @var App\Domain\SocialPost\SocialPost $model
 * @var Yiisoft\Session\Flash\Flash $flash
 */

$this->setTitle('Post #' . $model->getId() . ' Details');
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h3 font-weight-bold text-gray-800 mb-1">
                        <i class="bi bi-send text-primary me-2"></i>Post #<?= $model->getId() ?> Delivery Details
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="/sys-yii3/social-post">Social Posts</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Post #<?= $model->getId() ?></li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <?php if ($model->getOverallStatus() === 'partial_failure'): ?>
                        <a href="/sys-yii3/social-post/retry?id=<?= $model->getId() ?>" class="btn btn-warning me-2">
                            <i class="bi bi-arrow-repeat me-1"></i> Retry Failed
                        </a>
                    <?php endif; ?>
                    <a href="/sys-yii3/social-post" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back
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

            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm border-0 mb-4 h-100">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="m-0 font-weight-bold text-primary">Content &amp; Configuration</h6>
                        </div>
                        <div class="card-body">
                            <div class="p-3 bg-light rounded border mb-3 text-dark text-break" style="white-space: pre-wrap; font-size: 15px;">
                                <?= htmlspecialchars($model->getContent()) ?>
                            </div>

                            <dl class="row mb-0">
                                <dt class="col-sm-4 text-muted">Author</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($model->getAuthorEmail() ?? 'Admin') ?></dd>

                                <dt class="col-sm-4 text-muted">Delivery Mode</dt>
                                <dd class="col-sm-8">
                                    <?php if ($model->isScheduled()): ?>
                                        <span class="badge bg-warning text-dark">Scheduled for <?= htmlspecialchars($model->getScheduledAt() ?? '') ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Immediate</span>
                                    <?php endif; ?>
                                </dd>

                                <dt class="col-sm-4 text-muted">Created Date</dt>
                                <dd class="col-sm-8"><?= $model->getCreatedAt() ? date('Y-m-d H:i:s', $model->getCreatedAt()) : 'N/A' ?></dd>

                                <dt class="col-sm-4 text-muted">Overall Status</dt>
                                <dd class="col-sm-8">
                                    <span class="badge bg-primary text-uppercase"><?= htmlspecialchars($model->getOverallStatus()) ?></span>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm border-0 mb-4 h-100">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Destination Delivery Breakdown</h6>
                            <span class="badge bg-secondary"><?= count($model->getDestinations()) ?> Targets</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Platform</th>
                                            <th>Target Name</th>
                                            <th>Status</th>
                                            <th>Remote ID / Log</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($model->getDestinations())): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted p-4">No destination records found.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($model->getDestinations() as $d): ?>
                                                <tr>
                                                    <td>
                                                        <?php if ($d->getPlatform() === 'facebook'): ?>
                                                            <span class="badge bg-primary"><i class="bi bi-facebook me-1"></i>Facebook</span>
                                                        <?php elseif ($d->getPlatform() === 'instagram'): ?>
                                                            <span class="badge bg-danger"><i class="bi bi-instagram me-1"></i>Instagram</span>
                                                        <?php elseif ($d->getPlatform() === 'google'): ?>
                                                            <span class="badge bg-info text-dark"><i class="bi bi-google me-1"></i>Google</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary"><?= htmlspecialchars($d->getPlatform()) ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <strong><?= htmlspecialchars($d->getDestinationName() ?? $d->getPlatform()) ?></strong>
                                                    </td>
                                                    <td>
                                                        <?php if ($d->isPublished()): ?>
                                                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Published</span>
                                                        <?php elseif ($d->isFailed()): ?>
                                                            <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>Failed</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-info text-dark"><i class="bi bi-clock me-1"></i>Queued</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="small">
                                                        <?php if (!empty($d->getRemotePostId())): ?>
                                                            <code class="text-success"><?= htmlspecialchars($d->getRemotePostId()) ?></code>
                                                        <?php elseif (!empty($d->getErrorMessage())): ?>
                                                            <span class="text-danger"><?= htmlspecialchars($d->getErrorMessage()) ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">Awaiting worker</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
