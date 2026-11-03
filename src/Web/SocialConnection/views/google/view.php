<?php

declare(strict_types=1);

/**
 * @var App\Domain\SocialConnection\ServiceConfig $model
 */

$this->setTitle('Google Location: ' . $model->getDisplayName());
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="h3 font-weight-bold text-gray-800 mb-1">
                        <i class="bi bi-google text-danger me-2"></i><?= htmlspecialchars($model->getDisplayName()) ?>
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="/sys-yii3/settings/google">Google Settings</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($model->getDisplayName()) ?></li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="/sys-yii3/settings/google/update?id=<?= $model->getId() ?>" class="btn btn-primary me-2">
                        <i class="bi bi-pencil me-1"></i> Edit Settings
                    </a>
                    <a href="/sys-yii3/settings/google" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-md-4 mb-4">
            <?= $this->render('../_nav', ['activeTab' => 'google']) ?>
        </div>

        <div class="col-lg-9 col-md-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="m-0 font-weight-bold text-danger">Google Business Location Details</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3 text-muted">ID</dt>
                        <dd class="col-sm-9"><?= $model->getId() ?></dd>

                        <dt class="col-sm-3 text-muted">Location Name</dt>
                        <dd class="col-sm-9 fw-bold text-dark"><?= htmlspecialchars($model->getPageName() ?? 'N/A') ?></dd>

                        <dt class="col-sm-3 text-muted">Custom Label</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars($model->getLabel()) ?></dd>

                        <dt class="col-sm-3 text-muted">Google Location ID</dt>
                        <dd class="col-sm-9"><code><?= htmlspecialchars($model->getPageId() ?? 'N/A') ?></code></dd>

                        <dt class="col-sm-3 text-muted">Connection Status</dt>
                        <dd class="col-sm-9">
                            <?php if ($model->isLinked()): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Linked &amp; Active</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Unlinked</span>
                            <?php endif; ?>
                        </dd>

                        <dt class="col-sm-3 text-muted">Low Rating Alert Email</dt>
                        <dd class="col-sm-9">
                            <?= !empty($model->getEmailLowReview()) ? htmlspecialchars($model->getEmailLowReview()) : '<span class="text-muted fst-italic">Not set</span>' ?>
                        </dd>

                        <dt class="col-sm-3 text-muted">Created At</dt>
                        <dd class="col-sm-9"><?= $model->getCreatedAt() ? date('Y-m-d H:i:s', $model->getCreatedAt()) : 'N/A' ?></dd>

                        <dt class="col-sm-3 text-muted">Last Updated</dt>
                        <dd class="col-sm-9"><?= $model->getUpdatedAt() ? date('Y-m-d H:i:s', $model->getUpdatedAt()) : 'N/A' ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
