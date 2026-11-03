<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Reusable Breadcrumbs Component (Yii 3 Modern Theme)
 *
 * @var array<int, array{label: string, url?: string}|string> $links
 */
if (empty($links)) {
    return;
}
?>
<ul class="rs-breadcrumbs" aria-label="Breadcrumb">
    <?php
    $total = count($links);
    $i = 0;
    foreach ($links as $link):
        $i++;
        $isLast = ($i === $total);
        if (is_string($link)): ?>
            <li class="rs-breadcrumb__item <?= $isLast ? 'rs-breadcrumb__item--active' : '' ?>">
                <span><?= Html::encode($link) ?></span>
            </li>
        <?php elseif (is_array($link)):
            $label = $link['label'] ?? '';
            $url = $link['url'] ?? null;
            if ($url && !$isLast): ?>
                <li class="rs-breadcrumb__item">
                    <a href="<?= Html::encode($url) ?>" class="rs-breadcrumb__link"><?= Html::encode($label) ?></a>
                </li>
            <?php else: ?>
                <li class="rs-breadcrumb__item rs-breadcrumb__item--active" aria-current="page">
                    <span><?= Html::encode($label) ?></span>
                </li>
            <?php endif;
        endif;
    endforeach;
    ?>
</ul>
