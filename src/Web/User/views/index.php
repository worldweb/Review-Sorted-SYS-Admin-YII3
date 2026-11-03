<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Users Index View (Yii 3 Modern Theme)
 * Rendered within Main Authenticated Layout.
 *
 * @var array<\App\Domain\User\User> $users
 * @var int $total
 * @var \App\Domain\User\UserFilter $filter
 * @var \App\Domain\User\User $currentUser
 * @var \App\Security\TenantContextInterface $context
 * @var bool $canAddAdmin
 * @var bool $canAddAccountAdmin
 * @var bool $canAddWorker
 * @var array<int, string> $accountsList
 * @var string|null $csrfToken
 * @var Yiisoft\View\WebView $this
 */

$this->setTitle('Users');

$limit = $filter->pageSize > 0 ? $filter->pageSize : 20;
$totalPages = max(1, (int) ceil($total / $limit));
$currentPage = $filter->page;

// Reusable Page Header Action Buttons
$actionButtons = [];
if ($canAddAdmin) {
    $actionButtons[] = '<a href="/sys-yii3/user/create" class="rs-btn rs-btn--ghost rs-d-flex rs-align-items-center rs-gap-2"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg> Add Admin</a>';
    $actionButtons[] = '<a href="/sys-yii3/user/create-acc-admin" class="rs-btn rs-btn--secondary rs-d-flex rs-align-items-center rs-gap-2"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg> Add Account Admin</a>';
}
if ($canAddWorker) {
    $actionButtons[] = '<a href="/sys-yii3/user/create-worker" class="rs-btn rs-btn--primary rs-d-flex rs-align-items-center rs-gap-2"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg> Add User</a>';
}

$title = 'Users';
$description = 'Manage platform administrators, account managers, and standard users.';
$actions = $actionButtons;
include __DIR__ . '/../../Shared/Components/_page_header.php';
?>

<!-- Data Grid -->
<div class="rs-card">
    <form method="get" action="/sys-yii3/users" id="users-filter-form">
    <div class="rs-table-wrapper" style="overflow-x: auto;">
        <table class="rs-table rs-table--hover" style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="background: rgba(0,0,0,0.02);">
                    <th style="width: 50px; text-align: center;">#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Role</th>
                    <th>Account</th>
                    <th>Created At</th>
                    <th style="width: 140px; text-align: right;">Actions</th>
                </tr>
                <tr class="rs-table__filter-row" style="background: #f8fafc;">
                    <td></td>
                    <td>
                        <input type="text" name="q" class="rs-input rs-w-100" style="font-size: 12px; padding: 4px 8px;" placeholder="Filter name/email..." value="<?= Html::encode($filter->search ?? '') ?>" onkeydown="if(event.key==='Enter') this.form.submit();">
                    </td>
                    <td></td>
                    <td>
                        <select name="status" class="rs-select rs-input rs-w-100" style="font-size: 12px; padding: 4px 6px;" onchange="this.form.submit()">
                            <option value="">All</option>
                            <option value="1" <?= $filter->status === 1 ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= $filter->status === 0 ? 'selected' : '' ?>>Disabled</option>
                        </select>
                    </td>
                    <td>
                        <select name="role" class="rs-select rs-input rs-w-100" style="font-size: 12px; padding: 4px 6px;" onchange="this.form.submit()">
                            <option value="">All</option>
                            <option value="super" <?= $filter->role === 'super' ? 'selected' : '' ?>>Administrator (root)</option>
                            <option value="admin" <?= $filter->role === 'admin' ? 'selected' : '' ?>>Administrator</option>
                            <option value="account_admin" <?= $filter->role === 'account_admin' ? 'selected' : '' ?>>Account Administrator</option>
                            <option value="worker" <?= $filter->role === 'worker' ? 'selected' : '' ?>>User</option>
                        </select>
                    </td>
                    <td>
                        <?php if (!empty($accountsList)): ?>
                            <select name="account_id" class="rs-select rs-input rs-w-100" style="font-size: 12px; padding: 4px 6px;" onchange="this.form.submit()">
                                <option value="">All</option>
                                <?php foreach ($accountsList as $accId => $accName): ?>
                                    <option value="<?= $accId ?>" <?= $filter->accountId === $accId ? 'selected' : '' ?>>
                                        <?= Html::encode($accName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </td>
                    <td></td>
                    <td class="rs-text-right">
                        <button type="submit" class="rs-btn rs-btn--xs rs-btn--primary">Filter</button>
                    </td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 32px; color: var(--rs-text-secondary, #7B8A8B);">
                            No users found matching the filter criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $idx => $u): ?>
                        <tr>
                            <td style="text-align: center; color: var(--rs-text-secondary, #7B8A8B); font-size: 13px;">
                                <?= $filter->getOffset() + $idx + 1 ?>
                            </td>
                            <td>
                                <a href="/sys-yii3/user/view?id=<?= $u->getId() ?>" style="color: var(--rs-primary, #234137); font-weight: 600; text-decoration: none;">
                                    <?= Html::encode($u->getName()) ?>
                                </a>
                            </td>
                            <td style="color: var(--rs-text-secondary, #7B8A8B);">
                                <?= Html::encode($u->getEmail()) ?>
                            </td>
                            <td>
                                <span class="rs-badge <?= $u->isActive() ? 'rs-badge--success' : 'rs-badge--danger' ?>">
                                    <?= Html::encode($u->getStatusName()) ?>
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 13px;"><?= Html::encode($u->getRoleLabel()) ?></span>
                            </td>
                            <td>
                                <?php if ($u->getAccountId() !== null): ?>
                                    Account #<?= $u->getAccountId() ?>
                                <?php else: ?>
                                    <span style="color: var(--rs-text-secondary, #7B8A8B);">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="color: var(--rs-text-secondary, #7B8A8B); font-size: 13px;">
                                <?= Html::encode($u->getCreatedAtStr()) ?>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="/sys-yii3/user/view?id=<?= $u->getId() ?>" class="rs-btn rs-btn--sm rs-btn--ghost" style="text-decoration: none; padding: 4px 8px; font-size: 12px;">View</a>
                                <a href="/sys-yii3/user/update?id=<?= $u->getId() ?>" class="rs-btn rs-btn--sm rs-btn--secondary" style="text-decoration: none; padding: 4px 8px; font-size: 12px;">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Table Footer / Pagination -->
    <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-top: 1px solid var(--rs-border, #E2E8F0); font-size: 14px; color: var(--rs-text-secondary, #7B8A8B);">
        <div>
            Showing <strong><?= count($users) ?></strong> of <strong><?= $total ?></strong> users
        </div>
        <?php if ($totalPages > 1): ?>
            <div style="display: flex; gap: 6px;">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?= $currentPage - 1 ?>&q=<?= urlencode($filter->search) ?>&status=<?= $filter->status ?? '' ?>&role=<?= urlencode($filter->role ?? '') ?>" class="rs-btn rs-btn--sm rs-btn--ghost" style="text-decoration: none;">&laquo; Prev</a>
                <?php endif; ?>
                <span style="display: flex; align-items: center; padding: 0 8px;">Page <?= $currentPage ?> of <?= $totalPages ?></span>
                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?= $currentPage + 1 ?>&q=<?= urlencode($filter->search) ?>&status=<?= $filter->status ?? '' ?>&role=<?= urlencode($filter->role ?? '') ?>" class="rs-btn rs-btn--sm rs-btn--ghost" style="text-decoration: none;">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    </form>
</div>
