<?php

declare(strict_types=1);

use App\Domain\WhatsApp\WhatsAppAddress;
use App\Domain\WhatsApp\WhatsAppTemplate;
use Yiisoft\Html\Html;

/**
 * @var array<int, WhatsAppTemplate> $templates
 * @var array<int, WhatsAppAddress> $contacts
 * @var array<string, mixed> $formData
 * @var array<string, string> $errors
 * @var array<int, \App\Domain\Account\Account> $accounts
 * @var array<string, array{label: string, scope: string, example: string}> $supportedTags
 * @var \App\Domain\User\User $currentUser
 * @var \App\Security\TenantContextInterface $context
 * @var \Yiisoft\Session\Flash\Flash $flash
 * @var string|null $csrfToken
 */

$this->setTitle('Send WhatsApp Messages');
$activeTab = 'send';
?>

<!-- Hidden template bodies for JS content populator -->
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
        <h1 class="rs-page-title rs-m-0">Send WhatsApp Messages</h1>
        <p class="rs-text-muted rs-m-0">Compose and dispatch personalized WhatsApp messages to contacts or direct phone numbers.</p>
    </div>

    <form method="post" action="/sys-yii3/whatsapp/send">
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
                            <label class="rs-form-label" for="phoneList">Select from Saved Phone Directory</label>
                            <p class="rs-text-muted" style="font-size: 12px; margin-bottom: 6px;">Hold Ctrl/Cmd to select multiple recipients.</p>
                            <select name="phoneList[]" id="phoneList" class="rs-input" multiple size="6" style="height: auto; min-height: 120px;">
                                <?php if (empty($contacts)): ?>
                                    <option disabled>No saved contacts found. Import contacts via CSV or enter manually below.</option>
                                <?php else: ?>
                                    <?php foreach ($contacts as $c): ?>
                                        <?php $isSelected = in_array($c->getId(), (array) ($formData['phoneList'] ?? []), true); ?>
                                        <option value="<?= $c->getId() ?>" <?= $isSelected ? 'selected' : '' ?>>
                                            <?= Html::encode($c->getName()) ?> (<?= Html::encode($c->getPhone()) ?>)
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
                                        placeholder="Target Name (e.g. John Doe)"
                                    />
                                </div>
                                <div class="rs-form-group">
                                    <input
                                        type="tel"
                                        name="phone"
                                        value="<?= Html::encode($formData['phone'] ?? '') ?>"
                                        class="rs-input <?= isset($errors['phone']) ? 'rs-input--error' : '' ?>"
                                        placeholder="Target Phone (e.g. +61400111222)"
                                    />
                                    <?php if (isset($errors['phone'])): ?>
                                        <span class="rs-text-danger" style="font-size: 12px;"><?= Html::encode($errors['phone']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message Content Card -->
                <div class="rs-card">
                    <div class="rs-card__header">
                        <h3 class="rs-m-0" style="font-size: 16px;">Message Content</h3>
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

                        <div class="rs-form-group rs-mb-4">
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

                        <div class="rs-form-group rs-mt-4">
                            <label class="rs-form-label" for="campaign-message">Message Text (Plain Text) *</label>
                            <textarea
                                name="message"
                                id="campaign-message"
                                rows="8"
                                class="rs-input <?= isset($errors['message']) ? 'rs-input--error' : '' ?>"
                                style="font-size: 14px; line-height: 1.5;"
                                required
                            ><?= Html::encode($formData['message'] ?? '') ?></textarea>
                            <?php if (isset($errors['message'])): ?>
                                <span class="rs-text-danger" style="font-size: 12px;"><?= Html::encode($errors['message']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="rs-card__footer rs-d-flex rs-align-items-center rs-gap-3 rs-p-4" style="border-top: 1px solid var(--rs-border, #E2E8F0);">
                        <button type="submit" class="rs-btn rs-btn--primary">
                            <i class="fa-solid fa-paper-plane rs-mr-1"></i> Queue Message
                        </button>
                        <a href="/sys-yii3/whatsapp/templates" class="rs-btn rs-btn--ghost">Cancel</a>
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
                            Click to append tag into the message:
                        </p>
                        <div class="rs-d-flex rs-flex-column rs-gap-2">
                            <?php foreach ($supportedTags as $tag => $info): ?>
                                <button
                                    type="button"
                                    class="rs-btn rs-btn--sm rs-btn--outline rs-d-flex rs-align-items-center rs-justify-content-between"
                                    onclick="insertWhatsAppTag('<?= Html::encode($tag) ?>')"
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
                            <strong>Safe Queueing:</strong> Submitting creates outbound records in <code>whatsapp_queue</code>.
                        </p>
                        <p class="rs-mb-2">
                            <strong>Phone Sanitization:</strong> Recipient phone numbers are automatically formatted to international E.164 standards.
                        </p>
                        <p class="rs-m-0">
                            <strong>History Logs:</strong> View delivery logs and message status on the <a href="/sys-yii3/whatsapp/history">Message History</a> tab.
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
        const parser = new DOMParser();
        const decoded = parser.parseFromString(div.innerHTML, 'text/html').body.textContent || div.innerHTML;
        textarea.value = decoded;
    }
}

function insertWhatsAppTag(tag) {
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
