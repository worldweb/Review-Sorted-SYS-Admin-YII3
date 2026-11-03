<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Reusable Form Actions Footer Component (Yii 3 Modern Theme)
 *
 * @var string $submitLabel Label for the primary submit button
 * @var array<string, mixed> $submitOptions Additional HTML attributes for submit button
 * @var string $cancelUrl URL to redirect to on cancel
 * @var string|null $cancelLabel Label for the cancel button
 */

$submitLabel = $submitLabel ?? 'Save';
$submitOptions = $submitOptions ?? ['class' => 'rs-btn rs-btn--primary'];
$cancelLabel = $cancelLabel ?? 'Cancel';
$cancelUrl = $cancelUrl ?? 'javascript:history.back()';
?>
<div class="rs-card__footer rs-d-flex rs-align-items-center rs-justify-content-between rs-gap-3">
    <?= Html::submitButton($submitLabel, $submitOptions) ?>
    <?= Html::a($cancelLabel, $cancelUrl, ['class' => 'rs-btn rs-btn--ghost']) ?>
</div>
