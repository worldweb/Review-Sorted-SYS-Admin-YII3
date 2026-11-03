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
 * @var array<string, array{label: string, scope: string, example: string}> $supportedTags
 * @var array<string, string> $errors
 * @var WhatsAppTemplate|null $template
 * @var string|null $csrfToken
 */

$isProtected = $template?->isProtected() ?? false;
?>

<form method="post" action="<?= Html::encode($actionUrl) ?>">
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
                        <label class="rs-form-label" for="template-text">Message Text (Plain Text) *</label>
                        <p class="rs-text-muted" style="font-size: 12px; margin-bottom: 8px;">
                            WhatsApp messages are delivered as plain text. You can insert merge tags like <code>##NAME##</code> or <code>##PHONE##</code>.
                        </p>
                        <textarea
                            name="text"
                            id="template-text"
                            rows="10"
                            class="rs-input <?= isset($errors['text']) ? 'rs-input--error' : '' ?>"
                            style="font-size: 14px; line-height: 1.5;"
                            placeholder="Hello ##NAME##! Thank you for visiting us. Could you please take a moment to leave a review?"
                            required
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
                    <a href="/sys-yii3/whatsapp/templates" class="rs-btn rs-btn--ghost">Cancel</a>
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
                        Click <strong>Insert</strong> to append into the message text:
                    </p>

                    <div class="rs-d-flex rs-flex-column rs-gap-3">
                        <?php foreach ($supportedTags as $tag => $info): ?>
                            <div class="rs-p-3" style="border: 1px solid var(--rs-border, #E2E8F0); border-radius: 6px; background-color: var(--rs-bg-card, #FFFFFF);">
                                <div class="rs-d-flex rs-align-items-center rs-justify-content-between rs-mb-1">
                                    <code class="rs-font-semibold" style="color: #2563EB; font-size: 13px;"><?= Html::encode($tag) ?></code>
                                    <button type="button" class="rs-btn rs-btn--xs rs-btn--outline" onclick="insertTag('<?= Html::encode($tag) ?>')">
                                        Insert
                                    </button>
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
                    <h3 class="rs-m-0" style="font-size: 15px;">Plain Text Notes</h3>
                </div>
                <div class="rs-card__body rs-p-4" style="font-size: 12px; line-height: 1.6;">
                    <p class="rs-mb-2">
                        <strong>Formatting:</strong> WhatsApp supports standard markdown: <code>*bold*</code>, <code>_italic_</code>, <code>~strikethrough~</code>, <code>`code`</code>.
                    </p>
                    <p class="rs-m-0">
                        <strong>Delivery:</strong> Messages are sent via Meta WhatsApp Cloud API to normalized E.164 phone numbers.
                    </p>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function insertTag(tag) {
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
</script>
