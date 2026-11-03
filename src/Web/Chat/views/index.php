<?php

declare(strict_types=1);

/**
 * @var array<int, App\Domain\Chat\Chat> $chats
 * @var int $totalCount
 * @var int $page
 * @var int $pageCount
 * @var int $pageSize
 * @var App\Domain\User\User $currentUser
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 */

$this->setTitle('Chat & Customer Messaging');
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h3 font-weight-bold text-gray-800 mb-1">
                        <i class="bi bi-chat-dots text-primary me-2"></i>Live Chat &amp; Customer Inbox
                    </h2>
                    <p class="text-muted mb-0">Direct customer inquiries arriving through connected Facebook Pages and the website widget.</p>
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

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">All Conversation Threads (<?= $totalCount ?>)</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($chats)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="bi bi-chat-square-dots display-4 text-secondary mb-3 d-block"></i>
                            <h5>Your Inbox is Clear</h5>
                            <p class="small text-muted mb-0">Incoming customer messages from Facebook Messenger and website chat will appear here.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40px;"></th>
                                        <th>Customer</th>
                                        <th>Connected Channel / Page</th>
                                        <th>Started</th>
                                        <th>Status</th>
                                        <th class="text-end" style="width: 120px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($chats as $c): ?>
                                        <tr class="<?= !$c->isRead() ? 'table-warning' : '' ?>">
                                            <td class="text-center">
                                                <?php if (!$c->isRead()): ?>
                                                    <i class="bi bi-circle-fill text-primary small" title="Unread Message"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-check2 text-muted"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($c->getCustomerName()) ?></div>
                                                <small class="text-muted">ID: <code><?= htmlspecialchars($c->getCustomerId()) ?></code></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <i class="bi bi-facebook text-primary me-1"></i><?= htmlspecialchars($c->getServiceLabel() ?? 'Connected Page') ?>
                                                </span>
                                            </td>
                                            <td class="small text-muted"><?= htmlspecialchars($c->getStartDate()) ?></td>
                                            <td>
                                                <?php if (!$c->isRead()): ?>
                                                    <span class="badge bg-danger">New Message</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Read</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="/sys-yii3/chats/view?id=<?= $c->getId() ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-reply me-1"></i> Open Thread
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($pageCount > 1): ?>
                            <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
                                <span class="text-muted small">Showing <?= count($chats) ?> of <?= $totalCount ?> threads</span>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination pagination-sm mb-0">
                                        <?php for ($p = 1; $p <= $pageCount; $p++): ?>
                                            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                                <a class="page-link" href="/sys-yii3/chats?page=<?= $p ?>"><?= $p ?></a>
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
