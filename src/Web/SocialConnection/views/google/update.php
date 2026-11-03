<?php

declare(strict_types=1);

/**
 * @var App\Domain\SocialConnection\ServiceConfig $model
 * @var string $csrfToken
 * @var Yiisoft\Session\Flash\Flash $flash
 */

$this->setTitle('Edit Google Location: ' . $model->getDisplayName());
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="h3 font-weight-bold text-gray-800 mb-1">
                        <i class="bi bi-pencil-square text-danger me-2"></i>Edit Google Location
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="/sys-yii3/settings/google">Google Settings</a></li>
                            <li class="breadcrumb-item"><a href="/sys-yii3/settings/google/view?id=<?= $model->getId() ?>"><?= htmlspecialchars($model->getDisplayName()) ?></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Edit</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="/sys-yii3/settings/google/view?id=<?= $model->getId() ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Cancel
                    </a>
                </div>
            </div>

            <?php if ($flash->has('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars((string) $flash->get('error')) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-md-4 mb-4">
            <?= $this->render('../_nav', ['activeTab' => 'google']) ?>
        </div>

        <div class="col-lg-9 col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="m-0 font-weight-bold text-danger">Edit Settings for <?= htmlspecialchars($model->getDisplayName()) ?></h6>
                </div>
                <div class="card-body">
                    <form method="post" action="/sys-yii3/settings/google/update?id=<?= $model->getId() ?>">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">

                        <div class="mb-3">
                            <label for="label" class="form-label fw-bold">Internal Label <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="label" name="label" value="<?= htmlspecialchars($model->getLabel()) ?>" required>
                            <div class="form-text">Custom name to identify this location internally.</div>
                        </div>

                        <div class="mb-3">
                            <label for="email_low_review" class="form-label fw-bold">Low Rating Notification Email</label>
                            <input type="email" class="form-control" id="email_low_review" name="email_low_review" value="<?= htmlspecialchars($model->getEmailLowReview() ?? '') ?>" placeholder="manager@example.com">
                            <div class="form-text">An email address that receives instant notifications when low reviews are received.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted">Remote Google Location Name</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($model->getPageName() ?? 'N/A') ?>" readonly disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted">Remote Location ID</label>
                            <input type="text" class="form-control bg-light font-monospace" value="<?= htmlspecialchars($model->getPageId() ?? 'N/A') ?>" readonly disabled>
                        </div>

                        <div class="pt-3 border-top d-flex justify-content-end">
                            <a href="/sys-yii3/settings/google" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-danger px-4">
                                <i class="bi bi-check-lg me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
