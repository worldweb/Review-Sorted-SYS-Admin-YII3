<?php

declare(strict_types=1);

use App\Domain\Review\Review;
use Yiisoft\Html\Html;

/**
 * @var \Yiisoft\View\WebView $this
 * @var Review[] $reviews
 * @var array<int, array{external_success: bool, local_saved: bool, status: string, obj_id: ?string, error: ?string, platform: string}> $results
 * @var int $total
 * @var int $platformAcceptedCount
 * @var int $localSavedCount
 * @var int $platformFailedCount
 * @var string $q
 * @var \Yiisoft\Session\Flash\FlashInterface|null $flash
 */

$this->setTitle('Bulk Reply Results');

$backUrl = '/sys-yii3/reviews';
if (!empty($q)) {
    $backUrl .= '?' . $q;
}

$unsavedCount = $platformAcceptedCount - $localSavedCount;
?>

<div class="rs-page rs-p-6">
    <?php if (isset($flash)): ?>
        <?php include dirname(__DIR__, 2) . '/Shared/Components/_alerts.php'; ?>
    <?php endif; ?>

    <div class="rs-d-flex rs-justify-content-between rs-align-items-center rs-mb-6">
        <div>
            <h1 class="rs-page-title rs-m-0">Bulk Reply Results</h1>
            <p class="rs-text-muted rs-m-0 rs-mt-1">Review the live platform dispatch and local persistence outcomes below.</p>
        </div>
        <div>
            <a href="<?= Html::encode($backUrl) ?>" class="rs-btn rs-btn--primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Back to Reviews
            </a>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="rs-d-flex rs-gap-4 rs-flex-wrap rs-mb-6">
        <div class="rs-card rs-p-5" style="flex: 1 1 200px; border-top: 3px solid var(--rs-primary, #2563eb);">
            <div class="rs-text-xs rs-font-bold rs-text-uppercase rs-text-muted rs-mb-1">Total Processed</div>
            <div class="rs-font-bold rs-text-main" style="font-size: 28px; line-height: 1.2;"><?= $total ?></div>
        </div>

        <div class="rs-card rs-p-5" style="flex: 1 1 200px; border-top: 3px solid #10b981;">
            <div class="rs-text-xs rs-font-bold rs-text-uppercase rs-text-muted rs-mb-1" style="color: #10b981;">Platform Accepted</div>
            <div class="rs-font-bold rs-text-main" style="font-size: 28px; line-height: 1.2; color: #10b981;"><?= $platformAcceptedCount ?></div>
            <div class="rs-text-xs rs-text-muted rs-mt-1">
                <?= $localSavedCount ?> saved to DB<?= $unsavedCount > 0 ? ", <span class='rs-text-warning'>{$unsavedCount} unsaved locally</span>" : '' ?>
            </div>
        </div>

        <div class="rs-card rs-p-5" style="flex: 1 1 200px; border-top: 3px solid #ef4444;">
            <div class="rs-text-xs rs-font-bold rs-text-uppercase rs-text-muted rs-mb-1" style="color: #ef4444;">Platform Errors</div>
            <div class="rs-font-bold rs-text-main" style="font-size: 28px; line-height: 1.2; color: <?= $platformFailedCount > 0 ? '#ef4444' : '#64748b' ?>;"><?= $platformFailedCount ?></div>
            <div class="rs-text-xs rs-text-muted rs-mt-1">Rejected by API</div>
        </div>
    </div>

    <?php if ($unsavedCount > 0): ?>
        <div class="rs-alert rs-alert--warning rs-mb-5" role="alert" style="border-left: 4px solid var(--rs-warning, #f59e0b);">
            <div class="rs-alert__content">
                <strong>Attention:</strong> <?= $unsavedCount ?> <?= $unsavedCount === 1 ? 'reply was' : 'replies were' ?> successfully accepted by the external platform, but could not be recorded in the local database because the database connection is currently read-only. <strong>Please do NOT resend these reviews</strong>, as the replies are already live on the platform.
            </div>
        </div>
    <?php endif; ?>

    <!-- Outcomes Data Table Card -->
    <div class="rs-card rs-mb-6">
        <div class="rs-card__header rs-py-3">
            <h3 class="rs-card__title" style="font-size: 15px;">Live Dispatch & Persistence Details</h3>
        </div>
        <div class="rs-table-wrapper">
            <table class="rs-table rs-w-100" style="margin: 0;">
                <thead>
                    <tr>
                        <th style="width: 16%;">Alias</th>
                        <th style="width: 10%;">Channel</th>
                        <th style="width: 14%;">Customer</th>
                        <th style="width: 24%;">Review Text</th>
                        <th style="width: 18%;">Platform Dispatch</th>
                        <th class="rs-text-center" style="width: 18%;">Local Database</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews as $item): ?>
                        <?php
                        $rowResult = $results[$item->getId()] ?? [
                            'external_success' => false,
                            'local_saved' => false,
                            'status' => 'UNKNOWN',
                            'obj_id' => null,
                            'error' => 'No outcome recorded',
                            'platform' => $item->getServiceLabel() ?? 'Channel',
                        ];

                        $isExternalSuccess = $rowResult['external_success'];
                        $isLocalSaved = $rowResult['local_saved'];
                        $objId = $rowResult['obj_id'];
                        $error = $rowResult['error'];
                        $cfgLabel = $item->getServiceConfigLabel() ?? 'Page';
                        $servLabel = $item->getServiceLabel() ?? 'Channel';
                        ?>
                        <tr>
                            <td>
                                <span class="rs-font-medium" style="font-size: 13px;"><?= Html::encode($cfgLabel) ?></span>
                            </td>
                            <td>
                                <span class="rs-text-sm"><?= Html::encode($servLabel) ?></span>
                            </td>
                            <td>
                                <span class="rs-font-semibold rs-text-sm"><?= Html::encode($item->getCustomerName()) ?></span>
                            </td>
                            <td>
                                <div class="rs-text-sm" style="max-height: 3.5em; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                    <?= Html::encode($item->getReviewText()) ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($isExternalSuccess): ?>
                                    <span class="rs-badge rs-badge--success" style="font-size: 12px; padding: 4px 8px;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 3px;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        Accepted
                                    </span>
                                    <?php if ($objId !== null): ?>
                                        <div class="rs-text-xs rs-text-muted rs-mt-1" style="font-family: monospace; word-break: break-all;">
                                            ID: <?= Html::encode($objId) ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="rs-badge rs-badge--danger" style="font-size: 12px; padding: 4px 8px;" title="<?= Html::encode($error ?? 'Failed') ?>">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 3px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                        Failed
                                    </span>
                                    <?php if ($error !== null): ?>
                                        <div class="rs-text-xs rs-text-danger rs-mt-1" style="word-break: break-word;">
                                            <?= Html::encode($error) ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="rs-text-center">
                                <?php if ($isLocalSaved): ?>
                                    <span class="rs-badge rs-badge--success" style="font-size: 12px; padding: 4px 8px;">
                                        Saved
                                    </span>
                                <?php elseif ($isExternalSuccess): ?>
                                    <span class="rs-badge rs-badge--warning" style="font-size: 12px; padding: 4px 8px;" title="Local save prevented by read-only database mode">
                                        Unsaved (Read-Only DB)
                                    </span>
                                <?php else: ?>
                                    <span class="rs-badge rs-badge--soft" style="font-size: 12px; padding: 4px 8px; color: #94a3b8;">
                                        Not Attempted
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="rs-card__footer rs-p-4 rs-d-flex rs-justify-content-between rs-align-items-center">
            <span class="rs-text-sm rs-text-muted">Completed sequential execution.</span>
            <a href="<?= Html::encode($backUrl) ?>" class="rs-btn rs-btn--outline">Back to Reviews</a>
        </div>
    </div>
</div>
