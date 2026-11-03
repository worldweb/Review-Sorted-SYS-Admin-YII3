<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var array<int, array{label: string, value: string|int, helper?: string, icon?: string}> $cards
 */
?>
<?php if (!empty($cards)): ?>
<div class="facebook-dashboard rs-d-flex rs-gap-4 rs-flex-wrap rs-w-100 rs-mb-4" style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem;">
    <?php foreach ($cards as $card): ?>
    <div class="rs-widget rs-card--shadow rs-d-flex rs-flex-column rs-justify-content-between" style="padding: 1.5rem; flex: 1 1 280px; min-width: 240px; background-color: #ffffff; border-radius: 0.5rem; border: 1px solid var(--rs-border-color, #e2e8f0); box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);">
        <div>
            <div class="rs-d-flex" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                <div class="rs-widget__label" style="font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; color: var(--rs-primary, #1e3a8a);"><?= Html::encode($card['label']) ?></div>
                <?php if (!empty($card['icon'])): ?>
                <div class="rs-kpi-icon-container" style="width: 32px; height: 32px; background-color: #F6FCF8; border: 1px solid #E7F5EC; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <?= $card['icon'] ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="rs-widget__value" style="font-size: 1.85rem; font-weight: 700; color: var(--rs-text-main, #0f172a);"><?= Html::encode((string) $card['value']) ?></div>
        </div>
        <?php if (!empty($card['helper'])): ?>
            <div class="rs-text-muted rs-text-xs rs-mt-3" style="font-size: 0.75rem; color: #64748b; margin-top: 0.75rem;"><?= Html::encode($card['helper']) ?></div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
