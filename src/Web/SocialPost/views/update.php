<?php

declare(strict_types=1);

/**
 * @var App\Domain\SocialPost\SocialPost $model
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 */

$this->setTitle('Edit Post #' . $model->getId());
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h3 font-weight-bold text-gray-800 mb-1">
                        <i class="bi bi-pencil-square text-primary me-2"></i>Edit Scheduled Post #<?= $model->getId() ?>
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="/sys-yii3/social-post">Social Posts</a></li>
                            <li class="breadcrumb-item"><a href="/sys-yii3/social-post/view?id=<?= $model->getId() ?>">Post #<?= $model->getId() ?></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Edit</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="/sys-yii3/social-post/view?id=<?= $model->getId() ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Cancel
                    </a>
                </div>
            </div>

            <?php if ($flash->has('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars((string) $flash->get('error')) ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="m-0 font-weight-bold text-primary">Edit Post Details</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="/sys-yii3/social-post/update?id=<?= $model->getId() ?>">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">

                        <div class="mb-3">
                            <label for="content" class="form-label fw-bold">Post Content <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="content" name="content" rows="6" required><?= htmlspecialchars($model->getContent()) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="scheduled_at" class="form-label fw-bold">Scheduled Datetime</label>
                            <input type="datetime-local" class="form-control" id="scheduled_at" name="scheduled_at" value="<?= htmlspecialchars(!empty($model->getScheduledAt()) ? date('Y-m-d\TH:i', strtotime($model->getScheduledAt())) : '') ?>">
                        </div>

                        <div class="pt-3 border-top d-flex justify-content-end">
                            <a href="/sys-yii3/social-post/view?id=<?= $model->getId() ?>" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-lg me-1"></i> Update Post
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
