<?php

declare(strict_types=1);

use App\Domain\LocationGroup\ConnectedDestination;
use App\Domain\LocationGroup\LocationGroup;
use Yiisoft\Html\Html;

/**
 * Location Groups Index View (Yii 3 Modern Theme)
 * Displays paginated listing of tenant location groups with member destination badges.
 *
 * @var \Yiisoft\View\WebView $this
 * @var LocationGroup[] $groups
 * @var int $total
 * @var int $page
 * @var int $pageSize
 * @var \App\Domain\User\User $currentUser
 * @var \App\Security\TenantContextInterface $context
 * @var \Yiisoft\Session\Flash\FlashInterface|null $flash
 * @var string|null $csrfToken
 * @var string|null $csrf
 */

$effectiveCsrf = $csrfToken ?? $csrf ?? '';
$this->setTitle('Location Groups');

$totalPages = max(1, (int) ceil($total / max(1, $pageSize)));
$currentPage = max(1, min($page, $totalPages));
$startItem = $total > 0 ? (($currentPage - 1) * $pageSize) + 1 : 0;
$endItem = min($total, $currentPage * $pageSize);

// Page Header
$title = 'Location Groups';
$description = 'Organize connected social destinations into groups for streamlined multi-channel social posting.';
$actions = [
    '<a href="' . \App\Environment::url('/location-group/create') . '" class="rs-btn rs-btn--primary"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>Create Group</a>'
];
include __DIR__ . '/../../Shared/Components/_page_header.php';
?>

<?php if ($flash !== null && !empty($flash->getAll())): ?>
    <?php include __DIR__ . '/../../Shared/Components/_alerts.php'; ?>
<?php endif; ?>

<div class="rs-card">
    <div class="rs-card__header rs-d-flex rs-justify-content-between rs-align-items-center">
        <h3 class="rs-card__title">All Location Groups</h3>
        <span class="rs-badge rs-badge--default">
            <?= $total ?> Total <?= $total === 1 ? 'Group' : 'Groups' ?>
        </span>
    </div>

    <div class="rs-table-wrapper">
        <table class="rs-table rs-table--hover" id="location-groups-table" style="width: 100%; margin-bottom: 0;">
            <thead>
                <tr>
                    <th>Group Name</th>
                    <th>Destinations</th>
                    <th style="width: 120px;">Status</th>
                    <th style="width: 160px;">Last Updated</th>
                    <th style="width: 140px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($groups)): ?>
                    <tr>
                        <td colspan="5" class="rs-p-8 rs-text-center" style="padding: 48px 24px; text-align: center;">
                            <div style="width: 56px; height: 56px; border-radius: 50%; background: #f1f5f9; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px; color: #64748b;">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            </div>
                            <h4 class="rs-text-md rs-font-bold rs-mb-1" style="color: #1e293b; margin-bottom: 4px;">No location groups found</h4>
                            <p class="rs-text-sm rs-text-muted rs-mb-4" style="max-width: 400px; margin: 0 auto 16px; color: #64748b; font-size: 13px;">Create your first location group to start organizing your social destinations for streamlined posting.</p>
                            <a href="<?= \App\Environment::url('/location-group/create') ?>" class="rs-btn rs-btn--primary rs-btn--sm">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>Create Group
                            </a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($groups as $group): ?>
                        <?php
                        $destinations = $group->getDestinations();
                        $count = count($destinations);
                        $googleCount = 0;
                        $fbCount = 0;
                        $igCount = 0;

                        foreach ($destinations as $d) {
                            if ($d->getPlatform() === ConnectedDestination::PLATFORM_GOOGLE) {
                                $googleCount++;
                            } elseif ($d->getPlatform() === ConnectedDestination::PLATFORM_FACEBOOK) {
                                $fbCount++;
                            } elseif ($d->getPlatform() === ConnectedDestination::PLATFORM_INSTAGRAM) {
                                $igCount++;
                            }
                        }

                        $updatedTs = $group->getUpdatedAt() ?: $group->getCreatedAt();
                        ?>
                        <tr class="group-row">
                            <td>
                                <a href="<?= \App\Environment::url('/location-group/view?id=' . $group->getId()) ?>" style="font-weight: 600; color: var(--rs-primary, #059669); text-decoration: none; font-size: 0.9375rem;">
                                    <?= Html::encode($group->getName()) ?>
                                </a>
                                <?php if (!empty($group->getDescription())): ?>
                                    <div class="rs-text-xs rs-text-muted rs-mt-1" style="color: #64748b; font-size: 12px; margin-top: 4px; line-height: 1.4;">
                                        <?= Html::encode(mb_strimwidth($group->getDescription(), 0, 80, '...')) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($count === 0): ?>
                                    <span class="rs-badge rs-badge--default" style="font-weight: 500; color: #64748b; background: #f1f5f9;">0 Destinations</span>
                                <?php else: ?>
                                    <div class="rs-d-flex rs-align-items-center" style="display: flex; align-items: center; flex-wrap: wrap; gap: 4px;">
                                        <span class="rs-badge rs-badge--primary" style="font-weight: 600;">
                                            <?= $count ?> <?= $count === 1 ? 'Destination' : 'Destinations' ?>
                                        </span>
                                        <?php if ($googleCount > 0): ?>
                                            <span class="rs-badge" style="background:#e8f0fe; color:#1a73e8; font-weight:600; font-size:11px; padding:2px 7px; border-radius:4px;">G: <?= $googleCount ?></span>
                                        <?php endif; ?>
                                        <?php if ($fbCount > 0): ?>
                                            <span class="rs-badge" style="background:#e7f3ff; color:#1877f2; font-weight:600; font-size:11px; padding:2px 7px; border-radius:4px;">FB: <?= $fbCount ?></span>
                                        <?php endif; ?>
                                        <?php if ($igCount > 0): ?>
                                            <span class="rs-badge" style="background:#fce8f3; color:#d6249f; font-weight:600; font-size:11px; padding:2px 7px; border-radius:4px;">IG: <?= $igCount ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($group->isActive()): ?>
                                    <span class="rs-badge rs-badge--success" style="display: inline-flex; align-items: center; gap: 4px;">
                                        <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: currentColor;"></span> Active
                                    </span>
                                <?php else: ?>
                                    <span class="rs-badge rs-badge--default" style="display: inline-flex; align-items: center; gap: 4px;">
                                        <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: currentColor;"></span> Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="color: #64748b; font-size: 0.875rem;">
                                <?= $updatedTs ? date('M d, Y H:i', $updatedTs) : '—' ?>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="<?= \App\Environment::url('/location-group/view?id=' . $group->getId()) ?>" class="rs-btn rs-btn--secondary rs-btn--xs" title="View" style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; padding: 0; margin-right: 4px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                </a>
                                <a href="<?= \App\Environment::url('/location-group/update?id=' . $group->getId()) ?>" class="rs-btn rs-btn--secondary rs-btn--xs" title="Edit" style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; padding: 0; margin-right: 4px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                </a>
                                <button
                                    type="button"
                                    class="rs-btn rs-btn--danger rs-btn--xs"
                                    title="Delete"
                                    style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; padding: 0;"
                                    onclick="confirmDeleteGroup(<?= $group->getId() ?>, '<?= Html::encode(addslashes($group->getName())) ?>')"
                                >
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="rs-table-footer rs-d-flex rs-align-items-center rs-justify-content-between rs-p-4" style="padding: 12px 20px; border-top: 1px solid var(--rs-border, #E2E8F0); display: flex; justify-content: space-between; align-items: center;">
            <div class="rs-text-muted rs-font-size-sm" style="color: #64748b; font-size: 13px;">
                Showing <?= $startItem ?> to <?= $endItem ?> of <?= $total ?> groups
            </div>
            <div class="rs-d-flex rs-gap-1" style="display: flex; gap: 4px;">
                <?php if ($currentPage > 1): ?>
                    <a href="<?= \App\Environment::url('/location-group/index?page=' . ($currentPage - 1)) ?>" class="rs-btn rs-btn--sm rs-btn--outline">Previous</a>
                <?php endif; ?>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a href="<?= \App\Environment::url('/location-group/index?page=' . $p) ?>" class="rs-btn rs-btn--sm <?= $p === $currentPage ? 'rs-btn--primary' : 'rs-btn--outline' ?>">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>
                <?php if ($currentPage < $totalPages): ?>
                    <a href="<?= \App\Environment::url('/location-group/index?page=' . ($currentPage + 1)) ?>" class="rs-btn rs-btn--sm rs-btn--outline">Next</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
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
            <p class="rs-text-muted" style="font-size: 13px; margin: 0; color: #64748b;">
                This action only deletes the group association. Connected social accounts and locations will remain intact.
            </p>
        </div>
        <div class="rs-card__footer rs-d-flex rs-justify-content-end rs-gap-2" style="padding: 12px 20px; border-top: 1px solid var(--rs-border, #E2E8F0); background: #F8FAFC; display: flex; justify-content: flex-end; gap: 8px;">
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
