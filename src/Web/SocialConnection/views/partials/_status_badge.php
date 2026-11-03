<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var string $status
 * @var string|null $label
 */

$class = 'rs-badge--default';
if (in_array(strtolower($status), ['success', 'linked', 'connected'], true)) {
    $class = 'rs-badge--success';
} elseif (in_array(strtolower($status), ['warning', 'pending'], true)) {
    $class = 'rs-badge--warning';
} elseif (in_array(strtolower($status), ['danger', 'not linked', 'unlinked', 'error'], true)) {
    $class = 'rs-badge--danger';
}
?>
<span class="rs-badge <?= $class ?>"><?= Html::encode($label ?? $status) ?></span>
