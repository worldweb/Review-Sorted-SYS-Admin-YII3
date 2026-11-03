<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Reusable Alerts Component (Yii 3 Modern Theme)
 *
 * @var array<string, string|array<string>>|null $alerts Map of type => message or array of messages
 * @var \Yiisoft\Session\Flash\FlashInterface|\Yiisoft\Session\SessionInterface|null $flash Optional session/flash instance
 */

$renderedAlerts = [];

if (!empty($alerts)) {
    $renderedAlerts = $alerts;
} elseif (isset($flash) && is_object($flash)) {
    if (method_exists($flash, 'getAll')) {
        $renderedAlerts = $flash->getAll();
    }
}

if (empty($renderedAlerts)) {
    return;
}

$icons = [
    'success' => '<svg class="rs-alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
    'danger' => '<svg class="rs-alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>',
    'error' => '<svg class="rs-alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>',
    'warning' => '<svg class="rs-alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
    'info' => '<svg class="rs-alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
];
?>
<div class="rs-alerts-container">
    <?php foreach ($renderedAlerts as $type => $message):
        $normalizedType = ($type === 'error') ? 'danger' : $type;
        $icon = $icons[$normalizedType] ?? $icons['info'];
        $messages = is_array($message) ? $message : [$message];
        foreach ($messages as $msg):
            if (empty($msg)) continue;
    ?>
        <div class="rs-alert rs-alert--<?= Html::encode($normalizedType) ?>" role="alert">
            <?= $icon ?>
            <div class="rs-alert__content">
                <?= Html::encode($msg) ?>
            </div>
            <button type="button" class="rs-alert__close" aria-label="Close alert" onclick="this.closest('.rs-alert').remove();">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
    <?php endforeach;
    endforeach; ?>
</div>
