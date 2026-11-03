<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Workspace Create View (Yii 3 Modern Theme)
 * Rendered within Main Authenticated Layout.
 *
 * @var \App\Domain\User\User $currentUser
 * @var array $data
 * @var array $errors
 * @var string|null $infoMessage
 * @var string|null $csrfToken
 * @var Yiisoft\View\WebView $this
 */

$this->setTitle('Create Workspace');

$actions = [
    '<a href="/sys-yii3/accounts" class="rs-btn rs-btn--ghost">Back to Workspaces</a>',
];
$title = 'Create Workspace';
$description = 'Workspaces / Create';
include __DIR__ . '/../../Shared/Components/_page_header.php';
?>

<?php if (!empty($infoMessage)): ?>
    <div class="rs-alert rs-alert--info rs-mb-4" role="alert">
        <svg class="rs-alert__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
        </svg>
        <div class="rs-alert__content">
            <?= Html::encode($infoMessage) ?>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/_form.php'; ?>
