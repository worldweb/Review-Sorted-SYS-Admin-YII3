<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Reusable Page Header Component (Yii 3 Modern Theme)
 *
 * @var string $title Page title
 * @var string|null $description Optional description below title
 * @var string|array|null $actions Optional HTML string or array of HTML strings for header actions
 */
?>
<div class="rs-page-header rs-d-flex rs-align-items-start rs-justify-content-between rs-mb-6">
    <div class="rs-page-header__title-wrapper">
        <h1 class="rs-page-header__title"><?= Html::encode($title) ?></h1>
        <?php if (!empty($description)): ?>
            <p class="rs-text-muted rs-font-size-sm rs-mt-1 rs-mb-0"><?= Html::encode($description) ?></p>
        <?php endif; ?>
    </div>
    <?php if (!empty($actions)): ?>
        <div class="rs-page-header__actions rs-d-flex rs-gap-3">
            <?= is_array($actions) ? implode("\n", $actions) : $actions ?>
        </div>
    <?php endif; ?>
</div>
