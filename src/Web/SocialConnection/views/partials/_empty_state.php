<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var string $message
 * @var string|null $actionUrl
 * @var string|null $actionLabel
 */
?>
<div class="rs-card rs-card--empty rs-text-center rs-p-6 rs-mb-6" style="padding: 3rem 1.5rem; text-align: center; background: #ffffff; border-radius: 0.5rem; border: 1px solid var(--rs-border-color, #e2e8f0);">
    <div class="rs-text-muted">
        <div style="font-size: 3rem; margin-bottom: 1rem;">ℹ️</div>
        <p class="rs-m-0 rs-mb-4 rs-font-size-lg" style="font-size: 1.125rem; color: #475569; margin-bottom: 1.5rem;"><?= Html::encode($message) ?></p>
        <?php if (!empty($actionUrl) && !empty($actionLabel)): ?>
            <a href="<?= Html::encode($actionUrl) ?>" class="rs-btn rs-btn--primary rs-px-6 rs-py-2"><?= Html::encode($actionLabel) ?></a>
        <?php endif; ?>
    </div>
</div>
