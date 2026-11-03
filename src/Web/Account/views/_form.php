<?php

declare(strict_types=1);

/**
 * @var array $data
 * @var array $errors
 * @var string|null $csrfToken
 */
?>
<div class="rs-card">
    <form method="post" class="rs-form">
        <?php if (!empty($csrfToken)): ?>
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
        <?php endif; ?>

        <div class="rs-card__body rs-p-5" style="padding: 24px;">
            <div class="rs-row rs-gap-5" style="display: flex; gap: 32px;">
                <div class="rs-col" style="flex: 1;">
                    <div class="rs-form-group rs-mb-4" style="margin-bottom: 20px;">
                        <label for="acc-business-name" class="rs-label">Business / Workspace Name</label>
                        <input type="text" id="acc-business-name" name="business_name" class="rs-input" value="<?= htmlspecialchars((string) ($data['business_name'] ?? '')) ?>" required placeholder="Acme Corporation">
                        <?php if (isset($errors['business_name'])): ?>
                            <div class="rs-help-block rs-text-danger" style="font-size: 13px; color: #DC2626; margin-top: 4px;"><?= htmlspecialchars($errors['business_name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div style="display: flex; gap: 16px; margin-bottom: 20px;">
                        <div style="flex: 1;">
                            <label for="acc-first-name" class="rs-label">First Name</label>
                            <input type="text" id="acc-first-name" name="first_name" class="rs-input" value="<?= htmlspecialchars((string) ($data['first_name'] ?? '')) ?>" placeholder="John">
                        </div>
                        <div style="flex: 1;">
                            <label for="acc-last-name" class="rs-label">Last Name</label>
                            <input type="text" id="acc-last-name" name="last_name" class="rs-input" value="<?= htmlspecialchars((string) ($data['last_name'] ?? '')) ?>" placeholder="Doe">
                        </div>
                    </div>

                    <div style="display: flex; gap: 16px; margin-bottom: 20px;">
                        <div style="flex: 1;">
                            <label for="acc-email" class="rs-label">Contact Email</label>
                            <input type="email" id="acc-email" name="email" class="rs-input" value="<?= htmlspecialchars((string) ($data['email'] ?? '')) ?>" placeholder="contact@company.com">
                        </div>
                        <div style="flex: 1;">
                            <label for="acc-phone" class="rs-label">Contact Phone</label>
                            <input type="text" id="acc-phone" name="contact_number" class="rs-input" value="<?= htmlspecialchars((string) ($data['contact_number'] ?? '')) ?>" placeholder="+1 555-0123">
                        </div>
                    </div>

                    <div class="rs-form-group" style="margin-bottom: 20px;">
                        <label for="acc-website" class="rs-label">Website</label>
                        <input type="url" id="acc-website" name="website" class="rs-input" value="<?= htmlspecialchars((string) ($data['website'] ?? '')) ?>" placeholder="https://example.com">
                    </div>
                </div>

                <div class="rs-col" style="max-width: 300px; border-left: 1px solid var(--rs-border-muted, #CED4DA); padding-left: 24px;">
                    <h3 class="rs-text-md rs-mb-4" style="font-size: 16px; font-weight: 600; margin-top: 0;">Workspace Limits</h3>
                    
                    <div class="rs-form-group rs-mb-4" style="margin-bottom: 20px;">
                        <label for="acc-max-users" class="rs-label">Max Allowed Seats (Users)</label>
                        <input type="number" id="acc-max-users" name="max_users" class="rs-input" min="1" max="1000" value="<?= htmlspecialchars((string) ($data['max_users'] ?? 5)) ?>" style="height: 44px;">
                    </div>

                    <div class="rs-form-group rs-mb-4" style="margin-bottom: 20px;">
                        <label for="acc-status" class="rs-label">Status</label>
                        <select id="acc-status" name="status" class="rs-input" style="height: 44px;">
                            <option value="1" <?= ((int) ($data['status'] ?? 1)) === 1 ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= ((int) ($data['status'] ?? 1)) === 0 ? 'selected' : '' ?>>Suspended / Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="rs-card__footer rs-d-flex rs-justify-content-end rs-gap-3" style="padding: 16px 24px; border-top: 1px solid var(--rs-border-muted, #CED4DA); display: flex; justify-content: flex-end; gap: 12px;">
            <a href="/sys-yii3/accounts" class="rs-btn rs-btn--outline" style="text-decoration: none;">Cancel</a>
            <button type="submit" class="rs-btn rs-btn--primary">Save Workspace</button>
        </div>
    </form>
</div>
