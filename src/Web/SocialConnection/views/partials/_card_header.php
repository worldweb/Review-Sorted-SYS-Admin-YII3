<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var string $title
 * @var string|null $subtitle
 * @var string|null $action
 */
?>
<div class="rs-card__header rs-p-4 rs-border-bottom rs-d-flex rs-align-items-center rs-justify-content-between rs-flex-wrap rs-gap-3" style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--rs-border-color, #e2e8f0); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap;">
    <div>
        <h3 class="rs-card__title rs-m-0" style="font-size: 1.125rem; font-weight: 600; color: var(--rs-text-main, #0f172a); margin: 0;"><?= Html::encode($title) ?></h3>
        <?php if (!empty($subtitle)): ?>
            <p class="rs-text-muted rs-text-sm rs-m-0" style="font-size: 0.875rem; color: #64748b; margin: 0;"><?= Html::encode($subtitle) ?></p>
        <?php endif; ?>
    </div>
    <?php if (!empty($action)): ?>
        <div><?= $action ?></div>
    <?php endif; ?>
</div>
