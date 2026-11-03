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
    $actionButtons[] = '<a href="/sys-yii3/user/create" class="rs-btn rs-btn--ghost">+ Add Admin</a>';
    $actionButtons[] = '<a href="/sys-yii3/user/create-acc-admin" class="rs-btn rs-btn--secondary">+ Add Account Admin</a>';
} elseif ($canAddWorker) {
    $actionButtons[] = '<a href="/sys-yii3/user/create-worker" class="rs-btn rs-btn--primary">+ Add User</a>';
}

$title = 'Users';
$description = 'Manage platform administrators, account managers, and standard users.';
$actions = $actionButtons;
include __DIR__ . '/../../Shared/Components/_page_header.php';
?>

<!-- Filter Form -->
<div class="rs-card rs-mb-4" style="margin-bottom: 20px; padding: 16px 20px;">
    <form method="get" action="/sys-yii3/users" style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end;">
        <div style="flex: 1; min-width: 220px;">
            <label for="filter-q" class="rs-label" style="font-size: 12px; margin-bottom: 4px;">Search Name or Email</label>
            <input type="text" id="filter-q" name="q" class="rs-input" value="<?= Html::encode($filter->search) ?>" placeholder="Search users...">
        </div>

        <div style="width: 160px;">
            <label for="filter-status" class="rs-label" style="font-size: 12px; margin-bottom: 4px;">Status</label>
            <select id="filter-status" name="status" class="rs-input" style="height: 44px;">
                <option value="">All Statuses</option>
                <option value="1" <?= $filter->status === 1 ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= $filter->status === 0 ? 'selected' : '' ?>>Disabled</option>
            </select>
        </div>

        <div style="width: 180px;">
            <label for="filter-role" class="rs-label" style="font-size: 12px; margin-bottom: 4px;">Role</label>
            <select id="filter-role" name="role" class="rs-input" style="height: 44px;">
                <option value="">All Roles</option>
                <option value="super" <?= $filter->role === 'super' ? 'selected' : '' ?>>Administrator (root)</option>
                <option value="admin" <?= $filter->role === 'admin' ? 'selected' : '' ?>>Administrator</option>
                <option value="account_admin" <?= $filter->role === 'account_admin' ? 'selected' : '' ?>>Account Administrator</option>
                <option value="worker" <?= $filter->role === 'worker' ? 'selected' : '' ?>>User</option>
            </select>
        </div>

        <?php if (!empty($accountsList)): ?>
            <div style="width: 200px;">
                <label for="filter-account" class="rs-label" style="font-size: 12px; margin-bottom: 4px;">Workspace / Account</label>
                <select id="filter-account" name="account_id" class="rs-input" style="height: 44px;">
                    <option value="">All Workspaces</option>
                    <?php foreach ($accountsList as $accId => $accName): ?>
                        <option value="<?= $accId ?>" <?= $filter->accountId === $accId ? 'selected' : '' ?>>
                            <?= Html::encode($accName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="rs-btn rs-btn--primary" style="height: 44px;">Filter</button>
            <a href="/sys-yii3/users" class="rs-btn rs-btn--ghost" style="height: 44px; display: inline-flex; align-items: center; text-decoration: none;">Reset</a>
        </div>
    </form>
</div>

<!-- Data Grid -->
<div class="rs-card">
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
</div>
