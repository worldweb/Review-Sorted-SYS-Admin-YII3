<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Reusable Status Badge Component (Reviews Sorted Modern Theme)
 *
 * @var string $status
 * @var string|null $label
 */

$class = 'rs-badge--default';
if (in_array(strtolower($status), ['success', 'linked', 'connected', 'active', 'published', 'paid'], true)) {
    $class = 'rs-badge--success';
} elseif (in_array(strtolower($status), ['warning', 'scheduled', 'queued', 'pending'], true)) {
    $class = 'rs-badge--warning';
} elseif (in_array(strtolower($status), ['danger', 'failed', 'not linked', 'unlinked', 'inactive', 'cancelled'], true)) {
    $class = 'rs-badge--danger';
}
?>
<span class="rs-badge <?= $class ?>"><?= Html::encode($label ?? $status) ?></span>
