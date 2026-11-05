<?php

declare(strict_types=1);

use App\Domain\WhatsApp\WhatsAppTemplate;
use Yiisoft\Html\Html;

/**
 * @var string $actionUrl
 * @var string $name
 * @var string $text
 * @var int $status
 * @var int|null $targetAccountId
 * @var array<int, \App\Domain\Account\Account> $accounts
 * @var array<int, WhatsAppTemplate> $templates
 * @var array<string, array{label: string, scope: string, example: string}> $supportedTags
 * @var array<string, string> $errors
 * @var WhatsAppTemplate|null $template
 * @var string|null $csrfToken
 */

$isProtected = $template?->isProtected() ?? false;
$templates = $templates ?? [];
?>

<!-- Hidden template bodies for JS template selection populator -->
<?php foreach ($templates as $tpl): ?>
    <div id="tpl-data-<?= $tpl->getId() ?>" style="display:none;"><?= Html::encode($tpl->getText()) ?></div>
<?php endforeach; ?>

<form method="post" action="<?= Html::encode($actionUrl) ?>" id="whatsapp-template-form">
    <?php if (!empty($csrfToken)): ?>
        <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
    <?php endif; ?>
    <?php if ($template !== null): ?>
        <input type="hidden" name="id" value="<?= $template->getId() ?>">
    <?php endif; ?>

    <div class="rs-grid rs-grid--cols-12 rs-gap-6" style="display: grid; grid-template-columns: repeat(12, 1fr); gap: var(--rs-space-6, 24px);">
        <!-- Main Form Area (8 cols) -->
        <div style="grid-column: span 8; min-width: 0;">
            <div class="rs-card">
                <div class="rs-card__header">
                    <h3 class="rs-m-0" style="font-size: 16px;">WhatsApp Template Details</h3>
                </div>
                <div class="rs-card__body">
                    <?php if (!empty($accounts)): ?>
                        <div class="rs-form-group rs-mb-4" style="max-width: 400px;">
                            <label class="rs-form-label" for="account_id">Target Workspace / Account</label>
                            <select name="account_id" id="account_id" class="rs-input">
                                <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= $acc->getId() ?>" <?= ($targetAccountId === $acc->getId()) ? 'selected' : '' ?>>
                                        <?= Html::encode($acc->getBusinessName() ?? "Workspace #{$acc->getId()}") ?> (#<?= $acc->getId() ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($templates)): ?>
                        <div class="rs-form-group rs-mb-4" style="max-width: 450px;">
                            <label class="rs-form-label" for="copy-template-select">Load from Existing Template</label>
                            <select id="copy-template-select" class="rs-input">
                                <option value="">-- Choose a template to copy content --</option>
                                <?php foreach ($templates as $tpl): ?>
                                    <option value="<?= $tpl->getId() ?>"><?= Html::encode($tpl->getName()) ?> <?= $tpl->isProtected() ? '(Protected)' : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="rs-text-muted" style="font-size: 11px;">Selecting a template will populate the message editor below.</span>
                        </div>
                    <?php endif; ?>

                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--rs-space-4, 16px); margin-bottom: var(--rs-space-4, 16px);">
                        <div class="rs-form-group">
                            <label class="rs-form-label" for="template-name">Template Name *</label>
                            <input
                                type="text"
                                name="name"
                                id="template-name"
                                value="<?= Html::encode($name) ?>"
                                class="rs-input <?= isset($errors['name']) ? 'rs-input--error' : '' ?>"
                                placeholder="e.g. WhatsApp Feedback Request"
                                required
                            />
                            <?php if (isset($errors['name'])): ?>
                                <span class="rs-text-danger" style="font-size: 12px;"><?= Html::encode($errors['name']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="rs-form-group">
                            <label class="rs-form-label" for="template-status">Status</label>
                            <select name="status" id="template-status" class="rs-input" <?= $isProtected ? 'disabled' : '' ?>>
                                <option value="<?= WhatsAppTemplate::STATUS_ACTIVE ?>" <?= ($status === WhatsAppTemplate::STATUS_ACTIVE) ? 'selected' : '' ?>>Active</option>
                                <option value="<?= WhatsAppTemplate::STATUS_INACTIVE ?>" <?= ($status === WhatsAppTemplate::STATUS_INACTIVE) ? 'selected' : '' ?>>Disabled</option>
                            </select>
                            <?php if ($isProtected): ?>
                                <span class="rs-text-muted" style="font-size: 11px;">Protected system templates must remain active.</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="rs-form-group rs-mt-4">
                        <label class="rs-form-label" for="template-text">Template HTML Body *</label>
                        <p class="rs-text-muted" style="font-size: 12px; margin-bottom: 8px;">
                            Enter the HTML content for this template. Use double-hash merge tags (e.g. <code>##NAME##</code>) for personalized replacements.
                        </p>
                        <textarea
                            name="text"
                            id="template-text"
                            rows="18"
                            class="rs-input <?= isset($errors['text']) ? 'rs-input--error' : '' ?>"
                            style="font-family: monospace; font-size: 13px; line-height: 1.5;"
                            placeholder="<p>Hi ##NAME##,</p><p>Thank you for choosing our services...</p>"
                        ><?= Html::encode($text) ?></textarea>
                        <?php if (isset($errors['text'])): ?>
                            <span class="rs-text-danger" style="font-size: 12px;"><?= Html::encode($errors['text']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="rs-card__footer rs-d-flex rs-align-items-center rs-gap-3 rs-p-4" style="border-top: 1px solid var(--rs-border, #E2E8F0);">
                    <button type="submit" class="rs-btn rs-btn--primary">
                        <i class="fa-solid fa-check rs-mr-1"></i> Save Template
                    </button>
                    <a href="<?= \App\Environment::url('/whatsapp/templates') ?>" class="rs-btn rs-btn--ghost">Cancel</a>
                </div>
            </div>
        </div>

        <!-- Sidebar / Merge Tags & Guidelines (4 cols) -->
        <div style="grid-column: span 4;">
            <div class="rs-card rs-mb-6">
                <div class="rs-card__header">
                    <h3 class="rs-m-0" style="font-size: 15px;">Available Merge Tags</h3>
                </div>
                <div class="rs-card__body rs-p-4">
                    <p class="rs-text-muted" style="font-size: 12px; margin-bottom: 12px;">
                        Click <strong>Insert</strong> to append the tag into your template text, or <strong>Copy</strong> to clipboard.
                    </p>

                    <div class="rs-d-flex rs-flex-column rs-gap-3">
                        <?php foreach ($supportedTags as $tag => $info): ?>
                            <div class="rs-p-3" style="border: 1px solid var(--rs-border, #E2E8F0); border-radius: 6px; background-color: var(--rs-bg-card, #FFFFFF);">
                                <div class="rs-d-flex rs-align-items-center rs-justify-content-between rs-mb-1">
                                    <code class="rs-font-semibold" style="color: #2563EB; font-size: 13px;"><?= Html::encode($tag) ?></code>
                                    <div class="rs-d-flex rs-gap-1">
                                        <button type="button" class="rs-btn rs-btn--xs rs-btn--outline" onclick="insertTag('<?= Html::encode($tag) ?>')">
                                            Insert
                                        </button>
                                        <button type="button" class="rs-btn rs-btn--xs rs-btn--ghost" onclick="copyTag('<?= Html::encode($tag) ?>', this)">
                                            Copy
                                        </button>
                                    </div>
                                </div>
                                <div class="rs-text-muted" style="font-size: 11px;">
                                    <strong><?= Html::encode($info['label']) ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="rs-card">
                <div class="rs-card__header">
                    <h3 class="rs-m-0" style="font-size: 15px;">Help & Guidelines</h3>
                </div>
                <div class="rs-card__body rs-p-4" style="font-size: 12px; line-height: 1.6;">
                    <p class="rs-mb-2">
                        <strong>Exact Syntax:</strong> Merge tags must retain their exact double-hash format (e.g. <code>##NAME##</code>).
                    </p>
                    <p class="rs-mb-2">
                        <strong>Personalization:</strong> When campaigns are dispatched, tags are automatically replaced with recipient details.
                    </p>
                    <p class="rs-m-0">
                        <strong>Protected Templates:</strong> System templates cannot be deleted to ensure automated review follow-ups function properly.
                    </p>
                </div>
            </div>
        </div>
    </div>
</form>

<script src="<?= \App\Shared\AssetVersion::append(\App\Environment::url('/themes/modern/js/vendor/tinymce/tinymce.min.js')) ?>"></script>
<script>
function initWhatsAppTemplateEditor() {
    if (typeof tinymce === 'undefined') {
        return;
    }

    if (tinymce.get('template-text')) {
        tinymce.get('template-text').remove();
    }

    tinymce.init({
        selector: '#template-text',
        license_key: 'gpl',
        menubar: false,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'image'
        ],
        toolbar: [
            'undo redo | styles | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image code',
            'insertName insertEmail insertReview insertRate insertLoc'
        ],
        height: 480,
        min_height: 400,
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
                onAction: function() { editor.insertContent('##NAME##'); }
            });
            editor.ui.registry.addButton('insertEmail', {
                text: 'Email',
                onAction: function() { editor.insertContent('##EMAIL##'); }
            });
            editor.ui.registry.addButton('insertReview', {
                text: 'Review',
                onAction: function() { editor.insertContent('##REVIEW##'); }
            });
            editor.ui.registry.addButton('insertRate', {
                text: 'Rate',
                onAction: function() { editor.insertContent('##RATE##'); }
            });
            editor.ui.registry.addButton('insertLoc', {
                text: 'Location',
                onAction: function() { editor.insertContent('##LOCATION##'); }
            });
            editor.on('change keyup input', function() {
                editor.save();
            });
        }
    });

    var form = document.querySelector('form[action*="whatsapp/template"]') || document.getElementById('whatsapp-template-form');
    if (form) {
        form.addEventListener('submit', function() {
            if (window.tinymce) {
                tinymce.triggerSave();
            }
        });
    }

    // Template selection populator
    var copySelect = document.getElementById('copy-template-select');
    if (copySelect) {
        copySelect.addEventListener('change', function () {
            var val = this.value;
            if (!val) return;
            var dataDiv = document.getElementById('tpl-data-' + val);
            if (!dataDiv) return;
            var text = dataDiv.textContent || dataDiv.innerText;

            if (window.tinymce && tinymce.get('template-text')) {
                tinymce.get('template-text').setContent(text);
                tinymce.get('template-text').save();
            } else {
                var textarea = document.getElementById('template-text');
                if (textarea) {
                    textarea.value = text;
                }
            }
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initWhatsAppTemplateEditor);
} else {
    initWhatsAppTemplateEditor();
}

function insertTag(tag) {
    if (window.tinymce && tinymce.get('template-text')) {
        tinymce.get('template-text').insertContent(tag);
        tinymce.get('template-text').save();
        return;
    }
    const textarea = document.getElementById('template-text');
    if (!textarea) return;
    const start = textarea.selectionStart || 0;
    const end = textarea.selectionEnd || 0;
    const val = textarea.value;
    textarea.value = val.substring(0, start) + tag + val.substring(end);
    textarea.focus();
    const newPos = start + tag.length;
    textarea.setSelectionRange(newPos, newPos);
}

function copyTag(tag, btn) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(tag).then(() => {
            const orig = btn.innerText;
            btn.innerText = 'Copied!';
            setTimeout(() => { btn.innerText = orig; }, 1200);
        });
    }
}
</script>
