<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Send WhatsApp Messages View (Yii 3 Parity View)
 *
 * @var array<int, App\Domain\WhatsApp\WhatsAppTemplate> $templates
 * @var array<int, App\Domain\WhatsApp\WhatsAppAddress> $contacts
 * @var array<int, App\Domain\Account\Account> $accounts
 * @var array<string, array{label: string, sample: string}> $supportedTags
 * @var array<string, mixed> $formData
 * @var array<string, string> $errors
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 * @var Yiisoft\View\WebView $this
 */

$this->setTitle('Send WhatsApp Messages');

$actions = [
    (string) Html::a('Cancel', \App\Environment::url('/whatsapp/templates'), ['class' => 'rs-btn rs-btn--ghost']),
];
$title = 'Send WhatsApp Messages';
$description = 'Send WhatsApp templates to individual contacts or a contact list.';
include __DIR__ . '/../../Shared/Components/_page_header.php';
?>

<!-- Hidden template bodies for JS content populator -->
<?php foreach ($templates as $tpl): ?>
    <div id="tpl-<?= $tpl->getId() ?>" style="display:none;"><?= Html::encode($tpl->getText()) ?></div>
<?php endforeach; ?>

<div class="rs-page">
    <!-- Flash notifications -->
    <?php foreach ($flash->getAll() as $type => $messages): ?>
        <?php foreach ($messages as $message): ?>
            <div class="rs-alert rs-alert--<?= $type === 'error' ? 'danger' : Html::encode($type) ?> rs-mb-4" role="alert">
                <?= Html::encode($message) ?>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>

    <div class="rs-card rs-mb-6" style="max-width: 800px;">
        <form method="post" action="<?= \App\Environment::url('/whatsapp/send') ?>" class="rs-form">
            <?php if (!empty($csrfToken)): ?>
                <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
            <?php endif; ?>

            <div class="rs-card__body rs-p-6">
                <!-- Section 1: Target -->
                <div class="rs-mb-6">
                    <h4 class="rs-font-bold rs-text-main rs-mb-3" style="font-size: 1rem; border-bottom: 1px solid var(--rs-border, #E2E8F0); padding-bottom: 8px;">
                        Target
                    </h4>

                    <?php if (isset($errors['recipients'])): ?>
                        <div class="rs-alert rs-alert--danger rs-mb-4" role="alert">
                            <?= Html::encode($errors['recipients']) ?>
                        </div>
                    <?php endif; ?>

                    <div class="rs-form-group">
                        <label class="rs-label rs-font-semibold rs-mb-2" for="phoneList">Target Contacts</label>
                        <select name="phoneList[]" id="phoneList" class="rs-input rs-w-100" multiple size="6" style="height: auto; min-height: 120px;">
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
                        <div class="rs-text-muted rs-mt-1" style="font-size: 0.8rem;">Hold Ctrl/Cmd to select multiple recipients.</div>
                    </div>
                </div>

                <!-- Section 2: Manual Entry (And/Or) -->
                <div class="rs-mb-6">
                    <h4 class="rs-font-bold rs-text-main rs-mb-3" style="font-size: 1rem; border-bottom: 1px solid var(--rs-border, #E2E8F0); padding-bottom: 8px;">
                        Manual Entry (And/Or)
                    </h4>

                    <div class="rs-grid rs-grid--cols-1 rs-grid--cols-md-2 rs-gap-4">
                        <div class="rs-form-group">
                            <label class="rs-label rs-font-semibold rs-mb-2" for="manual-name">Target Name</label>
                            <input type="text" id="manual-name" name="name" value="<?= Html::encode($formData['name'] ?? '') ?>" class="rs-input rs-w-100" placeholder="e.g. John Doe">
                        </div>
                        <div class="rs-form-group">
                            <label class="rs-label rs-font-semibold rs-mb-2" for="manual-phone">Target Phone</label>
                            <input type="tel" id="manual-phone" name="phone" value="<?= Html::encode($formData['phone'] ?? '') ?>" class="rs-input rs-w-100 <?= isset($errors['phone']) ? 'rs-input--error' : '' ?>" placeholder="e.g. +61400111222">
                            <?php if (isset($errors['phone'])): ?>
                                <div class="rs-text-danger rs-mt-1" style="font-size: 0.8rem;"><?= Html::encode($errors['phone']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Message -->
                <div class="rs-mb-4">
                    <h4 class="rs-font-bold rs-text-main rs-mb-3" style="font-size: 1rem; border-bottom: 1px solid var(--rs-border, #E2E8F0); padding-bottom: 8px;">
                        Message
                    </h4>

                    <?php if (!empty($accounts)): ?>
                        <div class="rs-form-group rs-mb-4">
                            <label class="rs-label rs-font-semibold rs-mb-2" for="account_id">From Workspace</label>
                            <select name="account_id" id="account_id" class="rs-input rs-w-100">
                                <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= $acc->getId() ?>" <?= ((int) ($formData['account_id'] ?? 0) === $acc->getId()) ? 'selected' : '' ?>>
                                        <?= Html::encode($acc->getBusinessName() ?? "Workspace #{$acc->getId()}") ?> (#<?= $acc->getId() ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="rs-form-group rs-mb-4">
                        <label class="rs-label rs-font-semibold rs-mb-2" for="campaign-template">Template</label>
                        <select name="template" id="campaign-template" class="rs-input rs-w-100 <?= isset($errors['template']) ? 'rs-input--error' : '' ?>" onchange="onTemplateChange(this.value)">
                            <option value="">-- Choose a template --</option>
                            <?php foreach ($templates as $tpl): ?>
                                <option value="<?= $tpl->getId() ?>" <?= ((string) $tpl->getId() === ($formData['template_id'] ?? '')) ? 'selected' : '' ?>>
                                    <?= Html::encode($tpl->getName()) ?> <?= $tpl->isProtected() ? '(Protected)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['template'])): ?>
                            <div class="rs-text-danger rs-mt-1" style="font-size: 0.8rem;"><?= Html::encode($errors['template']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="rs-form-group rs-mb-3">
                        <label class="rs-label rs-font-semibold rs-mb-2" for="campaign-message">Message Text</label>
                        <textarea
                            name="message"
                            id="campaign-message"
                            rows="6"
                            class="rs-input rs-textarea-input rs-w-100 <?= isset($errors['message']) ? 'rs-input--error' : '' ?>"
                            required
                            placeholder="Write message text or select a template above..."><?= Html::encode($formData['message'] ?? '') ?></textarea>
                        <?php if (isset($errors['message'])): ?>
                            <div class="rs-text-danger rs-mt-1" style="font-size: 0.8rem;"><?= Html::encode($errors['message']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Personalization Tags -->
                    <div class="rs-mt-2 rs-mb-2">
                        <span class="rs-text-muted" style="font-size: 0.8rem; margin-right: 8px;">Merge tags:</span>
                        <?php foreach ($supportedTags as $tag => $info): ?>
                            <button
                                type="button"
                                class="rs-badge rs-badge--default"
                                onclick="insertWhatsAppTag('<?= Html::encode($tag) ?>')"
                                style="cursor: pointer; margin-right: 4px; margin-bottom: 4px; border: 1px solid var(--rs-border, #CBD5E1); background: #fff;">
                                <code><?= Html::encode($tag) ?></code>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="rs-card__footer rs-d-flex rs-justify-content-between rs-align-items-center rs-p-4" style="border-top: 1px solid var(--rs-border, #E2E8F0); background: #F8FAFC;">
                <a href="<?= \App\Environment::url('/whatsapp/templates') ?>" class="rs-btn rs-btn--secondary">Cancel</a>
                <button type="submit" class="rs-btn rs-btn--primary rs-px-6">
                    Queue Message
                </button>
            </div>
        </form>
    </div>
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