<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * User View (Yii 3 Modern Theme)
 * Rendered within Main Authenticated Layout.
 *
 * @var \App\Domain\User\User $user
 * @var \App\Domain\Account\Account|null $account
 * @var \App\Domain\User\User $currentUser
 * @var bool $canEdit
 * @var bool $canDelete
 * @var Yiisoft\View\WebView $this
 */

$this->setTitle($user->getName());
$statusClass = $user->isActive() ? 'rs-badge--success' : 'rs-badge--danger';

// Header Actions
$actionButtons = [
    '<a href="/sys-yii3/users" class="rs-btn rs-btn--ghost">Back to Users</a>',
];
if ($canEdit) {
    $actionButtons[] = '<a href="/sys-yii3/user/update?id=' . $user->getId() . '" class="rs-btn rs-btn--primary">Update User</a>';
}
if ($canDelete) {
    $actionButtons[] = '<a href="/sys-yii3/user/delete?id=' . $user->getId() . '" class="rs-btn rs-btn--danger" onclick="return confirm(\'Are you sure you want to delete this user?\');">Delete</a>';
}

$title = $user->getName();
$description = $user->getEmail() . ' • ' . $user->getRoleLabel() . ' • ' . $user->getStatusName();
$actions = $actionButtons;
include __DIR__ . '/../../Shared/Components/_page_header.php';
?>

<!-- Details Card -->
<div class="rs-card">
    <div class="rs-card__header" style="padding: 16px 24px; border-bottom: 1px solid var(--rs-border, #E2E8F0);">
        <h2 class="rs-card__title" style="margin: 0; font-size: 18px; font-weight: 600; color: var(--rs-primary, #234137);">User Details</h2>
    </div>
    <div class="rs-card__body rs-p-0">
        <table class="rs-table rs-table--striped" style="width: 100%; border-collapse: collapse;">
            <tbody>
                <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                    <th style="width: 220px; text-align: left; padding: 14px 24px; color: var(--rs-text-secondary, #7B8A8B); font-size: 14px; font-weight: 600;">ID</th>
                    <td style="padding: 14px 24px; font-size: 14px;"><?= $user->getId() ?></td>
                </tr>
                <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0); background: rgba(0,0,0,0.015);">
                    <th style="width: 220px; text-align: left; padding: 14px 24px; color: var(--rs-text-secondary, #7B8A8B); font-size: 14px; font-weight: 600;">Name</th>
                    <td style="padding: 14px 24px; font-size: 14px; font-weight: 600;"><?= Html::encode($user->getName()) ?></td>
                </tr>
                <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                    <th style="width: 220px; text-align: left; padding: 14px 24px; color: var(--rs-text-secondary, #7B8A8B); font-size: 14px; font-weight: 600;">Email</th>
                    <td style="padding: 14px 24px; font-size: 14px;"><?= Html::encode($user->getEmail()) ?></td>
                </tr>
                <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0); background: rgba(0,0,0,0.015);">
                    <th style="width: 220px; text-align: left; padding: 14px 24px; color: var(--rs-text-secondary, #7B8A8B); font-size: 14px; font-weight: 600;">Role</th>
                    <td style="padding: 14px 24px; font-size: 14px;"><?= Html::encode($user->getRoleLabel()) ?></td>
                </tr>
                <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                    <th style="width: 220px; text-align: left; padding: 14px 24px; color: var(--rs-text-secondary, #7B8A8B); font-size: 14px; font-weight: 600;">Status</th>
                    <td style="padding: 14px 24px; font-size: 14px;">
                        <span class="rs-badge <?= $statusClass ?>" style="padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                            <?= Html::encode($user->getStatusName()) ?>
                        </span>
                    </td>
                </tr>
                <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0); background: rgba(0,0,0,0.015);">
                    <th style="width: 220px; text-align: left; padding: 14px 24px; color: var(--rs-text-secondary, #7B8A8B); font-size: 14px; font-weight: 600;">Workspace / Account</th>
                    <td style="padding: 14px 24px; font-size: 14px;">
                        <?php if ($account !== null): ?>
                            <a href="/sys-yii3/accounts/view?id=<?= $account->getId() ?>" style="color: var(--rs-primary, #234137); font-weight: 600; text-decoration: none;">
                                <?= Html::encode($account->getBusinessName() ?? "Account #{$account->getId()}") ?>
                            </a>
                        <?php elseif ($user->getAccountId() !== null): ?>
                            Account #<?= $user->getAccountId() ?>
                        <?php else: ?>
                            <span style="color: var(--rs-text-secondary, #7B8A8B);">System Platform (Global)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                    <th style="width: 220px; text-align: left; padding: 14px 24px; color: var(--rs-text-secondary, #7B8A8B); font-size: 14px; font-weight: 600;">Created At</th>
                    <td style="padding: 14px 24px; font-size: 14px; color: var(--rs-text-secondary, #7B8A8B);"><?= $user->getCreatedAtStr('M j, Y H:i') ?></td>
                </tr>
                <tr>
                    <th style="width: 220px; text-align: left; padding: 14px 24px; color: var(--rs-text-secondary, #7B8A8B); font-size: 14px; font-weight: 600;">Updated At</th>
                    <td style="padding: 14px 24px; font-size: 14px; color: var(--rs-text-secondary, #7B8A8B);"><?= $user->getUpdatedAt() ? date('M j, Y H:i', $user->getUpdatedAt()) : '-' ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
