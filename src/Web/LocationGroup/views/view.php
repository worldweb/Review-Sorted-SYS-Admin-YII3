<?php

declare(strict_types=1);

use App\Domain\LocationGroup\ConnectedDestination;
use App\Domain\LocationGroup\LocationGroup;
use Yiisoft\Html\Html;

/**
 * Location Group View (Yii 3 Modern Theme)
 * Displays detailed information about a single Location Group and all its member social destinations.
 *
 * @var \Yiisoft\View\WebView $this
 * @var LocationGroup $model
 * @var \App\Domain\User\User $currentUser
 * @var \App\Security\TenantContextInterface $context
 * @var \Yiisoft\Session\Flash\FlashInterface|null $flash
 * @var string|null $csrfToken
 * @var string|null $csrf
 */

$effectiveCsrf = $csrfToken ?? $csrf ?? '';
$this->setTitle($model->getName() . ' — Location Group');

$destinations = $model->getDestinations();
$totalDests = count($destinations);

// Page Header
$title = $model->getName();
$description = 'Location group details and assigned social destinations.';
$actions = [
    '<a href="' . \App\Environment::url('/location-group/update?id=' . $model->getId()) . '" class="rs-btn rs-btn--primary"><i class="fa-solid fa-pencil rs-mr-1"></i> Edit Group</a>',
    '<button type="button" class="rs-btn rs-btn--outline-danger" onclick="confirmDeleteGroup(' . $model->getId() . ', \'' . Html::encode(addslashes($model->getName())) . '\')"><i class="fa-solid fa-trash rs-mr-1"></i> Delete</button>',
    '<a href="' . \App\Environment::url('/location-group/index') . '" class="rs-btn rs-btn--outline"><i class="fa-solid fa-arrow-left rs-mr-1"></i> Back to Groups</a>',
];
include __DIR__ . '/../../Shared/Components/_page_header.php';
?>

<?php if ($flash !== null && !empty($flash->getAll())): ?>
    <?php include __DIR__ . '/../../Shared/Components/_alerts.php'; ?>
<?php endif; ?>

<div class="rs-grid rs-grid--cols-1 rs-grid--cols-lg-3 rs-gap-6 rs-mb-6">
    <!-- Group Metadata Card -->
    <div class="rs-col-span-1">
        <div class="rs-card" style="height: 100%;">
            <div class="rs-card__header" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                <h3 style="margin: 0; font-size: 15px; font-weight: 600;">
                    <i class="fa-solid fa-circle-info rs-mr-1" style="color: #64748B;"></i> Group Summary
                </h3>
            </div>
            <div class="rs-card__body" style="padding: 20px;">
                <div style="margin-bottom: 16px;">
                    <span class="rs-text-muted" style="font-size: 12px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Group Name</span>
                    <div style="font-size: 16px; font-weight: 600; color: #0F172A; margin-top: 4px;"><?= Html::encode($model->getName()) ?></div>
                </div>

                <div style="margin-bottom: 16px;">
                    <span class="rs-text-muted" style="font-size: 12px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Account / Brand</span>
                    <div style="font-size: 14px; font-weight: 500; color: #334155; margin-top: 4px;">
                        <?= Html::encode($model->getAccountBusinessName() ?? 'Account #' . $model->getAccountId()) ?>
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <span class="rs-text-muted" style="font-size: 12px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Status</span>
                    <div style="margin-top: 4px;">
                        <?php if ($model->isActive()): ?>
                            <span class="rs-badge rs-badge--success">Active</span>
                        <?php else: ?>
                            <span class="rs-badge rs-badge--neutral">Inactive</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <span class="rs-text-muted" style="font-size: 12px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Description</span>
                    <div style="font-size: 14px; color: #475569; margin-top: 4px; line-height: 1.5;">
                        <?= !empty($model->getDescription()) ? nl2br(Html::encode($model->getDescription())) : '<span class="rs-text-muted">No description provided.</span>' ?>
                    </div>
                </div>

                <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-pt-3" style="border-top: 1px solid var(--rs-border, #E2E8F0); font-size: 12px; color: #94A3B8;">
                    <span>Created: <?= date('d M Y, H:i', $model->getCreatedAt()) ?></span>
                    <span>Updated: <?= date('d M Y, H:i', $model->getUpdatedAt()) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Member Destinations Card -->
    <div class="rs-col-span-2">
        <div class="rs-card" style="height: 100%;">
            <div class="rs-card__header rs-d-flex rs-justify-content-between rs-align-items-center" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                <div class="rs-d-flex rs-align-items-center rs-gap-2">
                    <h3 style="margin: 0; font-size: 15px; font-weight: 600;">
                        <i class="fa-solid fa-globe rs-mr-1" style="color: #64748B;"></i> Member Destinations
                    </h3>
                    <span class="rs-badge rs-badge--primary" style="font-weight: 600; font-size: 12px;">
                        <?= $totalDests ?> <?= $totalDests === 1 ? 'Destination' : 'Destinations' ?>
                    </span>
                </div>
            </div>

            <div class="rs-card__body" style="padding: 0;">
                <?php if (empty($destinations)): ?>
                    <div class="rs-text-center" style="padding: 40px 20px;">
                        <i class="fa-solid fa-triangle-exclamation" style="font-size: 32px; color: #F59E0B; margin-bottom: 12px;"></i>
                        <p style="margin: 0 0 8px; font-size: 15px; font-weight: 500; color: #1E293B;">No social destinations assigned</p>
                        <p class="rs-text-muted" style="margin: 0 0 16px; font-size: 13px;">This group currently has no active member locations or pages.</p>
                        <a href="<?= \App\Environment::url('/location-group/update?id=' . $model->getId()) ?>" class="rs-btn rs-btn--sm rs-btn--primary">
                            Assign Destinations
                        </a>
                    </div>
                <?php else: ?>
                    <div class="rs-table-responsive">
                        <table class="rs-table" style="width: 100%; margin-bottom: 0;">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th style="width: 150px;">Platform</th>
                                    <th>Destination Name</th>
                                    <th>Account / Brand</th>
                                    <th style="width: 110px; text-align: center;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $idx = 1; ?>
                                <?php foreach ($destinations as $dest): ?>
                                    <tr>
                                        <td><span class="rs-text-muted" style="font-size: 12px;"><?= $idx++ ?></span></td>
                                        <td>
                                            <?php if ($dest->getPlatform() === ConnectedDestination::PLATFORM_GOOGLE): ?>
                                                <div class="rs-d-flex rs-align-items-center rs-gap-1">
                                                    <i class="fa-brands fa-google" style="color: #4285F4; font-size: 14px;"></i>
                                                    <strong style="font-size: 13px; color: #1E293B;">Google</strong>
                                                </div>
                                            <?php elseif ($dest->getPlatform() === ConnectedDestination::PLATFORM_FACEBOOK): ?>
                                                <div class="rs-d-flex rs-align-items-center rs-gap-1">
                                                    <i class="fa-brands fa-facebook" style="color: #1877F2; font-size: 14px;"></i>
                                                    <strong style="font-size: 13px; color: #1E293B;">Facebook</strong>
                                                </div>
                                            <?php elseif ($dest->getPlatform() === ConnectedDestination::PLATFORM_INSTAGRAM): ?>
                                                <div class="rs-d-flex rs-align-items-center rs-gap-1">
                                                    <i class="fa-brands fa-instagram" style="color: #E1306C; font-size: 14px;"></i>
                                                    <strong style="font-size: 13px; color: #1E293B;">Instagram</strong>
                                                </div>
                                            <?php else: ?>
                                                <strong style="font-size: 13px;"><?= Html::encode(ucfirst($dest->getPlatform())) ?></strong>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="font-weight: 500; font-size: 13px; color: #0F172A;">
                                                <?= Html::encode($dest->getLabel()) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="rs-text-muted" style="font-size: 13px;">
                                                <?= Html::encode($dest->getAccountName() ?? '—') ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if ($dest->isLinked()): ?>
                                                <span class="rs-badge rs-badge--success" style="font-size: 11px;">Connected</span>
                                            <?php else: ?>
                                                <span class="rs-badge rs-badge--danger" style="font-size: 11px;">Disconnected</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Group Deletion Confirmation -->
<div id="delete-group-modal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 9999; align-items: center; justify-content: center;">
    <div class="rs-card" style="width: 100%; max-width: 440px; margin: 20px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
        <div class="rs-card__header" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #E2E8F0);">
            <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #DC2626;">Confirm Deletion</h3>
        </div>
        <div class="rs-card__body" style="padding: 20px;">
            <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.5; color: #475569;">
                Are you sure you want to delete location group <strong id="delete-group-name" style="color: #0F172A;"></strong>?
            </p>
            <p class="rs-text-muted" style="font-size: 13px; margin: 0;">
                This action only deletes the group association. Connected social accounts and locations will remain intact.
            </p>
        </div>
        <div class="rs-card__footer rs-d-flex rs-justify-content-end rs-gap-2" style="padding: 12px 20px; border-top: 1px solid var(--rs-border, #E2E8F0); background: #F8FAFC;">
            <button type="button" class="rs-btn rs-btn--sm rs-btn--outline" onclick="closeDeleteModal()">Cancel</button>
            <form id="delete-group-form" method="POST" action="<?= \App\Environment::url('/location-group/delete') ?>" style="margin: 0;">
                <input type="hidden" name="_csrf" value="<?= Html::encode($effectiveCsrf) ?>">
                <input type="hidden" name="id" id="delete-group-id" value="">
                <button type="submit" class="rs-btn rs-btn--sm rs-btn--danger">Delete Group</button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDeleteGroup(id, name) {
    document.getElementById('delete-group-id').value = id;
    document.getElementById('delete-group-name').textContent = '"' + name + '"';
    const modal = document.getElementById('delete-group-modal');
    modal.style.display = 'flex';
}

function closeDeleteModal() {
    const modal = document.getElementById('delete-group-modal');
    modal.style.display = 'none';
}
</script>
