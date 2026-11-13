<?php

declare(strict_types=1);

use App\Domain\QrCode\QrCode;
use Yiisoft\Html\Html;

/**
 * QR Code Studio Detail & Analytics View (Yii 3 Modern Theme)
 * Displays configuration metadata, scan analytics KPIs, live preview,
 * multi-format download actions, and safe record deletion controls.
 *
 * @var \Yiisoft\View\WebView $this
 * @var QrCode $model
 * @var array{totalScans: int, todayScans: int, lastScanAt: int|string|null} $analytics
 * @var \App\Domain\User\User $currentUser
 * @var \App\Security\TenantContextInterface $context
 * @var \Yiisoft\Session\Flash\FlashInterface|null $flash
 * @var string|null $csrfToken
 * @var string|null $csrf
 */

$qrName = $model->getName() ?? ('QR Code #' . $model->getId());
$this->setTitle($qrName);

$status = $model->getStatus();
$isActive = ($status === QrCode::STATUS_ACTIVE);
$badgeClass = $isActive ? 'rs-badge--success' : 'rs-badge--default';
$statusText = $isActive ? '🟢 Active' : 'Not Active';

$createdDateFormatted = $model->getCreatedAt() > 0 ? date('d M Y, H:i', $model->getCreatedAt()) : '—';

// Safe timestamp formatter for scan analytics
$formatLastScan = static function ($val): string {
    if ($val === null || $val === '' || $val === 0 || $val === '0') {
        return 'Never';
    }
    if (is_numeric($val)) {
        $ts = (int) $val;
    } else {
        $ts = strtotime((string) $val);
    }
    if (!$ts || $ts <= 0 || date('Y', $ts) === '1970') {
        return 'Never';
    }
    return date('d M Y, H:i', $ts);
};

$qrImagePath = $model->getQrcode();
$qrImageWebUrl = null;
if (!empty($qrImagePath)) {
    if (str_starts_with($qrImagePath, 'http://') || str_starts_with($qrImagePath, 'https://')) {
        $qrImageWebUrl = $qrImagePath;
    } else {
        $qrImageWebUrl = \App\Environment::url('/' . ltrim($qrImagePath, '/'));
    }
}

$effectiveCsrf = $csrfToken ?? $csrf ?? '';
?>

<div class="rs-page" style="padding: 0 0 24px 0;">
    <!-- 1. Header Section -->
    <div class="rs-page-header rs-d-flex rs-align-items-center rs-justify-content-between rs-mb-6 rs-flex-wrap rs-gap-4" style="margin-bottom: 24px;">
        <div class="rs-d-flex rs-align-items-center rs-gap-3 rs-flex-wrap">
            <a href="<?= \App\Environment::url('/qrcode/index') ?>" class="rs-btn rs-btn--sm rs-btn--ghost rs-text-muted" style="text-decoration: none;">
                <i class="fa-solid fa-arrow-left rs-mr-1"></i> Back to QR Codes
            </a>
            <h1 class="rs-page-title rs-m-0" style="font-size: 24px; font-weight: 700; color: var(--rs-primary, #234137); margin: 0;">
                <?= Html::encode($qrName) ?>
            </h1>
            <span class="rs-badge <?= $badgeClass ?>"><?= Html::encode($statusText) ?></span>
        </div>
        <div class="rs-page-header__actions rs-d-flex rs-gap-3">
            <a href="<?= \App\Environment::url('/qrcode/update?id=' . $model->getId()) ?>" class="rs-btn rs-btn--outline">
                <i class="fa-solid fa-pen-to-square rs-mr-1"></i> Edit Settings
            </a>
        </div>
    </div>

    <?php if (isset($flash) && $flash !== null): ?>
        <?php include __DIR__ . '/../../Shared/Components/_alerts.php'; ?>
    <?php endif; ?>

    <!-- 2. Responsive Grid Layout -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;" class="rs-qr-view-grid">
        <!-- Left Column: Metadata & Analytics -->
        <div>
            <!-- Metadata Configuration Card -->
            <div class="rs-card" style="margin-bottom: 24px;">
                <div class="rs-card__header" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                    <h3 class="rs-card__title" style="margin: 0; font-size: 16px; font-weight: 600; color: var(--rs-primary, #234137);">
                        Metadata Configuration
                    </h3>
                </div>
                <div class="rs-card__body rs-p-0">
                    <table class="rs-table" style="width: 100%; border-collapse: collapse;">
                        <tbody>
                            <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                                <td style="padding: 12px 20px; width: 200px; font-weight: 600; color: var(--rs-text-secondary, #7B8A8B); font-size: 13px;">ID</td>
                                <td style="padding: 12px 20px; color: var(--rs-text-main); font-size: 14px;">#<?= $model->getId() ?></td>
                            </tr>
                            <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                                <td style="padding: 12px 20px; font-weight: 600; color: var(--rs-text-secondary, #7B8A8B); font-size: 13px;">QR Name</td>
                                <td style="padding: 12px 20px; color: var(--rs-text-main); font-size: 14px; font-weight: 600;"><?= Html::encode($model->getName() ?? '—') ?></td>
                            </tr>
                            <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                                <td style="padding: 12px 20px; font-weight: 600; color: var(--rs-text-secondary, #7B8A8B); font-size: 13px;">Status</td>
                                <td style="padding: 12px 20px; font-size: 14px;">
                                    <span class="rs-badge <?= $badgeClass ?>"><?= Html::encode(ucfirst($model->getStatus())) ?></span>
                                </td>
                            </tr>
                            <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                                <td style="padding: 12px 20px; font-weight: 600; color: var(--rs-text-secondary, #7B8A8B); font-size: 13px;">Created</td>
                                <td style="padding: 12px 20px; color: var(--rs-text-main); font-size: 14px;"><?= Html::encode($createdDateFormatted) ?></td>
                            </tr>
                            <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                                <td style="padding: 12px 20px; font-weight: 600; color: var(--rs-text-secondary, #7B8A8B); font-size: 13px;">Redirect URL</td>
                                <td style="padding: 12px 20px; font-size: 14px;">
                                    <?php if (!empty($model->getUrl())): ?>
                                        <a href="<?= Html::encode($model->getUrl()) ?>" target="_blank" rel="noopener" style="color: var(--rs-primary, #234137); text-decoration: underline; word-break: break-all;">
                                            <?= Html::encode($model->getUrl()) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="rs-text-muted">— Not Configured</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                                <td style="padding: 12px 20px; font-weight: 600; color: var(--rs-text-secondary, #7B8A8B); font-size: 13px;">QR Code Logo Type</td>
                                <td style="padding: 12px 20px; color: var(--rs-text-main); font-size: 14px;">
                                    <?= Html::encode($model->getDefaultLogoName() ?: ($model->isUploadedLogo() ? 'Custom Uploaded' : '— Not Configured')) ?>
                                </td>
                            </tr>
                            <tr style="border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                                <td style="padding: 12px 20px; font-weight: 600; color: var(--rs-text-secondary, #7B8A8B); font-size: 13px;">QR Code Template</td>
                                <td style="padding: 12px 20px; color: var(--rs-text-main); font-size: 14px;">
                                    <?= Html::encode($model->getTemplateName() ?: 'default') ?>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 12px 20px; font-weight: 600; color: var(--rs-text-secondary, #7B8A8B); font-size: 13px;">QR Code Frame</td>
                                <td style="padding: 12px 20px; color: var(--rs-text-main); font-size: 14px;">
                                    <?= Html::encode($model->getFrameName() ?: '— None') ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Scan Analytics Card -->
            <div class="rs-card">
                <div class="rs-card__header" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #E2E8F0);">
                    <h3 class="rs-card__title" style="margin: 0; font-size: 16px; font-weight: 600; color: var(--rs-primary, #234137);">
                        Scan Analytics
                    </h3>
                </div>
                <div class="rs-card__body rs-p-6 rs-text-center rs-d-flex rs-flex-column rs-align-items-center rs-justify-content-center" style="min-height: 220px; background-color: var(--rs-bg-surface, #F8FAFC); padding: 32px 20px;">
                    <?php if (isset($analytics) && $analytics['totalScans'] > 0): ?>
                        <div style="font-size: 3rem; margin-bottom: 8px;">📊</div>
                        <h4 class="rs-font-bold rs-text-main" style="margin: 0 0 8px 0; font-size: 20px;">
                            Total Scans: <?= number_format($analytics['totalScans']) ?>
                        </h4>
                        <p class="rs-text-muted" style="margin: 0 0 6px 0; font-size: 14px;">
                            <strong>Today:</strong> <?= number_format($analytics['todayScans']) ?> scans
                        </p>
                        <p class="rs-text-muted" style="margin: 0; font-size: 13px;">
                            <strong>Last Scan:</strong> <?= $formatLastScan($analytics['lastScanAt'] ?? null) ?>
                        </p>
                    <?php else: ?>
                        <div style="font-size: 3rem; margin-bottom: 12px; opacity: 0.6;">📊</div>
                        <h4 class="rs-font-bold rs-text-main" style="margin: 0 0 6px 0; font-size: 18px;">No scan analytics are available yet.</h4>
                        <p class="rs-text-muted" style="margin: 0; font-size: 14px;">When users scan this QR Code, analytics will appear here.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: QR Preview & Actions -->
        <div>
            <!-- QR Preview Card -->
            <div class="rs-card" style="margin-bottom: 24px;">
                <div class="rs-card__header" style="padding: 16px 20px; border-bottom: 1px solid var(--rs-border, #E2E8F0); text-align: center;">
                    <h3 class="rs-card__title" style="margin: 0; font-size: 16px; font-weight: 600; color: var(--rs-primary, #234137);">
                        QR Preview
                    </h3>
                </div>
                <div class="rs-card__body rs-p-6 rs-text-center" style="padding: 24px;">
                    <?php if (!empty($qrImageWebUrl)): ?>
                        <div class="rs-mb-4 rs-d-flex rs-justify-content-center" style="margin-bottom: 20px;">
                            <img src="<?= Html::encode($qrImageWebUrl) ?>" alt="QR Code Preview" style="max-width: 100%; width: 220px; height: auto; border-radius: var(--rs-radius-md, 8px); box-shadow: var(--rs-shadow-sm, 0 1px 3px rgba(0,0,0,0.1)); border: 1px solid var(--rs-border, #E2E8F0);">
                        </div>
                    <?php else: ?>
                        <div class="rs-mb-4 rs-d-flex rs-align-items-center rs-justify-content-center" style="width: 220px; height: 220px; margin: 0 auto 20px auto; background-color: var(--rs-slate-50, #F8FAFC); border: 1px dashed var(--rs-slate-300, #CBD5E1); border-radius: var(--rs-radius-md, 8px);">
                            <span class="rs-text-muted rs-text-sm">— No Preview Available</span>
                        </div>
                    <?php endif; ?>

                    <div class="rs-d-flex rs-gap-2 rs-justify-content-center">
                        <a href="<?= \App\Environment::url('/qrcode/download?id=' . $model->getId() . '&format=png') ?>" class="rs-btn rs-btn--secondary rs-btn--sm" title="Download High-Resolution PNG">
                            <i class="fa-solid fa-download rs-mr-1"></i> PNG
                        </a>
                        <a href="<?= \App\Environment::url('/qrcode/download?id=' . $model->getId() . '&format=jpg') ?>" class="rs-btn rs-btn--secondary rs-btn--sm" title="Download High-Resolution JPG">
                            <i class="fa-solid fa-download rs-mr-1"></i> JPG
                        </a>
                        <a href="<?= \App\Environment::url('/qrcode/download?id=' . $model->getId() . '&format=pdf') ?>" class="rs-btn rs-btn--secondary rs-btn--sm" title="Download Print-Ready PDF">
                            <i class="fa-solid fa-file-pdf rs-mr-1"></i> PDF
                        </a>
                    </div>
                </div>
            </div>

            <!-- Danger Zone Card -->
            <div class="rs-card rs-card--danger" style="border: 1px solid #FCA5A5;">
                <div class="rs-card__header" style="padding: 14px 20px; border-bottom: 1px solid #FEE2E2; background-color: #FEF2F2;">
                    <h3 class="rs-card__title rs-text-danger" style="margin: 0; font-size: 15px; font-weight: 600; color: #DC2626;">
                        Danger Zone
                    </h3>
                </div>
                <div class="rs-card__body" style="padding: 16px 20px;">
                    <p class="rs-text-sm rs-text-muted" style="margin: 0 0 16px 0; font-size: 13px;">
                        Deleting this QR Code permanently removes its scan history and redirection configurations.
                    </p>
                    <form method="POST" action="<?= \App\Environment::url('/qrcode/delete?id=' . $model->getId()) ?>" onsubmit="return confirm('Are you absolutely sure you want to delete this QR code?');" style="margin: 0;">
                        <?php if (!empty($effectiveCsrf)): ?>
                            <input type="hidden" name="_csrf" value="<?= Html::encode($effectiveCsrf) ?>">
                        <?php endif; ?>
                        <input type="hidden" name="id" value="<?= $model->getId() ?>">
                        <button type="submit" class="rs-btn rs-btn--danger rs-btn--full-width" style="width: 100%;">
                            <i class="fa-solid fa-trash rs-mr-1"></i> Delete QR Code
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
