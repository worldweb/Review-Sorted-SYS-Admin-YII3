<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var array<int, App\Domain\SocialConnection\ServiceConfig> $configs
 * @var int $totalCount
 * @var int $page
 * @var int $pageCount
 * @var int $pageSize
 * @var App\Domain\User\User $currentUser
 * @var Yiisoft\Session\Flash\Flash $flash
 * @var string $csrfToken
 */

$this->setTitle('Google Accounts');
?>

<div class="rs-container">
    <?= $this->render('../partials/_header', [
        'title' => 'Google Accounts',
        'subtitle' => 'Connect and manage your Google Business Profile locations and review integration.'
    ]) ?>

    <?php if ($flash->has('success')): ?>
        <div class="rs-alert rs-alert--success rs-mb-4" role="alert">
            <?= Html::encode((string) $flash->get('success')) ?>
        </div>
    <?php endif; ?>
    <?php if ($flash->has('error')): ?>
        <div class="rs-alert rs-alert--danger rs-mb-4" role="alert">
            <?= Html::encode((string) $flash->get('error')) ?>
        </div>
    <?php endif; ?>
    <?php if ($flash->has('info')): ?>
        <div class="rs-alert rs-alert--info rs-mb-4" role="alert">
            <?= Html::encode((string) $flash->get('info')) ?>
        </div>
    <?php endif; ?>

    <!-- Connection Status Card -->
    <div class="rs-section rs-mb-5">
        <div class="rs-card rs-card--shadow rs-p-4" style="background:#fff; border-radius:8px; border:1px solid #e2e8f0; padding: 1.5rem; margin-bottom: 1.5rem;">
            <div class="rs-card__header rs-mb-3 rs-d-flex rs-align-items-center rs-justify-content-between" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <div style="display: flex; align-items: center;">
                    <svg width="24" height="24" viewBox="0 0 24 24" style="margin-right: 0.5rem;"><path fill="#EA4335" d="M12 5c1.54 0 2.94.57 4.03 1.51l3.03-3.03C17.22 1.73 14.77 1 12 1 7.37 1 3.4 3.63 1.48 7.45l3.68 2.85C6.04 7.21 8.78 5 12 5z"/><path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47c-.29 1.48-1.14 2.73-2.4 3.58l3.69 2.86c2.16-1.99 3.41-4.92 3.41-8.68z"/><path fill="#FBBC05" d="M5.16 14.86c-.24-.71-.38-1.47-.38-2.26s.14-1.55.38-2.26L1.48 7.45C.54 9.32 0 11.43 0 13.68s.54 4.36 1.48 6.23l3.68-2.85z"/><path fill="#34A853" d="M12 23c3.24 0 5.95-1.08 7.93-2.91l-3.69-2.86c-1.07.72-2.45 1.16-4.24 1.16-3.22 0-5.96-2.21-6.84-5.3L1.48 15.94C3.4 19.76 7.37 23 12 23z"/></svg>
                    <h3 class="rs-card__title rs-m-0" style="font-size: 1.25rem; font-weight: 700; margin: 0;">Google Business Profile Connection</h3>
                </div>
                <?= $this->render('../partials/_status_badge', [
                    'status' => $totalCount > 0 ? 'Connected' : 'Not Linked'
                ]) ?>
            </div>
            <hr class="rs-mb-3" style="border: 0; border-top: 1px solid #e2e8f0; margin: 1rem 0;" />
            <div class="rs-card__body" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <p class="rs-m-0" style="color: #475569; font-size: 0.95rem; margin: 0;">
                    Authorize Google Business Profile to sync real customer reviews and publish local store posts directly onto Google Maps and Search.
                </p>
                <a href="/sys-yii3/settings/google/setup" class="rs-btn rs-btn--primary" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.65rem 1.25rem; font-weight: 600; text-decoration: none; border-radius: 6px;">
                    Connect Google Location
                </a>
            </div>
        </div>
    </div>

    <!-- Google Locations Table -->
    <div class="rs-section">
        <?php if (empty($configs)): ?>
            <?= $this->render('../partials/_empty_state', [
                'message' => 'No Google Business Profile locations found. Authorize your Google account to discover locations.',
                'actionUrl' => '/sys-yii3/settings/google/setup',
                'actionLabel' => 'Connect Google Location'
            ]) ?>
        <?php else: ?>
            <div class="rs-card rs-card--shadow" style="background:#fff; border-radius:8px; border:1px solid #e2e8f0; overflow:hidden;">
                <?= $this->render('../partials/_card_header', [
                    'title' => 'Google Profiles',
                    'subtitle' => 'Profiles and locations currently integrated with your account'
                ]) ?>
                <div class="rs-table-wrapper" style="overflow-x: auto;">
                    <table class="rs-table rs-table--hover" style="width: 100%; min-width: 720px; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                <th style="padding: 12px 16px; width: 30%; min-width: 180px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b;">Business / Location Name</th>
                                <th style="padding: 12px 16px; width: 28%; min-width: 180px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b;">Google Location ID</th>
                                <th style="padding: 12px 16px; width: 12%; min-width: 90px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b;">Status</th>
                                <th style="padding: 12px 16px; width: 12%; min-width: 100px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b;">Connected On</th>
                                <th style="padding: 12px 16px; width: 18%; min-width: 170px; text-align: right; font-size: 0.8rem; text-transform: uppercase; color: #64748b;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($configs as $cfg): ?>
                                <?php
                                    $displayName = $cfg->getDisplayName();
                                    $label = $cfg->getLabel();
                                    $locationId = $cfg->getLocationId();
                                ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 14px 16px;">
                                        <div style="font-weight: 600; color: #0f172a; font-size: 0.95rem;"><?= Html::encode($displayName) ?></div>
                                        <?php if (!empty($label) && $label !== $displayName): ?>
                                            <div style="font-size: 0.75rem; color: #64748b; margin-top: 2px;">
                                                Label: <?= Html::encode($label) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($cfg->getEmailLowReview())): ?>
                                            <div style="font-size: 0.75rem; color: #64748b; margin-top: 2px;">
                                                Alert Email: <?= Html::encode($cfg->getEmailLowReview()) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 14px 16px; font-family: monospace; font-size: 0.85rem; color: #475569;">
                                        <?= Html::encode($locationId) ?>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <?= $this->render('../partials/_status_badge', [
                                            'status' => $cfg->isLinked() ? 'Connected' : 'Not Linked'
                                        ]) ?>
                                    </td>
                                    <td style="padding: 14px 16px; font-size: 0.85rem; color: #64748b;">
                                        <?= $cfg->getCreatedAt() ? date('M d, Y', $cfg->getCreatedAt()) : 'N/A' ?>
                                    </td>
                                    <td style="padding: 14px 16px; text-align: right; white-space: nowrap;">
                                        <div style="display: inline-flex; gap: 6px; justify-content: flex-end;">
                                            <a href="/sys-yii3/settings/google/view?id=<?= $cfg->getId() ?>" class="rs-btn rs-btn--sm rs-btn--outline" title="View Details">
                                                View
                                            </a>
                                            <a href="/sys-yii3/settings/google/update?id=<?= $cfg->getId() ?>" class="rs-btn rs-btn--sm rs-btn--secondary" title="Edit Settings">
                                                Edit
                                            </a>
                                            <a href="/sys-yii3/settings/google/revoke?id=<?= $cfg->getId() ?>" class="rs-btn rs-btn--sm rs-btn--ghost rs-text-danger" onclick="return confirm('Are you sure you want to disconnect this location?');" title="Disconnect">
                                                Disconnect
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($pageCount > 1): ?>
                    <div style="padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e2e8f0;">
                        <span style="font-size: 0.875rem; color: #64748b;">Showing <?= count($configs) ?> of <?= $totalCount ?> Google locations</span>
                        <div style="display: flex; gap: 4px;">
                            <?php for ($p = 1; $p <= $pageCount; $p++): ?>
                                <a href="/sys-yii3/settings/google?page=<?= $p ?>" class="rs-btn rs-btn--sm <?= $p === $page ? 'rs-btn--primary' : 'rs-btn--secondary' ?>" style="min-width: 32px; text-align: center;">
                                    <?= $p ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
