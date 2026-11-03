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
$description = 'Organize connected Google, Facebook, and Instagram destinations into logical operational groups.';
$actions = [
    '<a href="/sys-yii3/location-group/create" class="rs-btn rs-btn--primary"><i class="fa-solid fa-plus rs-mr-1"></i> Create Location Group</a>'
];
include __DIR__ . '/../../Shared/Components/_page_header.php';
?>

<?php if ($flash !== null && !empty($flash->getAll())): ?>
    <?php include __DIR__ . '/../../Shared/Components/_alerts.php'; ?>
<?php endif; ?>

<div class="rs-card">
    <div class="rs-card__header rs-d-flex rs-justify-content-between rs-align-items-center rs-flex-wrap rs-gap-3" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #E2E8F0);">
        <div class="rs-d-flex rs-align-items-center rs-gap-2">
            <span class="rs-badge rs-badge--neutral" style="font-size: 13px; font-weight: 600;">
                <?= $total ?> <?= $total === 1 ? 'Group' : 'Groups' ?>
            </span>
        </div>
        <div class="rs-d-flex rs-align-items-center rs-gap-2">
            <input
                type="text"
                id="group-filter-input"
                class="rs-input"
                placeholder="Filter visible groups..."
                style="width: 250px; max-width: 100%;"
                onkeyup="filterLocationGroupsTable()"
            >
        </div>
    </div>

    <div class="rs-table-responsive">
        <table class="rs-table" id="location-groups-table" style="width: 100%; margin-bottom: 0;">
            <thead>
                <tr>
                    <th style="min-width: 200px;">Group Name</th>
                    <?php if ($context->isUnrestricted()): ?>
                        <th style="min-width: 150px;">Account / Brand</th>
                    <?php endif; ?>
                    <th>Description</th>
                    <th style="min-width: 220px;">Destinations</th>
                    <th style="width: 100px;">Status</th>
                    <th style="width: 120px;">Created</th>
                    <th style="width: 140px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($groups)): ?>
                    <tr>
                        <td colspan="<?= $context->isUnrestricted() ? 7 : 6 ?>" class="rs-text-center rs-py-6">
                            <div class="rs-text-muted" style="padding: 30px 0;">
                                <i class="fa-solid fa-map-location-dot" style="font-size: 36px; margin-bottom: 12px; color: #94A3B8;"></i>
                                <p class="rs-mb-2" style="font-size: 15px; font-weight: 500;">No location groups found</p>
                                <a href="/sys-yii3/location-group/create" class="rs-btn rs-btn--sm rs-btn--primary">Create your first group</a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($groups as $group): ?>
                        <?php
                        $destinations = $group->getDestinations();
                        $totalDests = count($destinations);
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
                        ?>
                        <tr class="group-row" data-name="<?= Html::encode(strtolower($group->getName() . ' ' . ($group->getDescription() ?? ''))) ?>">
                            <td>
                                <a href="/sys-yii3/location-group/view?id=<?= $group->getId() ?>" style="font-weight: 600; font-size: 14px; color: var(--rs-primary, #0F172A); text-decoration: none;">
                                    <?= Html::encode($group->getName()) ?>
                                </a>
                            </td>
                            <?php if ($context->isUnrestricted()): ?>
                                <td>
                                    <span class="rs-text-muted" style="font-size: 13px;">
                                        <?= Html::encode($group->getAccountBusinessName() ?? 'Account #' . $group->getAccountId()) ?>
                                    </span>
                                </td>
                            <?php endif; ?>
                            <td>
                                <span class="rs-text-muted" style="font-size: 13px;">
                                    <?= !empty($group->getDescription()) ? Html::encode(mb_strimwidth($group->getDescription(), 0, 75, '...')) : '—' ?>
                                </span>
                            </td>
                            <td>
                                <div class="rs-d-flex rs-align-items-center rs-flex-wrap rs-gap-1">
                                    <span class="rs-badge" style="background: #3B82F6; color: #fff; font-weight: 600; font-size: 11px;">
                                        <?= $totalDests ?> Total
                                    </span>
                                    <?php if ($googleCount > 0): ?>
                                        <span class="rs-badge" style="background: #4285F4; color: #fff; font-weight: 600; font-size: 11px;" title="Google Business Profile">
                                            G: <?= $googleCount ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($fbCount > 0): ?>
                                        <span class="rs-badge" style="background: #1877F2; color: #fff; font-weight: 600; font-size: 11px;" title="Facebook Pages">
                                            FB: <?= $fbCount ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($igCount > 0): ?>
                                        <span class="rs-badge" style="background: #E1306C; color: #fff; font-weight: 600; font-size: 11px;" title="Instagram Business">
                                            IG: <?= $igCount ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($group->isActive()): ?>
                                    <span class="rs-badge rs-badge--success">Active</span>
                                <?php else: ?>
                                    <span class="rs-badge rs-badge--neutral">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="rs-text-muted" style="font-size: 12px;">
                                    <?= date('d M Y', $group->getCreatedAt()) ?>
                                </span>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <div class="rs-d-flex rs-justify-content-end rs-gap-1">
                                    <a href="/sys-yii3/location-group/view?id=<?= $group->getId() ?>" class="rs-btn rs-btn--xs rs-btn--outline" title="View details">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="/sys-yii3/location-group/update?id=<?= $group->getId() ?>" class="rs-btn rs-btn--xs rs-btn--outline-primary" title="Edit group">
                                        <i class="fa-solid fa-pencil"></i>
                                    </a>
                                    <button
                                        type="button"
                                        class="rs-btn rs-btn--xs rs-btn--outline-danger"
                                        title="Delete group"
                                        onclick="confirmDeleteGroup(<?= $group->getId() ?>, '<?= Html::encode(addslashes($group->getName())) ?>')"
                                    >
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="rs-card__footer rs-d-flex rs-justify-content-between rs-align-items-center rs-flex-wrap rs-gap-3" style="padding: 12px 20px; border-top: 1px solid var(--rs-border, #E2E8F0);">
            <div class="rs-text-muted rs-font-size-sm">
                Showing <?= $startItem ?> to <?= $endItem ?> of <?= $total ?> groups
            </div>
            <div class="rs-d-flex rs-gap-1">
                <?php if ($currentPage > 1): ?>
                    <a href="/sys-yii3/location-group/index?page=<?= $currentPage - 1 ?>" class="rs-btn rs-btn--sm rs-btn--outline">Previous</a>
                <?php endif; ?>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a href="/sys-yii3/location-group/index?page=<?= $p ?>" class="rs-btn rs-btn--sm <?= $p === $currentPage ? 'rs-btn--primary' : 'rs-btn--outline' ?>">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>
                <?php if ($currentPage < $totalPages): ?>
                    <a href="/sys-yii3/location-group/index?page=<?= $currentPage + 1 ?>" class="rs-btn rs-btn--sm rs-btn--outline">Next</a>
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
            <p class="rs-text-muted" style="font-size: 13px; margin: 0;">
                This action only deletes the group association. Connected social accounts and locations will remain intact.
            </p>
        </div>
        <div class="rs-card__footer rs-d-flex rs-justify-content-end rs-gap-2" style="padding: 12px 20px; border-top: 1px solid var(--rs-border, #E2E8F0); background: #F8FAFC;">
            <button type="button" class="rs-btn rs-btn--sm rs-btn--outline" onclick="closeDeleteModal()">Cancel</button>
            <form id="delete-group-form" method="POST" action="/sys-yii3/location-group/delete" style="margin: 0;">
                <input type="hidden" name="_csrf" value="<?= Html::encode($effectiveCsrf) ?>">
                <input type="hidden" name="id" id="delete-group-id" value="">
                <button type="submit" class="rs-btn rs-btn--sm rs-btn--danger">Delete Group</button>
            </form>
        </div>
    </div>
</div>

<script>
function filterLocationGroupsTable() {
    const input = document.getElementById('group-filter-input');
    const filter = input.value.toLowerCase().trim();
    const rows = document.querySelectorAll('#location-groups-table tbody tr.group-row');

    rows.forEach(row => {
        const text = row.getAttribute('data-name') || '';
        if (text.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

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
