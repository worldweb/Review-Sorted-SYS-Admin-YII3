<?php

declare(strict_types=1);

use App\Domain\LocationGroup\ConnectedDestination;
use App\Domain\LocationGroup\LocationGroup;
use Yiisoft\Html\Html;

/**
 * Location Group Form Partial (Yii 3 Modern Theme)
 * Provides interactive form controls, platform-wise destination categorization,
 * live search filtering, and AJAX account synchronization.
 *
 * @var array{id?: int, name: string, description: string, status: int, account_id: int} $formData
 * @var array<int, string> $accountOptions
 * @var array{google: ConnectedDestination[], facebook: ConnectedDestination[], instagram: ConnectedDestination[]} $destinationsByPlatform
 * @var string[] $selectedKeys
 * @var array<string, string> $errors
 * @var bool $isEdit
 * @var string|null $csrfToken
 * @var string|null $csrf
 */

$isEdit = $isEdit ?? false;
$effectiveCsrf = $csrfToken ?? $csrf ?? '';
$selectedKeySet = array_flip($selectedKeys);

$googleItems = $destinationsByPlatform['google'] ?? [];
$facebookItems = $destinationsByPlatform['facebook'] ?? [];
$instagramItems = $destinationsByPlatform['instagram'] ?? [];

$totalAvailable = count($googleItems) + count($facebookItems) + count($instagramItems);
$hasSelectedAccount = !empty($formData['account_id']);
?>

<form method="POST" action="<?= $isEdit ? \App\Environment::url('/location-group/update?id=' . (int) ($formData['id'] ?? 0)) : \App\Environment::url('/location-group/create') ?>" id="location-group-form">
    <input type="hidden" name="_csrf" value="<?= Html::encode($effectiveCsrf) ?>">

    <!-- Card 1: Account Selection & Group Details -->
    <div class="rs-card rs-mb-5">
        <div class="rs-card__header">
            <h3 class="rs-card__title">Group Details</h3>
        </div>
        <div class="rs-card__body">
                    
                    <!-- Account Selection -->
                    <div style="margin-bottom: 16px;">
                        <label class="rs-label" for="lg-account-id" style="font-size: 13px; font-weight: 600; margin-bottom: 6px; display: block;">
                            Target Account <span style="color: #EF4444;">*</span>
                        </label>
                        <?php if ($isEdit || count($accountOptions) <= 1): ?>
                            <input
                                type="text"
                                class="rs-input"
                                value="<?= Html::encode($accountOptions[$formData['account_id']] ?? 'Account #' . $formData['account_id']) ?>"
                                readonly
                                style="background: #F1F5F9; cursor: not-allowed; color: #64748B;"
                            >
                            <input type="hidden" name="account_id" id="lg-account-id" value="<?= (int) $formData['account_id'] ?>">
                        <?php else: ?>
                            <select
                                name="account_id"
                                id="lg-account-id"
                                class="rs-select <?= isset($errors['account_id']) ? 'rs-input--error' : '' ?>"
                                onchange="loadDestinationsForAccount(this.value)"
                                style="width: 100%;"
                            >
                                <option value="">— Select Account —</option>
                                <?php foreach ($accountOptions as $accId => $accName): ?>
                                    <option value="<?= $accId ?>" <?= (int) $formData['account_id'] === $accId ? 'selected' : '' ?>>
                                        <?= Html::encode($accName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['account_id'])): ?>
                                <span class="rs-text-danger" style="font-size: 12px; margin-top: 4px; display: block;">
                                    <?= Html::encode($errors['account_id']) ?>
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Group Name -->
                    <div style="margin-bottom: 16px;">
                        <label class="rs-label" for="lg-name" style="font-size: 13px; font-weight: 600; margin-bottom: 6px; display: block;">
                            Group Name <span style="color: #EF4444;">*</span>
                        </label>
                        <input
                            type="text"
                            name="name"
                            id="lg-name"
                            class="rs-input <?= isset($errors['name']) ? 'rs-input--error' : '' ?>"
                            placeholder="e.g. WA Stores, East Coast Branch, Flagship Outlets"
                            value="<?= Html::encode($formData['name']) ?>"
                            required
                        >
                        <?php if (isset($errors['name'])): ?>
                            <span class="rs-text-danger" style="font-size: 12px; margin-top: 4px; display: block;">
                                <?= Html::encode($errors['name']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Status -->
                    <div style="margin-bottom: 16px;">
                        <label class="rs-label" for="lg-status" style="font-size: 13px; font-weight: 600; margin-bottom: 6px; display: block;">
                            Status
                        </label>
                        <select name="status" id="lg-status" class="rs-select" style="width: 100%;">
                            <option value="<?= LocationGroup::STATUS_ACTIVE ?>" <?= (int) $formData['status'] === LocationGroup::STATUS_ACTIVE ? 'selected' : '' ?>>Active</option>
                            <option value="<?= LocationGroup::STATUS_INACTIVE ?>" <?= (int) $formData['status'] === LocationGroup::STATUS_INACTIVE ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <!-- Description -->
                    <div style="margin-bottom: 16px;">
                        <label class="rs-label" for="lg-description" style="font-size: 13px; font-weight: 600; margin-bottom: 6px; display: block;">
                            Description
                        </label>
                        <textarea
                            name="description"
                            id="lg-description"
                            class="rs-textarea"
                            rows="4"
                            placeholder="Optional notes or details regarding this location group..."
                            style="width: 100%; resize: vertical;"
                        ><?= Html::encode($formData['description']) ?></textarea>
                    </div>

                </div>
            </div>

    <!-- Card 2: Interactive Destination Picker -->
    <div class="rs-card rs-mb-5">
        <div class="rs-card__header rs-d-flex rs-justify-content-between rs-align-items-center rs-flex-wrap rs-gap-3" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                    <div class="rs-d-flex rs-align-items-center rs-gap-2">
                        <h3 style="margin: 0; font-size: 15px; font-weight: 600;">
                            <i class="fa-solid fa-globe rs-mr-1" style="color: #64748B;"></i> Connected Social Destinations
                        </h3>
                        <span id="selected-dest-counter" class="rs-badge rs-badge--primary" style="font-size: 12px; font-weight: 600;">
                            <?= count($selectedKeys) ?> / <?= $totalAvailable ?> Selected
                        </span>
                    </div>
                    <div class="rs-d-flex rs-gap-2" id="dest-global-actions" style="<?= ($hasSelectedAccount && $totalAvailable > 0) ? '' : 'display:none;' ?>">
                        <button type="button" class="rs-btn rs-btn--xs rs-btn--outline" onclick="selectAllDestinations()">
                            <i class="fa-solid fa-check-double rs-mr-1"></i> Select All
                        </button>
                        <button type="button" class="rs-btn rs-btn--xs rs-btn--outline" onclick="deselectAllDestinations()">
                            <i class="fa-solid fa-xmark rs-mr-1"></i> Deselect All
                        </button>
                    </div>
                </div>

                <div class="rs-card__body" style="padding: 20px;">
                    
                    <?php if (isset($errors['destination_keys'])): ?>
                        <div class="rs-alert rs-alert--danger rs-mb-4" style="padding: 12px 16px; border-radius: 6px;">
                            <?= Html::encode($errors['destination_keys']) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Loading Indicator -->
                    <div id="dest-loading" style="display: none; padding: 40px; text-align: center; color: #64748B;">
                        <i class="fa-solid fa-spinner fa-spin" style="font-size: 28px; margin-bottom: 12px; color: #3B82F6;"></i>
                        <p style="margin: 0; font-size: 14px;">Loading connected destinations...</p>
                    </div>

                    <!-- Empty Account Prompt -->
                    <div id="dest-select-prompt" style="<?= $hasSelectedAccount ? 'display: none;' : '' ?> padding: 30px; text-align: center; background: #F8FAFC; border: 1px dashed #CBD5E1; border-radius: 8px;">
                        <i class="fa-solid fa-hand-pointer" style="font-size: 28px; color: #94A3B8; margin-bottom: 8px;"></i>
                        <p style="margin: 0; font-size: 14px; color: #475569;">Please select an account first to view and assign its connected social destinations.</p>
                    </div>

                    <!-- Empty Destinations for Selected Account -->
                    <div id="dest-empty-state" style="<?= ($hasSelectedAccount && $totalAvailable === 0) ? '' : 'display: none;' ?> padding: 30px; text-align: center; background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 8px;">
                        <i class="fa-solid fa-triangle-exclamation" style="font-size: 28px; color: #F59E0B; margin-bottom: 8px;"></i>
                        <p style="margin: 0 0 4px; font-weight: 600; color: #92400E;">No connected social destinations</p>
                        <p style="margin: 0; font-size: 13px; color: #B45309;">This account does not have any active Google Business Profile locations, Facebook Pages, or Instagram accounts connected.</p>
                    </div>

                    <!-- Destination Container -->
                    <div id="dest-container" style="<?= ($hasSelectedAccount && $totalAvailable > 0) ? '' : 'display: none;' ?>">
                        
                        <!-- Search Box -->
                        <div style="margin-bottom: 16px;">
                            <input
                                type="text"
                                id="dest-search-input"
                                class="rs-input"
                                placeholder="Search destinations by name or platform..."
                                onkeyup="filterDestinations()"
                                style="width: 100%;"
                            >
                        </div>

                        <!-- Platform Groups Container -->
                        <div id="dest-platform-groups" style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 8px;">
                            
                            <!-- Google Platform Group -->
                            <div class="dest-group-block" id="block-google" style="<?= !empty($googleItems) ? '' : 'display:none;' ?> border: 1px solid #E2E8F0; border-radius: 6px; overflow: hidden;">
                                <div class="rs-d-flex rs-justify-content-between rs-align-items-center" style="padding: 10px 16px; background: #EFF6FF; border-bottom: 1px solid #DBEAFE;">
                                    <div class="rs-d-flex rs-align-items-center rs-gap-2">
                                        <i class="fa-brands fa-google" style="color: #4285F4; font-size: 16px;"></i>
                                        <strong style="color: #1E40AF; font-size: 13px;">Google Business Profile (<span id="count-google"><?= count($googleItems) ?></span>)</strong>
                                    </div>
                                    <button type="button" class="rs-btn rs-btn--xs rs-btn--ghost" onclick="togglePlatformGroup('google')" style="color: #2563EB;">Toggle All</button>
                                </div>
                                <div class="dest-items-list" id="list-google" style="padding: 8px 12px; display: flex; flex-direction: column; gap: 6px; background: #FFFFFF; max-height: 280px; overflow-y: auto; overscroll-behavior: contain;">
                                    <?php foreach ($googleItems as $item): ?>
                                        <?php $isChecked = isset($selectedKeySet[$item->getKey()]); ?>
                                        <label class="dest-item-row" data-search="<?= Html::encode(strtolower($item->getLabel() . ' google ' . ($item->getAccountName() ?? ''))) ?>" style="display: flex; align-items: center; gap: 10px; margin: 0; padding: 6px 8px; border-radius: 4px; cursor: pointer; transition: background 0.15s ease;">
                                            <input
                                                type="checkbox"
                                                name="destination_keys[]"
                                                value="<?= Html::encode($item->getKey()) ?>"
                                                class="dest-chk dest-chk--google"
                                                <?= $isChecked ? 'checked' : '' ?>
                                                onchange="updateSelectionCounter()"
                                                style="width: 16px; height: 16px; accent-color: #3B82F6;"
                                            >
                                            <span style="flex: 1; font-size: 13px; font-weight: 500; color: #1E293B;">
                                                <?= Html::encode($item->getLabel()) ?>
                                            </span>
                                            <span class="rs-badge" style="background: #4285F4; color: #fff; font-size: 10px; font-weight: 600;">Google</span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Facebook Platform Group -->
                            <div class="dest-group-block" id="block-facebook" style="<?= !empty($facebookItems) ? '' : 'display:none;' ?> border: 1px solid #E2E8F0; border-radius: 6px; overflow: hidden;">
                                <div class="rs-d-flex rs-justify-content-between rs-align-items-center" style="padding: 10px 16px; background: #EFF6FF; border-bottom: 1px solid #DBEAFE;">
                                    <div class="rs-d-flex rs-align-items-center rs-gap-2">
                                        <i class="fa-brands fa-facebook" style="color: #1877F2; font-size: 16px;"></i>
                                        <strong style="color: #1E40AF; font-size: 13px;">Facebook Pages (<span id="count-facebook"><?= count($facebookItems) ?></span>)</strong>
                                    </div>
                                    <button type="button" class="rs-btn rs-btn--xs rs-btn--ghost" onclick="togglePlatformGroup('facebook')" style="color: #2563EB;">Toggle All</button>
                                </div>
                                <div class="dest-items-list" id="list-facebook" style="padding: 8px 12px; display: flex; flex-direction: column; gap: 6px; background: #FFFFFF; max-height: 280px; overflow-y: auto; overscroll-behavior: contain;">
                                    <?php foreach ($facebookItems as $item): ?>
                                        <?php $isChecked = isset($selectedKeySet[$item->getKey()]); ?>
                                        <label class="dest-item-row" data-search="<?= Html::encode(strtolower($item->getLabel() . ' facebook ' . ($item->getAccountName() ?? ''))) ?>" style="display: flex; align-items: center; gap: 10px; margin: 0; padding: 6px 8px; border-radius: 4px; cursor: pointer; transition: background 0.15s ease;">
                                            <input
                                                type="checkbox"
                                                name="destination_keys[]"
                                                value="<?= Html::encode($item->getKey()) ?>"
                                                class="dest-chk dest-chk--facebook"
                                                <?= $isChecked ? 'checked' : '' ?>
                                                onchange="updateSelectionCounter()"
                                                style="width: 16px; height: 16px; accent-color: #3B82F6;"
                                            >
                                            <span style="flex: 1; font-size: 13px; font-weight: 500; color: #1E293B;">
                                                <?= Html::encode($item->getLabel()) ?>
                                            </span>
                                            <span class="rs-badge" style="background: #1877F2; color: #fff; font-size: 10px; font-weight: 600;">Facebook</span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Instagram Platform Group -->
                            <div class="dest-group-block" id="block-instagram" style="<?= !empty($instagramItems) ? '' : 'display:none;' ?> border: 1px solid #E2E8F0; border-radius: 6px; overflow: hidden;">
                                <div class="rs-d-flex rs-justify-content-between rs-align-items-center" style="padding: 10px 16px; background: #FDF2F8; border-bottom: 1px solid #FCE7F3;">
                                    <div class="rs-d-flex rs-align-items-center rs-gap-2">
                                        <i class="fa-brands fa-instagram" style="color: #E1306C; font-size: 16px;"></i>
                                        <strong style="color: #9D174D; font-size: 13px;">Instagram Business Accounts (<span id="count-instagram"><?= count($instagramItems) ?></span>)</strong>
                                    </div>
                                    <button type="button" class="rs-btn rs-btn--xs rs-btn--ghost" onclick="togglePlatformGroup('instagram')" style="color: #DB2777;">Toggle All</button>
                                </div>
                                <div class="dest-items-list" id="list-instagram" style="padding: 8px 12px; display: flex; flex-direction: column; gap: 6px; background: #FFFFFF; max-height: 280px; overflow-y: auto; overscroll-behavior: contain;">
                                    <?php foreach ($instagramItems as $item): ?>
                                        <?php $isChecked = isset($selectedKeySet[$item->getKey()]); ?>
                                        <label class="dest-item-row" data-search="<?= Html::encode(strtolower($item->getLabel() . ' instagram ' . ($item->getAccountName() ?? ''))) ?>" style="display: flex; align-items: center; gap: 10px; margin: 0; padding: 6px 8px; border-radius: 4px; cursor: pointer; transition: background 0.15s ease;">
                                            <input
                                                type="checkbox"
                                                name="destination_keys[]"
                                                value="<?= Html::encode($item->getKey()) ?>"
                                                class="dest-chk dest-chk--instagram"
                                                <?= $isChecked ? 'checked' : '' ?>
                                                onchange="updateSelectionCounter()"
                                                style="width: 16px; height: 16px; accent-color: #3B82F6;"
                                            >
                                            <span style="flex: 1; font-size: 13px; font-weight: 500; color: #1E293B;">
                                                <?= Html::encode($item->getLabel()) ?>
                                            </span>
                                            <span class="rs-badge" style="background: #E1306C; color: #fff; font-size: 10px; font-weight: 600;">Instagram</span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- No search results -->
                            <div id="dest-no-search" style="display: none; padding: 30px; text-align: center; color: #64748B;">
                                <p style="margin: 0; font-size: 14px;">No destinations match your search query.</p>
                            </div>

                        </div>
                    </div>

                </div>
            </div>

    <!-- Form Actions Footer -->
    <div class="rs-card rs-mt-5 rs-mb-6" style="padding: 16px 20px; background: #F8FAFC; border: 1px solid var(--rs-border, #E2E8F0); border-radius: 8px; margin-bottom: 40px; box-shadow: var(--rs-shadow-sm, 0 1px 3px rgba(0,0,0,0.05));">
        <div class="rs-d-flex rs-justify-content-end rs-gap-3" style="display: flex; justify-content: flex-end; align-items: center; gap: 12px;">
            <a href="<?= \App\Environment::url('/location-group/index') ?>" class="rs-btn rs-btn--secondary" style="min-width: 100px; text-align: center;">Cancel</a>
            <button type="submit" class="rs-btn rs-btn--primary" style="padding: 9px 20px; font-weight: 600; min-width: 180px;">
                <?= $isEdit ? 'Update Location Group' : 'Create Location Group' ?>
            </button>
        </div>
    </div>
</form>

<script>
let totalAvailableDestinations = <?= $totalAvailable ?>;

function updateSelectionCounter() {
    const checked = document.querySelectorAll('input.dest-chk:checked').length;
    const total = document.querySelectorAll('input.dest-chk').length;
    const counter = document.getElementById('selected-dest-counter');
    if (counter) {
        counter.textContent = checked + ' / ' + total + ' Selected';
    }
}

function selectAllDestinations() {
    const checkboxes = document.querySelectorAll('input.dest-chk');
    checkboxes.forEach(cb => {
        const row = cb.closest('.dest-item-row');
        if (row && row.style.display !== 'none') {
            cb.checked = true;
        }
    });
    updateSelectionCounter();
}

function deselectAllDestinations() {
    const checkboxes = document.querySelectorAll('input.dest-chk');
    checkboxes.forEach(cb => {
        const row = cb.closest('.dest-item-row');
        if (row && row.style.display !== 'none') {
            cb.checked = false;
        }
    });
    updateSelectionCounter();
}

function togglePlatformGroup(platform) {
    const checkboxes = document.querySelectorAll('input.dest-chk--' + platform);
    const anyUnchecked = Array.from(checkboxes).some(cb => !cb.checked);
    checkboxes.forEach(cb => {
        cb.checked = anyUnchecked;
    });
    updateSelectionCounter();
}

function filterDestinations() {
    const input = document.getElementById('dest-search-input');
    const filter = input ? input.value.toLowerCase().trim() : '';
    const rows = document.querySelectorAll('.dest-item-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const search = row.getAttribute('data-search') || '';
        if (search.includes(filter)) {
            row.style.display = 'flex';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const noResults = document.getElementById('dest-no-search');
    if (noResults) {
        noResults.style.display = (visibleCount === 0 && rows.length > 0) ? 'block' : 'none';
    }
}

function loadDestinationsForAccount(accountId) {
    const prompt = document.getElementById('dest-select-prompt');
    const emptyState = document.getElementById('dest-empty-state');
    const container = document.getElementById('dest-container');
    const actions = document.getElementById('dest-global-actions');
    const loading = document.getElementById('dest-loading');

    if (!accountId || parseInt(accountId, 10) <= 0) {
        if (prompt) prompt.style.display = 'block';
        if (emptyState) emptyState.style.display = 'none';
        if (container) container.style.display = 'none';
        if (actions) actions.style.display = 'none';
        return;
    }

    if (prompt) prompt.style.display = 'none';
    if (emptyState) emptyState.style.display = 'none';
    if (container) container.style.display = 'none';
    if (actions) actions.style.display = 'none';
    if (loading) loading.style.display = 'block';

    const url = '<?= \App\Environment::url('/location-group/destinations') ?>?accountId=' + encodeURIComponent(accountId);

    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (loading) loading.style.display = 'none';

        if (!data || !data.success || !data.destinations) {
            if (emptyState) emptyState.style.display = 'block';
            return;
        }

        const google = data.destinations.google || [];
        const facebook = data.destinations.facebook || [];
        const instagram = data.destinations.instagram || [];
        const total = google.length + facebook.length + instagram.length;

        if (total === 0) {
            if (emptyState) emptyState.style.display = 'block';
            return;
        }

        // Render Google items
        renderPlatformList('google', google, '#4285F4', 'Google');
        renderPlatformList('facebook', facebook, '#1877F2', 'Facebook');
        renderPlatformList('instagram', instagram, '#E1306C', 'Instagram');

        if (container) container.style.display = 'block';
        if (actions) actions.style.display = 'flex';
        updateSelectionCounter();
    })
    .catch(err => {
        if (loading) loading.style.display = 'none';
        if (emptyState) emptyState.style.display = 'block';
        console.error('Failed to load destinations:', err);
    });
}

function renderPlatformList(platform, items, badgeColor, badgeLabel) {
    const block = document.getElementById('block-' + platform);
    const list = document.getElementById('list-' + platform);
    const countSpan = document.getElementById('count-' + platform);

    if (countSpan) countSpan.textContent = items.length;

    if (!items || items.length === 0) {
        if (block) block.style.display = 'none';
        if (list) list.innerHTML = '';
        return;
    }

    if (block) block.style.display = 'block';
    if (list) {
        let html = '';
        items.forEach(item => {
            const searchStr = (item.label + ' ' + platform + ' ' + (item.account_name || '')).toLowerCase();
            html += '<label class="dest-item-row" data-search="' + escapeHtml(searchStr) + '" style="display: flex; align-items: center; gap: 10px; margin: 0; padding: 6px 8px; border-radius: 4px; cursor: pointer;">';
            html += '  <input type="checkbox" name="destination_keys[]" value="' + escapeHtml(item.key) + '" class="dest-chk dest-chk--' + platform + '" onchange="updateSelectionCounter()" style="width: 16px; height: 16px; accent-color: #3B82F6;">';
            html += '  <span style="flex: 1; font-size: 13px; font-weight: 500; color: #1E293B;">' + escapeHtml(item.label) + '</span>';
            html += '  <span class="rs-badge" style="background: ' + badgeColor + '; color: #fff; font-size: 10px; font-weight: 600;">' + badgeLabel + '</span>';
            html += '</label>';
        });
        list.innerHTML = html;
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>
