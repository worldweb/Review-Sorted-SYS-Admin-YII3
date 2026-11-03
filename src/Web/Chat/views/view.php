<?php

declare(strict_types=1);

/**
 * @var App\Domain\Chat\Chat $model
 * @var App\Domain\User\User $currentUser
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 */

$this->setTitle('Chat with ' . $model->getCustomerName());
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h3 font-weight-bold text-gray-800 mb-1">
                        <i class="bi bi-chat-left-text text-primary me-2"></i>Conversation: <?= htmlspecialchars($model->getCustomerName()) ?>
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="/sys-yii3/chats">Chat Inbox</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($model->getCustomerName()) ?></li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="/sys-yii3/chats" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to Inbox
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
                <!-- Main Chat Thread -->
                <div class="col-lg-8 mb-4">
                    <div class="card shadow-sm border-0 d-flex flex-column" style="height: 600px;">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 38px; height: 38px;">
                                    <i class="bi bi-person"></i>
                                </div>
                                <div>
                                    <h6 class="m-0 font-weight-bold text-dark"><?= htmlspecialchars($model->getCustomerName()) ?></h6>
                                    <small class="text-muted"><i class="bi bi-facebook text-primary me-1"></i><?= htmlspecialchars($model->getServiceLabel() ?? 'Connected Page') ?></small>
                                </div>
                            </div>
                            <span class="badge bg-light text-dark border">Started: <?= htmlspecialchars($model->getStartDate()) ?></span>
                        </div>

                        <!-- Chat Messages Container -->
                        <div class="card-body p-4 flex-grow-1 overflow-auto bg-light" id="chatThreadBox">
                            <?php if (empty($model->getMessages())): ?>
                                <div class="text-center text-muted py-5">
                                    <p class="mb-0">No message history recorded in this thread.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($model->getMessages() as $msg): ?>
                                    <div class="d-flex mb-3 <?= $msg->isOutgoing() ? 'justify-content-end' : 'justify-content-start' ?>">
                                        <?php if ($msg->isIncoming()): ?>
                                            <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2 mt-1 flex-shrink-0" style="width: 32px; height: 32px; font-size: 13px;">
                                                <i class="bi bi-person"></i>
                                            </div>
                                        <?php endif; ?>

                                        <div style="max-width: 70%;">
                                            <div class="p-3 rounded shadow-sm <?= $msg->isOutgoing() ? 'bg-primary text-white' : 'bg-white text-dark border' ?>">
                                                <div class="text-break" style="white-space: pre-wrap; font-size: 14px;"><?= htmlspecialchars($msg->getChatText()) ?></div>
                                            </div>
                                            <div class="text-muted mt-1 <?= $msg->isOutgoing() ? 'text-end' : '' ?>" style="font-size: 11px;">
                                                <?= $msg->isOutgoing() ? 'Staff Agent' : htmlspecialchars($model->getCustomerName()) ?> &bull; <?= htmlspecialchars($msg->getChatDate()) ?>
                                            </div>
                                        </div>

                                        <?php if ($msg->isOutgoing()): ?>
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center ms-2 mt-1 flex-shrink-0" style="width: 32px; height: 32px; font-size: 13px;">
                                                <i class="bi bi-person-badge"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Reply Composer Box -->
                        <div class="card-footer bg-white p-3 border-top">
                            <form method="post" action="/sys-yii3/chats/view?id=<?= $model->getId() ?>">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                                <div class="input-group">
                                    <textarea class="form-control" name="message" rows="2" placeholder="Type your response to <?= htmlspecialchars($model->getCustomerName()) ?>..." required></textarea>
                                    <button class="btn btn-primary px-4" type="submit">
                                        <i class="bi bi-send me-1"></i> Send Reply
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Customer Details Sidebar -->
                <div class="col-lg-4 mb-4">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="m-0 font-weight-bold text-primary">Thread Metadata</h6>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-4 text-muted">Customer</dt>
                                <dd class="col-sm-8 fw-bold text-dark"><?= htmlspecialchars($model->getCustomerName()) ?></dd>

                                <dt class="col-sm-4 text-muted">Remote ID</dt>
                                <dd class="col-sm-8"><code><?= htmlspecialchars($model->getCustomerId()) ?></code></dd>

                                <dt class="col-sm-4 text-muted">Service Page</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($model->getServiceLabel() ?? 'Connected Page') ?></dd>

                                <dt class="col-sm-4 text-muted">Total Messages</dt>
                                <dd class="col-sm-8"><?= count($model->getMessages()) ?></dd>

                                <dt class="col-sm-4 text-muted">Thread Status</dt>
                                <dd class="col-sm-8"><span class="badge bg-success">Active</span></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var box = document.getElementById('chatThreadBox');
    if (box) {
        box.scrollTop = box.scrollHeight;
    }
});
</script>
