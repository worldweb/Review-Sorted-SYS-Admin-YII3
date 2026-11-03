<?php

declare(strict_types=1);

/**
 * @var App\Domain\User\User $currentUser
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 */

$this->setTitle('Create New Plan');
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h3 font-weight-bold text-gray-800 mb-1">
                        <i class="bi bi-plus-circle text-primary me-2"></i>Create New Subscription Plan
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="/sys-yii3/subscription">Subscription Plans</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Create Plan</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="/sys-yii3/subscription" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to Plans
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
                    <h6 class="m-0 font-weight-bold text-primary">Plan Information</h6>
                </div>
                <div class="card-body">
                    <?= $this->render('_form', [
                        'model' => null,
                        'csrfToken' => $csrfToken,
                    ]) ?>
                </div>
            </div>
        </div>
    </div>
</div>
