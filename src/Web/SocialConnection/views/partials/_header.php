<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var string $title
 * @var string|null $subtitle
 */
?>
<div class="rs-page-header rs-d-flex rs-align-items-center rs-justify-content-between rs-mb-6">
    <div>
        <h1 class="rs-page-title" style="font-size: 1.75rem; font-weight: 700; color: var(--rs-text-main, #0f172a); margin: 0;"><?= Html::encode($title) ?></h1>
        <?php if (!empty($subtitle)): ?>
            <div class="rs-text-muted rs-mt-1" style="font-size: 0.875rem; color: #64748b;"><?= Html::encode($subtitle) ?></div>
        <?php endif; ?>
    </div>
</div>
<hr class="rs-mb-6" style="border: 0; border-top: 1px solid var(--rs-border-color, #e2e8f0); margin-top: 1rem; margin-bottom: 1.5rem;" />
