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
?>

<div class="rs-page">
    <div class="rs-page-header rs-d-flex rs-align-items-center rs-justify-content-between rs-mb-6">
        <div class="rs-d-flex rs-align-items-center rs-gap-4">
            <div class="rs-avatar rs-avatar--lg" style="width: 48px; height: 48px; border-radius: 50%; background: var(--rs-primary, #234137); color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: bold; flex-shrink: 0;">
                <?= Html::encode(strtoupper(substr($user->getName(), 0, 1))) ?>
            </div>
            <div>
                <h1 class="rs-page-title rs-m-0"><?= Html::encode($user->getName()) ?></h1>
                <div class="rs-d-flex rs-align-items-center rs-gap-2 rs-mt-1">
                    <span class="rs-text-muted"><?= Html::encode($user->getEmail()) ?></span>
                    <span class="rs-text-muted">&bull;</span>
                    <span class="rs-badge <?= $statusClass ?>"><?= Html::encode($user->getStatusName()) ?></span>
                </div>
            </div>
        </div>
        <div class="rs-page-header__actions rs-d-flex rs-gap-3">
            <a href="/sys-yii3/users" class="rs-btn rs-btn--ghost">Back to Users</a>
            <?php if ($canEdit): ?>
                <a href="/sys-yii3/user/update?id=<?= $user->getId() ?>" class="rs-btn rs-btn--primary">Update</a>
            <?php endif; ?>
            <?php if ($canDelete): ?>
                <a href="/sys-yii3/user/delete?id=<?= $user->getId() ?>" class="rs-btn rs-btn--danger" onclick="return confirm('Are you sure you want to permanently delete this user?');">Delete</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Details Card -->
    <div class="rs-card">
        <div class="rs-card__header">
            <h2 class="rs-card__title">User Details</h2>
        </div>
        <div class="rs-card__body rs-p-0">
            <table class="rs-table rs-table--striped" style="width: 100%; border-collapse: collapse;">
                <tbody>
                    <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                        <th style="width: 220px; text-align: left; padding: 14px 24px; color: var(--rs-text-muted, #64748b); font-size: 14px; font-weight: 600;">ID</th>
                        <td style="padding: 14px 24px; font-size: 14px;"><?= $user->getId() ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0); background: rgba(0,0,0,0.015);">
                        <th style="width: 220px; text-align: left; padding: 14px 24px; color: var(--rs-text-muted, #64748b); font-size: 14px; font-weight: 600;">Name</th>
                        <td style="padding: 14px 24px; font-size: 14px; font-weight: 600;"><?= Html::encode($user->getName()) ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                        <th style="width: 220px; text-align: left; padding: 14px 24px; color: var(--rs-text-muted, #64748b); font-size: 14px; font-weight: 600;">Email</th>
                        <td style="padding: 14px 24px; font-size: 14px;"><?= Html::encode($user->getEmail()) ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0); background: rgba(0,0,0,0.015);">
                        <th style="width: 220px; text-align: left; padding: 14px 24px; color: var(--rs-text-muted, #64748b); font-size: 14px; font-weight: 600;">Role</th>
                        <td style="padding: 14px 24px; font-size: 14px;"><?= Html::encode($user->getRoleLabel()) ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                        <th style="width: 220px; text-align: left; padding: 14px 24px; color: var(--rs-text-muted, #64748b); font-size: 14px; font-weight: 600;">Status</th>
                        <td style="padding: 14px 24px; font-size: 14px;">
                            <span class="rs-badge <?= $statusClass ?>">
                                <?= Html::encode($user->getStatusName()) ?>
                            </span>
                        </td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0); background: rgba(0,0,0,0.015);">
                        <th style="width: 220px; text-align: left; padding: 14px 24px; color: var(--rs-text-muted, #64748b); font-size: 14px; font-weight: 600;">Workspace / Account</th>
                        <td style="padding: 14px 24px; font-size: 14px;">
                            <?php if ($account !== null): ?>
                                <a href="/sys-yii3/accounts/view?id=<?= $account->getId() ?>" style="color: var(--rs-primary, #234137); text-decoration: none; font-weight: 600;">
                                    <?= Html::encode($account->getBusinessName()) ?> (#<?= $account->getId() ?>)
                                </a>
                            <?php elseif ($user->getAccountId() !== null): ?>
                                Account #<?= $user->getAccountId() ?>
                            <?php else: ?>
                                <span class="rs-text-muted">— Global System Administrator (No workspace restriction) —</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                        <th style="width: 220px; text-align: left; padding: 14px 24px; color: var(--rs-text-muted, #64748b); font-size: 14px; font-weight: 600;">Created At</th>
                        <td style="padding: 14px 24px; font-size: 14px; color: var(--rs-text-muted, #64748b);"><?= Html::encode($user->getCreatedAtStr()) ?></td>
                    </tr>
                    <tr>
                        <th style="width: 220px; text-align: left; padding: 14px 24px; color: var(--rs-text-muted, #64748b); font-size: 14px; font-weight: 600;">Updated At</th>
                        <td style="padding: 14px 24px; font-size: 14px; color: var(--rs-text-muted, #64748b);"><?= Html::encode($user->getUpdatedAtStr()) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
