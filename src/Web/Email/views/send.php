<?php

declare(strict_types=1);

use App\Domain\Email\EmailAddress;
use App\Domain\Email\EmailTemplate;
use Yiisoft\Html\Html;

/**
 * @var array<int, EmailTemplate> $templates
 * @var array<int, EmailAddress> $toContacts
 * @var array<int, EmailAddress> $bccContacts
 * @var array<string, mixed> $formData
 * @var array<string, string> $errors
 * @var array<int, \App\Domain\Account\Account> $accounts
 * @var array<string, array{label: string, scope: string, example: string}> $supportedTags
 * @var \App\Domain\User\User $currentUser
 * @var \App\Security\TenantContextInterface $context
 * @var \Yiisoft\Session\Flash\Flash $flash
 * @var string|null $csrfToken
 */

$this->setTitle('Send E-mail Campaign');
$activeTab = 'send';
?>

<!-- Hidden template bodies for dynamic client-side populator -->
<?php foreach ($templates as $tpl): ?>
    <div id="tpl-<?= $tpl->getId() ?>" style="display:none;"><?= Html::encode($tpl->getText()) ?></div>
<?php endforeach; ?>

<div class="rs-page">
    <?php include __DIR__ . '/_nav.php'; ?>

    <!-- Flash notifications -->
    <?php foreach ($flash->getAll() as $type => $messages): ?>
        <?php foreach ($messages as $message): ?>
            <div class="rs-alert rs-alert--<?= $type === 'error' ? 'danger' : Html::encode($type) ?> rs-mb-4" role="alert">
                <?= Html::encode($message) ?>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>

    <div class="rs-page-header rs-mb-6">
        <h1 class="rs-page-title rs-m-0">Send E-mail Campaign</h1>
        <p class="rs-text-muted rs-m-0">Compose and dispatch an outbound email campaign to target contacts or individual recipients.</p>
    </div>

    <form method="post" action="/sys-yii3/email/send">
        <?php if (!empty($csrfToken)): ?>
            <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
        <?php endif; ?>

        <div class="rs-grid rs-grid--cols-12 rs-gap-6" style="display: grid; grid-template-columns: repeat(12, 1fr); gap: var(--rs-space-6, 24px);">
            <!-- Left Area: Recipients & Message (8 cols) -->
            <div style="grid-column: span 8; min-width: 0;">

                <!-- Recipients Card -->
                <div class="rs-card rs-mb-6">
                    <div class="rs-card__header">
                        <h3 class="rs-m-0" style="font-size: 16px;">Target Recipients</h3>
                    </div>
                    <div class="rs-card__body">
                        <?php if (isset($errors['recipients'])): ?>
                            <div class="rs-alert rs-alert--danger rs-mb-4" role="alert">
                                <?= Html::encode($errors['recipients']) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Select from saved contacts -->
                        <div class="rs-form-group rs-mb-4">
                            <label class="rs-form-label" for="emailList">Select from Saved Contacts (To)</label>
                            <p class="rs-text-muted" style="font-size: 12px; margin-bottom: 6px;">Hold Ctrl/Cmd to select multiple contacts from your address book.</p>
                            <select name="emailList[]" id="emailList" class="rs-input" multiple size="6" style="height: auto; min-height: 120px;">
                                <?php if (empty($toContacts)): ?>
                                    <option disabled>No saved contacts found. Import contacts via CSV or enter manually below.</option>
                                <?php else: ?>
                                    <?php foreach ($toContacts as $contact): ?>
                                        <?php $isSelected = in_array($contact->getId(), (array) ($formData['emailList'] ?? []), true); ?>
                                        <option value="<?= $contact->getId() ?>" <?= $isSelected ? 'selected' : '' ?>>
                                            <?= Html::encode($contact->getName()) ?> &lt;<?= Html::encode($contact->getEmail()) ?>&gt;
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Manual Recipient -->
                        <div style="border-top: 1px dashed var(--rs-border, #E2E8F0); padding-top: 16px; margin-top: 16px;">
                            <label class="rs-form-label rs-mb-2" style="font-weight: 600;">Or Enter Single Recipient Details</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--rs-space-4, 16px);">
                                <div class="rs-form-group">
                                    <input
                                        type="text"
                                        name="name"
                                        value="<?= Html::encode($formData['name'] ?? '') ?>"
                                        class="rs-input"
                                        placeholder="Full Name"
                                    />
                                </div>
                                <div class="rs-form-group">
                                    <input
                                        type="email"
                                        name="email"
                                        value="<?= Html::encode($formData['email'] ?? '') ?>"
                                        class="rs-input <?= isset($errors['email']) ? 'rs-input--error' : '' ?>"
                                        placeholder="recipient@example.com"
                                    />
                                    <?php if (isset($errors['email'])): ?>
                                        <span class="rs-text-danger" style="font-size: 12px;"><?= Html::encode($errors['email']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Optional BCC -->
                        <div class="rs-form-group rs-mt-4">
                            <label class="rs-form-label" for="bccEmailList">Send BCC Copies to</label>
                            <select name="bccEmailList[]" id="bccEmailList" class="rs-input" multiple size="3" style="height: auto;">
                                <?php foreach ($bccContacts as $contact): ?>
                                    <?php $isSelected = in_array($contact->getId(), (array) ($formData['bccEmailList'] ?? []), true); ?>
                                    <option value="<?= $contact->getId() ?>" <?= $isSelected ? 'selected' : '' ?>>
                                        <?= Html::encode($contact->getName()) ?> &lt;<?= Html::encode($contact->getEmail()) ?>&gt;
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Campaign Message Card -->
                <div class="rs-card">
                    <div class="rs-card__header">
                        <h3 class="rs-m-0" style="font-size: 16px;">Campaign Message</h3>
                    </div>
                    <div class="rs-card__body">
                        <?php if (!empty($accounts)): ?>
                            <div class="rs-form-group rs-mb-4" style="max-width: 400px;">
                                <label class="rs-form-label" for="account_id">From Workspace</label>
                                <select name="account_id" id="account_id" class="rs-input">
                                    <?php foreach ($accounts as $acc): ?>
                                        <option value="<?= $acc->getId() ?>" <?= ((int) ($formData['account_id'] ?? 0) === $acc->getId()) ? 'selected' : '' ?>>
                                            <?= Html::encode($acc->getName()) ?> (#<?= $acc->getId() ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--rs-space-4, 16px); margin-bottom: var(--rs-space-4, 16px);">
                            <div class="rs-form-group">
                                <label class="rs-form-label" for="campaign-template">Select Base Template *</label>
                                <select name="template" id="campaign-template" class="rs-input <?= isset($errors['template']) ? 'rs-input--error' : '' ?>" onchange="onTemplateChange(this.value)">
                                    <option value="">-- Choose a template --</option>
                                    <?php foreach ($templates as $tpl): ?>
                                        <option value="<?= $tpl->getId() ?>" <?= ((string) $tpl->getId() === ($formData['template_id'] ?? '')) ? 'selected' : '' ?>>
                                            <?= Html::encode($tpl->getName()) ?> <?= $tpl->isProtected() ? '(Protected)' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['template'])): ?>
                                    <span class="rs-text-danger" style="font-size: 12px;"><?= Html::encode($errors['template']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="rs-form-group">
                                <label class="rs-form-label" for="campaign-subject">Subject Line *</label>
                                <input
                                    type="text"
                                    name="subject"
                                    id="campaign-subject"
                                    value="<?= Html::encode($formData['subject'] ?? '') ?>"
                                    class="rs-input <?= isset($errors['subject']) ? 'rs-input--error' : '' ?>"
                                    placeholder="e.g. How was your recent experience with us?"
                                    required
                                />
                                <?php if (isset($errors['subject'])): ?>
                                    <span class="rs-text-danger" style="font-size: 12px;"><?= Html::encode($errors['subject']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="rs-form-group rs-mt-4">
                            <label class="rs-form-label" for="campaign-message">Email Message Content (HTML) *</label>
                            <textarea
                                name="message"
                                id="campaign-message"
                                rows="16"
                                class="rs-input <?= isset($errors['message']) ? 'rs-input--error' : '' ?>"
                                style="font-family: monospace; font-size: 13px; line-height: 1.5;"
                                required
                            ><?= Html::encode($formData['message'] ?? '') ?></textarea>
                            <?php if (isset($errors['message'])): ?>
                                <span class="rs-text-danger" style="font-size: 12px;"><?= Html::encode($errors['message']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="rs-card__footer rs-d-flex rs-align-items-center rs-gap-3 rs-p-4" style="border-top: 1px solid var(--rs-border, #E2E8F0);">
                        <button type="submit" class="rs-btn rs-btn--primary">
                            <i class="fa-solid fa-paper-plane rs-mr-1"></i> Queue Campaign
                        </button>
                        <a href="/sys-yii3/email/templates" class="rs-btn rs-btn--ghost">Cancel</a>
                    </div>
                </div>
            </div>

            <!-- Right Area: Sidebar Tags (4 cols) -->
            <div style="grid-column: span 4;">
                <div class="rs-card rs-mb-6">
                    <div class="rs-card__header">
                        <h3 class="rs-m-0" style="font-size: 15px;">Personalization Merge Tags</h3>
                    </div>
                    <div class="rs-card__body rs-p-4">
                        <p class="rs-text-muted" style="font-size: 12px; margin-bottom: 12px;">
                            Click to append tag into the message body:
                        </p>
                        <div class="rs-d-flex rs-flex-column rs-gap-2">
                            <?php foreach ($supportedTags as $tag => $info): ?>
                                <button
                                    type="button"
                                    class="rs-btn rs-btn--sm rs-btn--outline rs-d-flex rs-align-items-center rs-justify-content-between"
                                    onclick="insertCampaignTag('<?= Html::encode($tag) ?>')"
                                    style="text-align: left;"
                                >
                                    <code><?= Html::encode($tag) ?></code>
                                    <span class="rs-text-muted" style="font-size: 11px;"><?= Html::encode($info['label']) ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="rs-card">
                    <div class="rs-card__header">
                        <h3 class="rs-m-0" style="font-size: 15px;">Queueing Notes</h3>
                    </div>
                    <div class="rs-card__body rs-p-4" style="font-size: 12px; line-height: 1.6;">
                        <p class="rs-mb-2">
                            <strong>Safe Queueing:</strong> Submitting creates outbound records in <code>email_queue</code>.
                        </p>
                        <p class="rs-mb-2">
                            <strong>Per-Recipient Tokens:</strong> <code>##NAME##</code> and <code>##EMAIL##</code> are personalized individually for each contact in the list.
                        </p>
                        <p class="rs-m-0">
                            <strong>Audit Logs:</strong> Monitor processing states on the <a href="/sys-yii3/email/queue">Delivery Queue</a> tab.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function onTemplateChange(templateId) {
    if (!templateId) return;
    const div = document.getElementById('tpl-' + templateId);
    const textarea = document.getElementById('campaign-message');
    if (div && textarea) {
        // Decode HTML entities from hidden template div
        const parser = new DOMParser();
        const decoded = parser.parseFromString(div.innerHTML, 'text/html').body.textContent || div.innerHTML;
        textarea.value = decoded;
    }
}

function insertCampaignTag(tag) {
    const textarea = document.getElementById('campaign-message');
    if (!textarea) return;
    const start = textarea.selectionStart || 0;
    const end = textarea.selectionEnd || 0;
    const val = textarea.value;
    textarea.value = val.substring(0, start) + tag + val.substring(end);
    textarea.focus();
    const newPos = start + tag.length;
    textarea.setSelectionRange(newPos, newPos);
}
</script>
