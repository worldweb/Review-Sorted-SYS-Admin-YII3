<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Workspace View (Yii 3 Modern Theme)
 * Rendered within Main Authenticated Layout.
 *
 * @var \App\Domain\Account\Account $account
 * @var \App\Domain\User\User[] $members
 * @var array $billingData
 * @var \App\Domain\User\User $currentUser
 * @var Yiisoft\View\WebView $this
 */

$businessName = $account->getBusinessName() ?? "Workspace #{$account->getId()}";
$this->setTitle($businessName);
$statusClass = $account->isActive() ? 'rs-badge--success' : 'rs-badge--danger';

$url = static fn(string $p): string => \App\Environment::url($p);

// Header Actions
$actionButtons = [
    '<a href="' . $url('/accounts') . '" class="rs-btn rs-btn--ghost">Back</a>',
    '<a href="' . $url('/accounts/update?id=' . $account->getId()) . '" class="rs-btn rs-btn--primary">Edit Workspace</a>',
];
if ($currentUser->isAdmin()) {
    $actionButtons[] = '<a href="' . $url('/accounts/delete?id=' . $account->getId()) . '" class="rs-btn rs-btn--danger" onclick="return confirm(\'Are you sure you want to delete this workspace?\');">Delete</a>';
}

$title = $businessName;
$description = $account->getStatusName() . ' • ' . $account->getMaxUsers() . ' allowed seats';
$actions = $actionButtons;
include __DIR__ . '/../../Shared/Components/_page_header.php';
?>

<!-- Workspace Details Table -->
<div class="rs-card rs-mb-6" style="margin-bottom: 28px;">
    <div class="rs-card__header" style="padding: 16px 24px; border-bottom: 1px solid var(--rs-border, #E2E8F0);">
        <h2 class="rs-card__title" style="margin: 0; font-size: 18px; font-weight: 600; color: var(--rs-primary, #234137);">Workspace Details</h2>
    </div>
    <div class="rs-card__body rs-p-0">
        <table class="rs-table rs-table--striped" style="width: 100%; border-collapse: collapse;">
            <tbody>
                <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                    <th style="width: 220px; text-align: left; padding: 14px 24px; color: var(--rs-text-secondary, #7B8A8B); font-size: 14px;">Workspace ID</th>
                    <td style="padding: 14px 24px; font-size: 14px;"><?= $account->getId() ?></td>
                </tr>
                <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0); background: rgba(0,0,0,0.015);">
                    <th style="width: 220px; text-align: left; padding: 14px 24px; color: var(--rs-text-secondary, #7B8A8B); font-size: 14px;">Business Name</th>
                    <td style="padding: 14px 24px; font-size: 14px; font-weight: 600;"><?= Html::encode($account->getBusinessName() ?? '-') ?></td>
                </tr>
                <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                    <th style="width: 220px; text-align: left; padding: 14px 24px; color: var(--rs-text-secondary, #7B8A8B); font-size: 14px;">Primary Contact</th>
                    <td style="padding: 14px 24px; font-size: 14px;"><?= Html::encode(trim(($account->getFirstName() ?? '') . ' ' . ($account->getLastName() ?? '')) ?: '-') ?></td>
                </tr>
                <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0); background: rgba(0,0,0,0.015);">
                    <th style="width: 220px; text-align: left; padding: 14px 24px; color: var(--rs-text-secondary, #7B8A8B); font-size: 14px;">Email Address</th>
                    <td style="padding: 14px 24px; font-size: 14px;"><?= Html::encode($account->getEmail() ?? '-') ?></td>
                </tr>
                <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                    <th style="width: 220px; text-align: left; padding: 14px 24px; color: var(--rs-text-secondary, #7B8A8B); font-size: 14px;">Contact Phone</th>
                    <td style="padding: 14px 24px; font-size: 14px;"><?= Html::encode($account->getContactNumber() ?? '-') ?></td>
                </tr>
                <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0); background: rgba(0,0,0,0.015);">
                    <th style="width: 220px; text-align: left; padding: 14px 24px; color: var(--rs-text-secondary, #7B8A8B); font-size: 14px;">Website</th>
                    <td style="padding: 14px 24px; font-size: 14px;">
                        <?php if ($account->getWebsite()): ?>
                            <a href="<?= Html::encode($account->getWebsite()) ?>" target="_blank" rel="noopener noreferrer" style="color: var(--rs-primary, #234137);"><?= Html::encode($account->getWebsite()) ?></a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                    <th style="width: 220px; text-align: left; padding: 14px 24px; color: var(--rs-text-secondary, #7B8A8B); font-size: 14px;">Status</th>
                    <td style="padding: 14px 24px; font-size: 14px;">
                        <span class="rs-badge <?= $statusClass ?>" style="padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                            <?= Html::encode($account->getStatusName()) ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th style="width: 220px; text-align: left; padding: 14px 24px; color: var(--rs-text-secondary, #7B8A8B); font-size: 14px;">Created At</th>
                    <td style="padding: 14px 24px; font-size: 14px; color: var(--rs-text-secondary, #7B8A8B);"><?= $account->getCreatedAtStr('M j, Y H:i') ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Assigned Team Members -->
<div class="rs-card">
    <div class="rs-card__header" style="padding: 16px 24px; border-bottom: 1px solid var(--rs-border, #E2E8F0); display: flex; justify-content: space-between; align-items: center;">
        <h2 class="rs-card__title" style="margin: 0; font-size: 18px; font-weight: 600; color: var(--rs-primary, #234137);">Team Members (<?= count($members) ?> / <?= $account->getMaxUsers() ?> seats)</h2>
        <a href="<?= $url('/user/create-worker') ?>" class="rs-btn rs-btn--sm rs-btn--primary" style="text-decoration: none;">+ Add Member</a>
    </div>
    <div class="rs-table-wrapper" style="overflow-x: auto;">
        <table class="rs-table" style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="background: rgba(0,0,0,0.02);">
                    <th style="padding: 12px 24px;">Name</th>
                    <th style="padding: 12px 24px;">Email</th>
                    <th style="padding: 12px 24px;">Role</th>
                    <th style="padding: 12px 24px;">Status</th>
                    <th style="padding: 12px 24px; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($members)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 24px; color: var(--rs-text-secondary, #7B8A8B);">
                            No users currently assigned to this workspace.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($members as $m): ?>
                        <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                            <td style="padding: 12px 24px; font-weight: 600;">
                                <a href="<?= $url('/user/view?id=' . $m->getId()) ?>" style="color: var(--rs-primary, #234137); text-decoration: none;">
                                    <?= Html::encode($m->getName()) ?>
                                </a>
                            </td>
                            <td style="padding: 12px 24px; color: var(--rs-text-secondary, #7B8A8B);"><?= Html::encode($m->getEmail()) ?></td>
                            <td style="padding: 12px 24px;"><?= Html::encode($m->getRoleLabel()) ?></td>
                            <td style="padding: 12px 24px;">
                                <span class="rs-badge <?= $m->isActive() ? 'rs-badge--success' : 'rs-badge--danger' ?>" style="padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                    <?= Html::encode($m->getStatusName()) ?>
                                </span>
                            </td>
                            <td style="padding: 12px 24px; text-align: right;">
                                <a href="<?= $url('/user/view?id=' . $m->getId()) ?>" class="rs-btn rs-btn--sm rs-btn--ghost" style="text-decoration: none; font-size: 12px;">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
