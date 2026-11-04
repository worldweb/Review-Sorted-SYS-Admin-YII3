<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Role & Permission Management Tab View (Matches Yii 2 Modern Theme _tab_roles.php)
 *
 * @var array<string, array{
 *     key: string,
 *     label: string,
 *     description: string,
 *     category: string,
 *     editable: bool,
 *     state: string,
 *     permissions: string[]
 * }> $roleMatrix
 * @var string $targetRole
 * @var array<string, string> $roleOptions
 * @var string $csrfToken
 */
?>

<div class="rs-card rs-mb-6" style="background: #ffffff; border: 1px solid var(--rs-border, #E2E8F0); border-radius: 8px; padding: 24px; max-width: 1000px;">
    <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mb-4" style="border-bottom: 1px solid var(--rs-border, #E2E8F0); padding-bottom: 16px;">
        <div>
            <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: var(--rs-text-main, #0F172A);">
                🛡️ Role &amp; Permission Management
            </h3>
            <p class="rs-text-muted" style="margin: 4px 0 0 0; font-size: 13px;">
                Manage application feature access for specific user roles. (Root Super Administrator privileges and tenant data isolation remain strictly protected).
            </p>
        </div>
        
        <!-- TARGET ROLE SELECTOR DROPDOWN -->
        <div style="min-width: 240px;">
            <label style="font-size: 12px; font-weight: 600; color: #475569; display: block; margin-bottom: 4px;">
                Configuring Role:
            </label>
            <select class="rs-input rs-select" style="font-weight: 600; color: #0f172a; width: 100%;" onchange="location.href = <?= json_encode(\App\Environment::url('/global-settings?section=roles&target_role=')) ?> + encodeURIComponent(this.value);">
                <?php foreach ($roleOptions as $roleKey => $roleLabel): ?>
                    <option value="<?= Html::encode($roleKey) ?>" <?= $targetRole === $roleKey ? 'selected' : '' ?>>
                        <?= Html::encode($roleLabel) ?> (<?= Html::encode($roleKey) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- MAIN ROLE PERMISSIONS FORM -->
    <form id="role-permissions-form" action="<?= \App\Environment::url('/global-settings?section=roles&target_role=' . urlencode($targetRole)) ?>" method="post">
        <?php if (!empty($csrfToken)): ?>
            <input type="hidden" name="_csrf" value="<?= Html::encode($csrfToken) ?>">
        <?php endif; ?>
        <input type="hidden" name="RolePermissionsForm[target_role]" value="<?= Html::encode($targetRole) ?>">

        <div class="rs-table-wrapper" style="overflow-x: auto;">
            <table class="rs-table rs-table--hover" style="width: 100%; margin-bottom: 0;">
                <thead>
                    <tr style="background-color: #f8fafc; border-bottom: 2px solid var(--rs-border, #E2E8F0);">
                        <th style="width: 60px; text-align: center; font-size: 12px; color: #475569; text-transform: uppercase;">Grant</th>
                        <th style="font-size: 12px; color: #475569; text-transform: uppercase;">Feature / Capability</th>
                        <th style="font-size: 12px; color: #475569; text-transform: uppercase;">Category</th>
                        <th style="font-size: 12px; color: #475569; text-transform: uppercase;">Current Authorization State</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roleMatrix as $featureKey => $item): ?>
                        <?php
                        $isAllowed = ($item['state'] === 'allowed' || $item['state'] === 'inherited');
                        $isProtected = ($item['state'] === 'protected' || !$item['editable']);
                        $isInherited = ($item['state'] === 'inherited');
                        ?>
                        <tr style="<?= $isProtected ? 'background-color: #fcfcfc;' : '' ?>">
                            <td style="text-align: center; vertical-align: middle;">
                                <?php if ($isProtected): ?>
                                    <input type="checkbox" disabled checked style="cursor: not-allowed; opacity: 0.6;">
                                <?php else: ?>
                                    <input type="checkbox" 
                                           name="RolePermissionsForm[selected_features][]" 
                                           value="<?= Html::encode($featureKey) ?>" 
                                           <?= $isAllowed ? 'checked' : '' ?>
                                           style="width: 18px; height: 18px; cursor: pointer;">
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="font-size: 14px; color: var(--rs-text-main, #0F172A);"><?= Html::encode($item['label']) ?></strong>
                                <p style="margin: 2px 0 0 0; font-size: 12px; color: #64748b;"><?= Html::encode($item['description']) ?></p>
                            </td>
                            <td>
                                <span class="rs-badge rs-badge--default" style="font-size: 11px; padding: 4px 8px; background-color: #e2e8f0; color: #334155; font-weight: 500;">
                                    <?= Html::encode($item['category']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($item['state'] === 'protected'): ?>
                                    <span class="rs-badge" style="font-size: 11px; padding: 4px 10px; background-color: #334155; color: #ffffff;">
                                        🔒 System Protected
                                    </span>
                                <?php elseif ($item['state'] === 'inherited'): ?>
                                    <span class="rs-badge rs-badge--info" style="font-size: 11px; padding: 4px 10px; background-color: #0284c7; color: #ffffff;">
                                        ↳ Allowed (Inherited)
                                    </span>
                                <?php elseif ($item['state'] === 'allowed'): ?>
                                    <span class="rs-badge rs-badge--success" style="font-size: 11px; padding: 4px 10px; background-color: #16a34a; color: #ffffff;">
                                        ✓ Allowed (Direct)
                                    </span>
                                <?php else: ?>
                                    <span class="rs-badge rs-badge--ghost" style="font-size: 11px; padding: 4px 10px; background-color: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1;">
                                        ✗ Not Granted
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mt-6" style="padding-top: 16px; border-top: 1px solid var(--rs-border, #E2E8F0);">
            <span style="font-size: 12px; color: #64748b;">
                💡 Changes take effect immediately. RBAC hierarchy and route permissions are enforced server-side.
            </span>
            <button type="submit" class="rs-btn rs-btn--primary" style="padding: 10px 24px; font-weight: 600; border-radius: 6px;">
                Save Role Permissions
            </button>
        </div>
    </form>
</div>
