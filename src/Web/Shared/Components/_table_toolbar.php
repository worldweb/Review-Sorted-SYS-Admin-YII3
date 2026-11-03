<?php

declare(strict_types=1);

/**
 * Reusable Table Toolbar Component (Yii 3 Modern Theme)
 *
 * @var string $searchInput HTML string for the search input wrapper
 * @var string|null $actions Optional HTML string for additional action buttons
 * @var string|null $filters Optional HTML string for filters
 */
?>
<div class="mod-table-toolbar">
    <div style="display: flex; gap: 16px; align-items: center; flex: 1; flex-wrap: wrap;">
        <?= $searchInput ?>
        <?php if (!empty($filters)): ?>
            <?= $filters ?>
        <?php endif; ?>
    </div>
    <?php if (!empty($actions)): ?>
        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <?= $actions ?>
        </div>
    <?php endif; ?>
</div>
