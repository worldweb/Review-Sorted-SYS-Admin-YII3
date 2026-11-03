<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * User Update View (Yii 3 Modern Theme)
 * Rendered within Main Authenticated Layout.
 *
 * @var \App\Domain\User\User $user
 * @var \App\Domain\User\User $currentUser
 * @var array $data
 * @var array $errors
 * @var string|null $infoMessage
 * @var array $accountsList
 * @var string|null $csrfToken
 * @var Yiisoft\View\WebView $this
 */

$isOwnProfile = $currentUser->getId() === $user->getId();
$pageTitle = $isOwnProfile ? 'My Profile: ' . $user->getName() : 'Update User: ' . $user->getName();
$this->setTitle($pageTitle);

$actions = [
    '<a href="/sys-yii3/user/view?id=' . $user->getId() . '" class="rs-btn rs-btn--secondary">View User</a>',
    '<a href="/sys-yii3/users" class="rs-btn rs-btn--ghost">Cancel</a>',
];
$title = $pageTitle;
$description = 'Modify user configuration, credentials, and workspace permissions.';
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

<?php
if ($user->isAccountAdmin() && !empty($accountsList)) {
    include __DIR__ . '/_form_acc_admin.php';
} elseif ($user->isWorker()) {
    $limitReached = false;
    include __DIR__ . '/_form_worker.php';
} else {
    include __DIR__ . '/_form.php';
}
?>
