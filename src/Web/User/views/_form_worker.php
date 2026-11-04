<?php

declare(strict_types=1);

/**
 * @var array $data
 * @var array $errors
 * @var bool $limitReached
 * @var string|null $csrfToken
 */
?>
<div class="rs-card">
    <form method="post" class="rs-form">
        <?php if (!empty($csrfToken)): ?>
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
        <?php endif; ?>

        <div class="rs-card__body rs-p-5">
            <?php if (!empty($limitReached)): ?>
                <div class="rs-alert rs-alert--danger rs-mb-4" style="background-color: #FEE2E2; color: #991B1B; padding: 12px 16px; border-radius: 6px; font-size: 14px;">
                    <?= htmlspecialchars($errors['limit'] ?? 'Account reached user limit.') ?>
                </div>
            <?php endif; ?>

            <div class="rs-row rs-gap-5" style="display: flex; gap: 32px;">
                <div class="rs-col" style="flex: 1;">
                    <div class="rs-form-group rs-mb-4">
                        <label for="user-name" class="rs-label">Name</label>
                        <input type="text" id="user-name" name="name" class="rs-input" value="<?= htmlspecialchars((string) ($data['name'] ?? '')) ?>" <?= !empty($limitReached) ? 'disabled' : 'required' ?> placeholder="Enter full name">
                        <?php if (isset($errors['name'])): ?>
                            <div class="rs-help-block rs-text-danger" style="font-size: 13px; margin-top: 4px;"><?= htmlspecialchars($errors['name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="rs-form-group rs-mb-4">
                        <label for="user-email" class="rs-label">Email</label>
                        <input type="email" id="user-email" name="email" class="rs-input" value="<?= htmlspecialchars((string) ($data['email'] ?? '')) ?>" <?= !empty($limitReached) ? 'disabled' : 'required' ?> placeholder="Enter email address">
                        <?php if (isset($errors['email'])): ?>
                            <div class="rs-help-block rs-text-danger" style="font-size: 13px; margin-top: 4px;"><?= htmlspecialchars($errors['email']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div style="display: flex; gap: 16px;">
                        <div style="flex: 1;">
                            <div class="rs-form-group rs-mb-4">
                                <label for="user-password" class="rs-label">Password</label>
                                <input type="password" id="user-password" name="password" class="rs-input" <?= !empty($limitReached) ? 'disabled' : 'required' ?> placeholder="Enter password">
                                <?php if (isset($errors['password'])): ?>
                                    <div class="rs-help-block rs-text-danger" style="font-size: 13px; margin-top: 4px;"><?= htmlspecialchars($errors['password']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="flex: 1;">
                            <div class="rs-form-group rs-mb-4">
                                <label for="user-password-conf" class="rs-label">Confirm Password</label>
                                <input type="password" id="user-password-conf" name="password_conf" class="rs-input" <?= !empty($limitReached) ? 'disabled' : 'required' ?> placeholder="Confirm password">
                                <?php if (isset($errors['password_conf'])): ?>
                                    <div class="rs-help-block rs-text-danger" style="font-size: 13px; margin-top: 4px;"><?= htmlspecialchars($errors['password_conf']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rs-col" style="max-width: 300px; border-left: 1px solid var(--rs-border-muted, #CED4DA); padding-left: 24px;">
                    <h3 class="rs-text-md rs-mb-4" style="font-size: 16px; font-weight: 600; margin-top: 0;">Status</h3>
                    <div class="rs-form-group rs-mb-4">
                        <label for="user-status" class="rs-label">Status</label>
                        <select id="user-status" name="status" class="rs-input" style="height: 44px;" <?= !empty($limitReached) ? 'disabled' : '' ?>>
                            <option value="1" <?= ((int) ($data['status'] ?? 1)) === 1 ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= ((int) ($data['status'] ?? 1)) === 0 ? 'selected' : '' ?>>Disabled</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="rs-card__footer rs-d-flex rs-justify-content-end rs-gap-3" style="padding: 16px 24px; border-top: 1px solid var(--rs-border-muted, #CED4DA); display: flex; justify-content: flex-end; gap: 12px;">
            <a href="<?= \App\Environment::url('/users') ?>" class="rs-btn rs-btn--outline" style="text-decoration: none;">Cancel</a>
            <?php if (empty($limitReached)): ?>
                <button type="submit" class="rs-btn rs-btn--primary">Save User</button>
            <?php endif; ?>
        </div>
    </form>
</div>
