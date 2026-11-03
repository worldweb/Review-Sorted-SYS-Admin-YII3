<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Reusable Empty State Component (Yii 3 Modern Theme)
 *
 * @var string|null $icon Full SVG string for the icon
 * @var string $title The main title text
 * @var string|null $description Supporting text below the title
 * @var string|null $actions Optional HTML string for buttons/actions
 */
?>
<div class="rs-empty-state">
    <?php if (!empty($icon)): ?>
        <?= $icon ?>
    <?php endif; ?>
    <h3 class="rs-empty-state__title"><?= Html::encode($title) ?></h3>
    <?php if (!empty($description)): ?>
        <p class="rs-empty-state__description"><?= Html::encode($description) ?></p>
    <?php endif; ?>
    <?php if (!empty($actions)): ?>
        <div class="rs-empty-state__actions">
            <?= $actions ?>
        </div>
    <?php endif; ?>
</div>
