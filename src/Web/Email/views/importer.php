<?php

declare(strict_types=1);

use App\Domain\Email\EmailAddress;
use Yiisoft\Html\Html;

/**
 * Email Contacts & CSV Importer View (Yii 3 Modern Theme)
 *
 * @var \Yiisoft\View\WebView $this
 * @var EmailAddress[] $contacts
 * @var int $total
 * @var int $page
 * @var int $pageSize
 * @var int|null $accountId
 * @var \App\Domain\User\User $currentUser
 * @var \App\Security\TenantContextInterface $context
 * @var \Yiisoft\Session\Flash\FlashInterface|null $flash
 * @var string|null $csrfToken
 * @var string|null $csrf
 */

$effectiveCsrf = $csrfToken ?? $csrf ?? '';
$this->setTitle('E-mail Contacts & CSV Import');

$totalPages = max(1, (int) ceil($total / max(1, $pageSize)));
$currentPage = max(1, min($page, $totalPages));
$startItem = $total > 0 ? (($currentPage - 1) * $pageSize) + 1 : 0;
$endItem = min($total, $currentPage * $pageSize);

// Page Header
$title = 'E-mail Contacts & Import';
$description = 'Manage recipient contacts and import bulk addresses via CSV.';
$actions = [
    '<a href="/sys-yii3/email/importer/sample" class="rs-btn rs-btn--outline"><i class="fa-solid fa-download rs-mr-1"></i> Download Sample CSV</a>',
    '<a href="/sys-yii3/email/send" class="rs-btn rs-btn--primary"><i class="fa-solid fa-paper-plane rs-mr-1"></i> Send Campaign</a>'
];
include __DIR__ . '/../../Shared/Components/_page_header.php';
?>

<?php if ($flash !== null && !empty($flash->getAll())): ?>
    <?php include __DIR__ . '/../../Shared/Components/_alerts.php'; ?>
<?php endif; ?>

<!-- Upload CSV Card -->
<div class="rs-card rs-mb-6">
    <div class="rs-card__header">
        <h3 class="rs-card__title">Select CSV file to upload</h3>
    </div>
    <div class="rs-card__body rs-p-6">
                <form method="POST" action="/sys-yii3/email/importer" enctype="multipart/form-data">
                    <input type="hidden" name="_csrf" value="<?= Html::encode($effectiveCsrf) ?>">
                    <?php if ($accountId !== null && $context->isUnrestricted()): ?>
                        <input type="hidden" name="account_id" value="<?= $accountId ?>">
                    <?php endif; ?>

                    <div style="margin-bottom: 16px;">
                        <label class="rs-label" for="csv-file-input" style="font-size: 13px; font-weight: 600; margin-bottom: 6px; display: block;">
                            Select CSV File <span style="color: #EF4444;">*</span>
                        </label>
                        <input
                            type="file"
                            name="file"
                            id="csv-file-input"
                            accept=".csv,text/csv"
                            class="rs-input"
                            required
                            style="padding: 8px 12px;"
                        >
                    </div>

                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 6px; padding: 12px 14px; margin-bottom: 16px; font-size: 12px; color: #475569; line-height: 1.5;">
                        <strong style="color: #0F172A; display: block; margin-bottom: 4px;">CSV Format Requirements:</strong>
                        <ul style="margin: 0; padding-left: 18px;">
                            <li>Columns: <code>name</code>, <code>email</code>, <code>type</code></li>
                            <li><code>type</code> must be <code>to</code>, <code>cc</code>, or <code>bcc</code></li>
                            <li>File size limit: 5 MB (max 5,000 rows)</li>
                        </ul>
                    </div>

                    <div class="rs-d-flex rs-justify-content-between rs-align-items-center">
                        <a href="/sys-yii3/email/importer/sample" class="rs-text-primary" style="font-size: 13px; text-decoration: none;">
                            <i class="fa-solid fa-circle-down rs-mr-1"></i> Sample CSV
                        </a>
                        <button type="submit" class="rs-btn rs-btn--primary">
                            <i class="fa-solid fa-cloud-arrow-up rs-mr-1"></i> Import CSV
                        </button>
                    </div>
                </form>
            </div>
        </div>

    <!-- Saved Contacts Card -->
    <div class="rs-card rs-mb-6">
        <div class="rs-card__header rs-d-flex rs-justify-content-between rs-align-items-center rs-flex-wrap rs-gap-3">
            <h3 class="rs-card__title">Saved Email Contacts</h3>
                <div class="rs-d-flex rs-align-items-center rs-gap-2">
                    <span class="rs-badge rs-badge--neutral" style="font-size: 13px; font-weight: 600;">
                        <?= $total ?> <?= $total === 1 ? 'Contact' : 'Contacts' ?>
                    </span>
                    <button
                        type="button"
                        id="bulk-delete-btn"
                        class="rs-btn rs-btn--xs rs-btn--danger"
                        style="display: none;"
                        onclick="confirmBulkDelete()"
                    >
                        <i class="fa-solid fa-trash rs-mr-1"></i> Delete Selected (<span id="selected-count">0</span>)
                    </button>
                </div>
                <div>
                    <input
                        type="text"
                        id="contact-filter-input"
                        class="rs-input"
                        placeholder="Filter visible contacts..."
                        style="width: 220px; max-width: 100%; font-size: 13px;"
                        onkeyup="filterContactsTable()"
                    >
                </div>
            </div>

            <div class="rs-table-responsive">
                <table class="rs-table" id="contacts-table" style="width: 100%; margin-bottom: 0;">
                    <thead>
                        <tr>
                            <th style="width: 36px; text-align: center;">
                                <input type="checkbox" id="select-all-chk" onclick="toggleSelectAllContacts(this)" style="width: 15px; height: 15px;">
                            </th>
                            <th style="min-width: 180px;">Name</th>
                            <th style="min-width: 220px;">Email</th>
                            <th style="width: 80px;">Type</th>
                            <th style="width: 90px;">Status</th>
                            <th style="width: 110px;">Added</th>
                            <th style="width: 60px; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($contacts)): ?>
                            <tr>
                                <td colspan="7" class="rs-text-center rs-py-6">
                                    <div class="rs-text-muted" style="padding: 30px 0;">
                                        <i class="fa-solid fa-address-book" style="font-size: 32px; color: #94A3B8; margin-bottom: 8px;"></i>
                                        <p style="margin: 0; font-size: 14px;">No email contacts in this account yet.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($contacts as $contact): ?>
                                <tr class="contact-row" data-search="<?= Html::encode(strtolower($contact->getName() . ' ' . $contact->getEmail() . ' ' . $contact->getType())) ?>">
                                    <td style="text-align: center;">
                                        <input
                                            type="checkbox"
                                            class="contact-chk"
                                            value="<?= $contact->getId() ?>"
                                            onchange="updateBulkDeleteState()"
                                            style="width: 15px; height: 15px;"
                                        >
                                    </td>
                                    <td>
                                        <strong style="font-size: 13px; color: #0F172A;"><?= Html::encode($contact->getName()) ?></strong>
                                    </td>
                                    <td>
                                        <span style="font-size: 13px; color: #334155;"><?= Html::encode($contact->getEmail()) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($contact->getType() === EmailAddress::TYPE_TO): ?>
                                            <span class="rs-badge rs-badge--primary" style="font-size: 10px;">TO</span>
                                        <?php elseif ($contact->getType() === EmailAddress::TYPE_CC): ?>
                                            <span class="rs-badge rs-badge--neutral" style="font-size: 10px;">CC</span>
                                        <?php else: ?>
                                            <span class="rs-badge rs-badge--neutral" style="font-size: 10px;">BCC</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($contact->isActive()): ?>
                                            <span class="rs-badge rs-badge--success" style="font-size: 10px;">Active</span>
                                        <?php else: ?>
                                            <span class="rs-badge rs-badge--neutral" style="font-size: 10px;">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="rs-text-muted" style="font-size: 12px;">
                                            <?= $contact->getCreatedAt() ? date('d M Y', $contact->getCreatedAt()) : '—' ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <button
                                            type="button"
                                            class="rs-btn rs-btn--xs rs-btn--outline-danger"
                                            title="Delete contact"
                                            onclick="confirmDeleteContact(<?= $contact->getId() ?>, '<?= Html::encode(addslashes($contact->getEmail())) ?>')"
                                        >
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
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
                        Showing <?= $startItem ?> to <?= $endItem ?> of <?= $total ?> contacts
                    </div>
                    <div class="rs-d-flex rs-gap-1">
                        <?php if ($currentPage > 1): ?>
                            <a href="/sys-yii3/email/importer?page=<?= $currentPage - 1 ?>" class="rs-btn rs-btn--sm rs-btn--outline">Previous</a>
                        <?php endif; ?>
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <a href="/sys-yii3/email/importer?page=<?= $p ?>" class="rs-btn rs-btn--sm <?= $p === $currentPage ? 'rs-btn--primary' : 'rs-btn--outline' ?>">
                                <?= $p ?>
                            </a>
                        <?php endfor; ?>
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="/sys-yii3/email/importer?page=<?= $currentPage + 1 ?>" class="rs-btn rs-btn--sm rs-btn--outline">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

<!-- Modal for Single Contact Delete -->
<div id="delete-contact-modal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 9999; align-items: center; justify-content: center;">
    <div class="rs-card" style="width: 100%; max-width: 420px; margin: 20px;">
        <div class="rs-card__header" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #E2E8F0);">
            <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #DC2626;">Delete Contact</h3>
        </div>
        <div class="rs-card__body" style="padding: 20px;">
            <p style="margin: 0; font-size: 14px; color: #475569;">
                Are you sure you want to delete contact <strong id="delete-contact-email" style="color: #0F172A;"></strong>?
            </p>
        </div>
        <div class="rs-card__footer rs-d-flex rs-justify-content-end rs-gap-2" style="padding: 12px 20px; border-top: 1px solid var(--rs-border, #E2E8F0); background: #F8FAFC;">
            <button type="button" class="rs-btn rs-btn--sm rs-btn--outline" onclick="closeDeleteModal()">Cancel</button>
            <form method="POST" action="/sys-yii3/email/contact/delete" style="margin: 0;">
                <input type="hidden" name="_csrf" value="<?= Html::encode($effectiveCsrf) ?>">
                <input type="hidden" name="id" id="delete-contact-id" value="">
                <button type="submit" class="rs-btn rs-btn--sm rs-btn--danger">Delete</button>
            </form>
        </div>
    </div>
</div>

<!-- Modal for Bulk Contact Delete -->
<div id="bulk-delete-modal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 9999; align-items: center; justify-content: center;">
    <div class="rs-card" style="width: 100%; max-width: 420px; margin: 20px;">
        <div class="rs-card__header" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #E2E8F0);">
            <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #DC2626;">Delete Selected Contacts</h3>
        </div>
        <div class="rs-card__body" style="padding: 20px;">
            <p style="margin: 0; font-size: 14px; color: #475569;">
                Are you sure you want to delete <strong id="bulk-delete-count" style="color: #0F172A;"></strong> selected contacts?
            </p>
        </div>
        <div class="rs-card__footer rs-d-flex rs-justify-content-end rs-gap-2" style="padding: 12px 20px; border-top: 1px solid var(--rs-border, #E2E8F0); background: #F8FAFC;">
            <button type="button" class="rs-btn rs-btn--sm rs-btn--outline" onclick="closeBulkDeleteModal()">Cancel</button>
            <form method="POST" action="/sys-yii3/email/contact/delete-bulk" style="margin: 0;">
                <input type="hidden" name="_csrf" value="<?= Html::encode($effectiveCsrf) ?>">
                <input type="hidden" name="ids" id="bulk-delete-ids" value="">
                <button type="submit" class="rs-btn rs-btn--sm rs-btn--danger">Delete All Selected</button>
            </form>
        </div>
    </div>
</div>

<script>
function filterContactsTable() {
    const input = document.getElementById('contact-filter-input');
    const filter = input.value.toLowerCase().trim();
    const rows = document.querySelectorAll('#contacts-table tbody tr.contact-row');

    rows.forEach(row => {
        const text = row.getAttribute('data-search') || '';
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}

function toggleSelectAllContacts(master) {
    const checkboxes = document.querySelectorAll('input.contact-chk');
    checkboxes.forEach(cb => {
        const row = cb.closest('tr.contact-row');
        if (row && row.style.display !== 'none') {
            cb.checked = master.checked;
        }
    });
    updateBulkDeleteState();
}

function updateBulkDeleteState() {
    const selected = document.querySelectorAll('input.contact-chk:checked');
    const bulkBtn = document.getElementById('bulk-delete-btn');
    const counter = document.getElementById('selected-count');

    if (counter) counter.textContent = selected.length;
    if (bulkBtn) {
        bulkBtn.style.display = selected.length > 0 ? 'inline-flex' : 'none';
    }
}

function confirmDeleteContact(id, email) {
    document.getElementById('delete-contact-id').value = id;
    document.getElementById('delete-contact-email').textContent = email;
    document.getElementById('delete-contact-modal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('delete-contact-modal').style.display = 'none';
}

function confirmBulkDelete() {
    const selected = document.querySelectorAll('input.contact-chk:checked');
    const ids = Array.from(selected).map(cb => cb.value);
    if (ids.length === 0) return;

    document.getElementById('bulk-delete-ids').value = ids.join(',');
    document.getElementById('bulk-delete-count').textContent = ids.length;
    document.getElementById('bulk-delete-modal').style.display = 'flex';
}

function closeBulkDeleteModal() {
    document.getElementById('bulk-delete-modal').style.display = 'none';
}
</script>
