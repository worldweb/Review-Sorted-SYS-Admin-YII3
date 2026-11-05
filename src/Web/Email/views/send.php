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
 * @var \Yiisoft\View\WebView $this
 */

$this->setTitle('Send E-mail Campaign');
?>

<style>
    /* ── Divider between multi-select and single recipient ── */
    .mod-divider {
        display: flex;
        align-items: center;
        text-align: center;
        margin: var(--rs-space-4, 16px) 0;
        color: var(--rs-text-muted, #94a3b8);
        font-size: 13px;
        text-transform: lowercase;
    }

    .mod-divider::before,
    .mod-divider::after {
        content: '';
        flex: 1;
        border-bottom: 1px solid var(--rs-border, #e2e8f0);
    }

    .mod-divider:not(:empty)::before {
        margin-right: .75em;
    }

    .mod-divider:not(:empty)::after {
        margin-left: .75em;
    }
</style>

<!-- Hidden template bodies for JS template content populator -->
<?php foreach ($templates as $template): ?>
    <div id="tpl-<?= $template->getId() ?>" style="display:none"><?= $template->getText() ?></div>
<?php endforeach; ?>

<div class="rs-email-send">
    <!-- Flash Notifications -->
    <?php foreach ($flash->getAll() as $type => $messages): ?>
        <?php foreach ($messages as $message): ?>
            <div class="rs-alert rs-alert--<?= $type === 'error' ? 'danger' : Html::encode($type) ?> rs-mb-4" role="alert">
                <?= Html::encode($message) ?>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>

    <div class="rs-page-header rs-mb-6">
        <h1 class="rs-page-title rs-m-0">Send E-mail Campaign</h1>
        <p class="rs-text-muted rs-m-0" style="color: #64748b; font-size: 14px; margin-top: 4px;">Send an email campaign to a list of target contacts or custom recipient.</p>
    </div>

    <form method="post" action="<?= \App\Environment::url('/email/send') ?>">
        <?php if (!empty($csrfToken)): ?>
            <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
        <?php endif; ?>

        <div class="rs-grid rs-grid--cols-12 rs-gap-6" style="display: grid; grid-template-columns: repeat(12, 1fr); gap: var(--rs-space-6, 24px);">
            <!-- Main Form Fields (8 Columns) -->
            <div style="grid-column: span 8; min-width: 0;" class="rs-lg-col-span-8">

                <!-- Target / Recipients Card -->
                <div class="rs-card mod-recipients-card rs-mb-6">
                    <div class="rs-card__header">
                        <h3 class="rs-card__title">Recipients (Target)</h3>
                    </div>
                    <div class="rs-card__body">
                        <?php if (isset($errors['recipients'])): ?>
                            <div class="rs-alert rs-alert--danger rs-mb-4" role="alert">
                                <?= Html::encode($errors['recipients']) ?>
                            </div>
                        <?php endif; ?>

                        <div class="form-group rs-mb-4">
                            <label class="rs-label" for="emailList">Select from Saved Contacts</label>
                            <select name="emailList[]" id="emailList" class="rs-select2-input rs-input" multiple style="width: 100%;">
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

                        <div class="mod-divider">or enter single recipient details</div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--rs-space-4, 16px);">
                            <div class="form-group">
                                <label class="rs-label" for="manual-name">Recipient Name</label>
                                <input
                                    type="text"
                                    id="manual-name"
                                    name="name"
                                    value="<?= Html::encode($formData['name'] ?? '') ?>"
                                    class="rs-input"
                                    maxlength="255"
                                    placeholder="Full Name" />
                            </div>
                            <div class="form-group">
                                <label class="rs-label" for="manual-email">Recipient Email</label>
                                <input
                                    type="email"
                                    id="manual-email"
                                    name="email"
                                    value="<?= Html::encode($formData['email'] ?? '') ?>"
                                    class="rs-input <?= isset($errors['email']) ? 'rs-input--error' : '' ?>"
                                    maxlength="255"
                                    placeholder="email@example.com" />
                                <?php if (isset($errors['email'])): ?>
                                    <span class="rs-text-danger" style="font-size: 12px; display: block; margin-top: 4px;"><?= Html::encode($errors['email']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="margin-top: var(--rs-space-4, 16px);">
                            <label class="rs-label" for="bccEmailList">Send BCC copy to</label>
                            <select name="bccEmailList[]" id="bccEmailList" class="rs-select2-input rs-input" multiple style="width: 100%;">
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

                <!-- Message Body & Content Card -->
                <div class="rs-card">
                    <div class="rs-card__header">
                        <h3 class="rs-card__title">Campaign Message</h3>
                    </div>
                    <div class="rs-card__body">
                        <?php if ($currentUser->isAdmin() && !empty($accounts)): ?>
                            <div style="max-width: 400px; margin-bottom: var(--rs-space-4, 16px);">
                                <label class="rs-label" for="account_id">From Account</label>
                                <select name="account_id" id="account_id" class="rs-select rs-input">
                                    <?php foreach ($accounts as $acc): ?>
                                        <option value="<?= $acc->getId() ?>" <?= ((int) ($formData['account_id'] ?? 0) === $acc->getId()) ? 'selected' : '' ?>>
                                            <?= Html::encode($acc->getBusinessName() ?? "Account #{$acc->getId()}") ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--rs-space-4, 16px); margin-bottom: var(--rs-space-4, 16px);">
                            <div class="form-group">
                                <label class="rs-label" for="campaign-subject">Subject Line</label>
                                <input
                                    type="text"
                                    id="campaign-subject"
                                    name="subject"
                                    value="<?= Html::encode($formData['subject'] ?? '') ?>"
                                    class="rs-input <?= isset($errors['subject']) ? 'rs-input--error' : '' ?>"
                                    maxlength="255"
                                    placeholder="Email subject line" />
                                <?php if (isset($errors['subject'])): ?>
                                    <span class="rs-text-danger" style="font-size: 12px; display: block; margin-top: 4px;"><?= Html::encode($errors['subject']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label class="rs-label" for="campaign-template">Select Base Template</label>
                                <select
                                    name="template"
                                    id="campaign-template"
                                    class="rs-select rs-input <?= isset($errors['template']) ? 'rs-input--error' : '' ?>"
                                    onchange="onTemplateChange(this.value)">
                                    <option value="">Select a template...</option>
                                    <?php foreach ($templates as $tpl): ?>
                                        <option value="<?= $tpl->getId() ?>" <?= ((string) $tpl->getId() === ($formData['template_id'] ?? '')) ? 'selected' : '' ?>>
                                            <?= Html::encode($tpl->getName()) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['template'])): ?>
                                    <span class="rs-text-danger" style="font-size: 12px; display: block; margin-top: 4px;"><?= Html::encode($errors['template']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="rs-label" for="campaign-message">Email Message Content</label>
                            <div class="mod-tinymce-body">
                                <textarea
                                    name="message"
                                    id="campaign-message"
                                    rows="22"
                                    class="rs-input <?= isset($errors['message']) ? 'rs-input--error' : '' ?>"
                                    style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6;"><?= Html::encode($formData['message'] ?? '') ?></textarea>
                            </div>
                            <?php if (isset($errors['message'])): ?>
                                <span class="rs-text-danger" style="font-size: 12px; display: block; margin-top: 4px;"><?= Html::encode($errors['message']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="rs-card__footer rs-d-flex rs-align-items-center rs-gap-3 rs-p-4" style="border-top: 1px solid var(--rs-border, #E2E8F0); padding: 16px 24px;">
                        <button type="submit" class="rs-btn rs-btn--primary">
                            Queue Campaign
                        </button>
                        <a href="<?= \App\Environment::url('/email/templates') ?>" class="rs-btn rs-btn--secondary">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>

            <!-- Help & Tips Sidebar (4 Columns) -->
            <div style="grid-column: span 4;" class="rs-lg-col-span-4">
                <!-- Setup Guide Card -->
                <div class="rs-card rs-mb-6">
                    <div class="rs-card__header">
                        <h3 class="rs-card__title">Campaign Setup Guide</h3>
                    </div>
                    <div class="rs-card__body" style="padding: var(--rs-space-5, 20px); font-size: var(--rs-font-size-sm, 13px); line-height: 1.6; color: var(--rs-text-secondary, #64748b);">
                        <div style="margin-bottom: var(--rs-space-5, 20px);">
                            <h4 style="font-size: var(--rs-font-size-base, 14px); font-weight: 600; margin-bottom: var(--rs-space-2, 8px); color: var(--rs-text-main, #0f172a);">1. Select Target</h4>
                            <p style="margin: 0; font-size: var(--rs-font-size-sm, 13px); color: var(--rs-text-secondary, #64748b); line-height: 1.55;">Select one or more saved contacts from the searchable list, or enter custom recipient details below.</p>
                        </div>

                        <div style="margin-bottom: var(--rs-space-5, 20px);">
                            <h4 style="font-size: var(--rs-font-size-base, 14px); font-weight: 600; margin-bottom: var(--rs-space-2, 8px); color: var(--rs-text-main, #0f172a);">2. Use Email Templates</h4>
                            <p style="margin: 0; font-size: var(--rs-font-size-sm, 13px); color: var(--rs-text-secondary, #64748b); line-height: 1.55;">Choosing a base template will automatically populate the message body editor. You can then edit it as needed.</p>
                        </div>

                        <div>
                            <h4 style="font-size: var(--rs-font-size-base, 14px); font-weight: 600; margin-bottom: var(--rs-space-2, 8px); color: var(--rs-text-main, #0f172a);">3. Queueing Delivery</h4>
                            <p style="margin: 0; font-size: var(--rs-font-size-sm, 13px); color: var(--rs-text-secondary, #64748b); line-height: 1.55;">Clicking "Queue Campaign" validates details and stores emails in the outgoing delivery queue for dispatch.</p>
                        </div>
                    </div>
                </div>

                <!-- Personalization Merge Tags Card -->
                <div class="rs-card">
                    <div class="rs-card__header">
                        <h3 class="rs-card__title">Personalization Merge Tags</h3>
                    </div>
                    <div class="rs-card__body rs-p-4" style="padding: 16px;">
                        <p class="rs-text-muted" style="font-size: 12px; margin-bottom: 12px;">
                            Click to append tag into the message body:
                        </p>
                        <div class="rs-d-flex rs-flex-column rs-gap-2">
                            <?php foreach ($supportedTags as $tag => $info): ?>
                                <button
                                    type="button"
                                    class="rs-btn rs-btn--sm rs-btn--outline rs-d-flex rs-align-items-center rs-justify-content-between"
                                    onclick="insertCampaignTag('<?= Html::encode($tag) ?>')"
                                    style="text-align: left; width: 100%; display: flex; justify-content: space-between; align-items: center;">
                                    <code><?= Html::encode($tag) ?></code>
                                    <span class="rs-text-muted" style="font-size: 11px;"><?= Html::encode($info['label']) ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="<?= \App\Shared\AssetVersion::append(\App\Environment::url('/themes/modern/js/vendor/tinymce/tinymce.min.js')) ?>"></script>
<script>
    (function() {
        function initSendPage() {
            // Initialize Select2 on recipients
            if (window.jQuery && jQuery.fn.select2) {
                jQuery('#emailList').select2({
                    placeholder: 'Search and select contacts...',
                    allowClear: true,
                    width: '100%'
                });
                jQuery('#bccEmailList').select2({
                    placeholder: 'Search and select BCC contacts...',
                    allowClear: true,
                    width: '100%'
                });
            }

            // Initialize TinyMCE on message content
            if (typeof tinymce !== 'undefined') {
                if (tinymce.get('campaign-message')) {
                    tinymce.get('campaign-message').remove();
                }

                tinymce.init({
                    selector: '#campaign-message',
                    license_key: 'gpl',
                    menubar: false,
                    plugins: [
                        'advlist', 'autolink', 'lists', 'link', 'charmap', 'preview',
                        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                        'insertdatetime', 'media', 'table', 'image'
                    ],
                    toolbar: [
                        'undo redo | styles | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image code',
                        'insertName insertEmail'
                    ],
                    height: 480,
                    min_height: 380,
                    resize: 'vertical',
                    elementpath: false,
                    statusbar: true,
                    relative_urls: false,
                    remove_script_host: false,
                    convert_urls: true,
                    branding: false,
                    promotion: false,
                    setup: function(editor) {
                        editor.ui.registry.addButton('insertName', {
                            text: 'Name',
                            onAction: function() {
                                editor.insertContent('##NAME##');
                            }
                        });
                        editor.ui.registry.addButton('insertEmail', {
                            text: 'Email',
                            onAction: function() {
                                editor.insertContent('##EMAIL##');
                            }
                        });
                        editor.on('change keyup input', function() {
                            editor.save();
                        });
                    }
                });
            }

            var form = document.querySelector('form[action*="email/send"]');
            if (form) {
                form.addEventListener('submit', function() {
                    if (window.tinymce) {
                        tinymce.triggerSave();
                    }
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initSendPage);
        } else {
            initSendPage();
        }
    })();

    function onTemplateChange(templateId) {
        if (!templateId) return;
        const div = document.getElementById('tpl-' + templateId);
        if (!div) return;
        const content = div.innerHTML;
        if (window.tinymce && tinymce.get('campaign-message')) {
            tinymce.get('campaign-message').setContent(content);
            tinymce.get('campaign-message').save();
        } else {
            const textarea = document.getElementById('campaign-message');
            if (textarea) textarea.value = content;
        }
    }

    function insertCampaignTag(tag) {
        if (window.tinymce && tinymce.get('campaign-message')) {
            tinymce.get('campaign-message').insertContent(tag);
            tinymce.get('campaign-message').save();
            return;
        }
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