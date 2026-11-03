<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Reusable Card Header Component (Reviews Sorted Modern Theme)
 *
 * @var string $title
 * @var string|null $subtitle
 * @var string|null $action
 */
?>
<div class="rs-card__header rs-p-4 rs-border-bottom rs-d-flex rs-align-items-center rs-justify-content-between rs-flex-wrap rs-gap-3">
    <div>
        <h3 class="rs-card__title rs-m-0"><?= Html::encode($title) ?></h3>
        <?php if (!empty($subtitle)): ?>
            <p class="rs-text-muted rs-text-sm rs-m-0"><?= Html::encode($subtitle) ?></p>
        <?php endif; ?>
    </div>
    <?php if (!empty($action)): ?>
        <div><?= $action ?></div>
    <?php endif; ?>
</div>
