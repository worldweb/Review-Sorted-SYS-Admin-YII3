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
$this->setTitle('Import Email Contacts');

$totalPages = max(1, (int) ceil($total / max(1, $pageSize)));
$currentPage = max(1, min($page, $totalPages));
$startItem = $total > 0 ? (($currentPage - 1) * $pageSize) + 1 : 0;
$endItem = min($total, $currentPage * $pageSize);

// Page Header
$title = 'Import Email Contacts';
$description = 'Import contacts from CSV to send email templates and newsletters.';
$actions = [];
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
        <form method="POST" action="<?= \App\Environment::url('/email/importer') ?>" enctype="multipart/form-data">
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
                    class="rs-input file-input"
                    required
                    style="padding: 8px 12px; width: 100%; box-sizing: border-box;"
                >
            </div>

            <div class="rs-form-group rs-d-flex rs-gap-3" style="margin-top: var(--rs-space-5, 20px); display: flex; gap: var(--rs-space-3, 12px);">
                <button type="submit" class="rs-btn rs-btn--primary">
                    Upload &amp; Import Contacts
                </button>
                <a href="<?= \App\Environment::url('/email/importer/sample') ?>" class="rs-btn rs-btn--outline">
                    Download Sample CSV
                </a>
            </div>

            <div style="margin-top:24px; padding:18px; background:var(--rs-bg-app, #f8fafc); border-radius:var(--rs-radius-md, 6px); border:1px solid var(--rs-border, #e2e8f0);">
                <p style="font-size:0.8125rem; font-weight:600; color:var(--rs-text-secondary, #475569); margin:0 0 8px;">CSV Layout &amp; Formatting Requirements</p>
                <ul style="font-size:0.78rem; color:var(--rs-text-muted, #64748b); margin:0; padding-left:18px; line-height:1.8;">
                    <li>CSV file must have header columns: <code>name</code>, <code>email</code>, <code>type</code></li>
                    <li>The <code>type</code> column values must be either <code>to</code> (standard recipient) or <code>bcc</code> (blind carbon copy)</li>
                    <li>Encoding must be UTF-8</li>
                </ul>
            </div>
        </form>
    </div>
</div>

<!-- Saved Contacts Card -->
<div class="rs-card rs-mb-6">
    <div class="rs-card__header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="rs-card__title" style="margin: 0;">Saved Email Contacts</h3>
        <button id="emails-delete-all-btn" type="button" class="rs-btn rs-btn--sm rs-btn--outline" style="color: var(--rs-danger, #ef4444); border-color: var(--rs-border, #cbd5e1);" onclick="confirmBulkDelete()">
            Delete Selected
        </button>
    </div>

    <div class="rs-table-wrapper rs-p-0" style="overflow-x: auto;">
        <table class="rs-table rs-table--hover" id="contacts-table" style="width: 100%; margin-bottom: 0;">
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;">
                        <input type="checkbox" id="select-all-chk" onclick="toggleSelectAllContacts(this)" style="width: 15px; height: 15px;">
                    </th>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>Contact Type</th>
                    <th style="width: 120px; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contacts)): ?>
                    <tr>
                        <td colspan="5" class="rs-text-center rs-py-6">
                            <div class="rs-text-muted" style="padding: 30px 0; text-align: center;">
                                <svg style="width: 36px; height: 36px; stroke: #94a3b8; margin-bottom: 8px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                                <p style="margin: 0; font-size: 14px; font-weight: 500; color: #475569;">No imported email contacts</p>
                                <p style="margin: 4px 0 0; font-size: 12px; color: #94a3b8;">Upload a CSV file to add email addresses to your list.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($contacts as $contact): ?>
                        <tr class="contact-row">
                            <td style="text-align: center;">
                                <input
                                    type="checkbox"
                                    class="contact-chk"
                                    value="<?= $contact->getId() ?>"
                                    style="width: 15px; height: 15px;"
                                >
                            </td>
                            <td class="rs-font-semibold rs-text-main">
                                <?= Html::encode($contact->getName()) ?>
                            </td>
                            <td class="rs-font-medium" style="word-break: break-all;">
                                <?= Html::encode($contact->getEmail()) ?>
                            </td>
                            <td>
                                <?php if ($contact->getType() === EmailAddress::TYPE_TO): ?>
                                    <span class="rs-badge rs-badge--success">TO</span>
                                <?php else: ?>
                                    <span class="rs-badge rs-badge--muted">BCC</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <button
                                    type="button"
                                    class="rs-btn rs-btn--sm rs-btn--ghost"
                                    style="color: var(--rs-danger, #ef4444);"
                                    onclick="confirmDeleteContact(<?= $contact->getId() ?>, '<?= Html::encode(addslashes($contact->getEmail())) ?>')"
                                >
                                    Remove
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
                    <a href="<?= \App\Environment::url('/email/importer?page=' . ($currentPage - 1)) ?>" class="rs-btn rs-btn--sm rs-btn--outline">Previous</a>
                <?php endif; ?>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a href="<?= \App\Environment::url('/email/importer?page=' . $p) ?>" class="rs-btn rs-btn--sm <?= $p === $currentPage ? 'rs-btn--primary' : 'rs-btn--outline' ?>">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>
                <?php if ($currentPage < $totalPages): ?>
                    <a href="<?= \App\Environment::url('/email/importer?page=' . ($currentPage + 1)) ?>" class="rs-btn rs-btn--sm rs-btn--outline">Next</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal for Single Contact Delete -->
<div id="delete-contact-modal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 9999; align-items: center; justify-content: center;">
    <div class="rs-card" style="width: 100%; max-width: 420px; margin: 20px;">
        <div class="rs-card__header" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #E2E8F0);">
            <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #DC2626;">Delete Contact</h3>
        </div>
        <div class="rs-card__body" style="padding: 20px;">
            <p style="margin: 0; font-size: 14px; color: #475569;">
                Are you sure you want to remove contact <strong id="delete-contact-email" style="color: #0F172A;"></strong>?
            </p>
        </div>
        <div class="rs-card__footer rs-d-flex rs-justify-content-end rs-gap-2" style="padding: 12px 20px; border-top: 1px solid var(--rs-border, #E2E8F0); background: #F8FAFC; display: flex; justify-content: flex-end; gap: 8px;">
            <button type="button" class="rs-btn rs-btn--sm rs-btn--outline" onclick="closeDeleteModal()">Cancel</button>
            <form method="POST" action="<?= \App\Environment::url('/email/contact/delete') ?>" style="margin: 0;">
                <input type="hidden" name="_csrf" value="<?= Html::encode($effectiveCsrf) ?>">
                <input type="hidden" name="id" id="delete-contact-id" value="">
                <button type="submit" class="rs-btn rs-btn--sm rs-btn--danger">Remove</button>
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
                Are you sure you want to delete <strong id="bulk-delete-count" style="color: #0F172A;"></strong> selected contact(s)?
            </p>
        </div>
        <div class="rs-card__footer rs-d-flex rs-justify-content-end rs-gap-2" style="padding: 12px 20px; border-top: 1px solid var(--rs-border, #E2E8F0); background: #F8FAFC; display: flex; justify-content: flex-end; gap: 8px;">
            <button type="button" class="rs-btn rs-btn--sm rs-btn--outline" onclick="closeBulkDeleteModal()">Cancel</button>
            <form method="POST" action="<?= \App\Environment::url('/email/contact/delete-bulk') ?>" style="margin: 0;">
                <input type="hidden" name="_csrf" value="<?= Html::encode($effectiveCsrf) ?>">
                <input type="hidden" name="ids" id="bulk-delete-ids" value="">
                <button type="submit" class="rs-btn rs-btn--sm rs-btn--danger">Delete All Selected</button>
            </form>
        </div>
    </div>
</div>

<script>
function toggleSelectAllContacts(master) {
    const checkboxes = document.querySelectorAll('input.contact-chk');
    checkboxes.forEach(cb => {
        cb.checked = master.checked;
    });
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
    if (ids.length === 0) {
        alert('Please select at least 1 contact');
        return;
    }

    document.getElementById('bulk-delete-ids').value = ids.join(',');
    document.getElementById('bulk-delete-count').textContent = ids.length;
    document.getElementById('bulk-delete-modal').style.display = 'flex';
}

function closeBulkDeleteModal() {
    document.getElementById('bulk-delete-modal').style.display = 'none';
}
</script>
