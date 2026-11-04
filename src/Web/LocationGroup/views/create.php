<?php

declare(strict_types=1);

use App\Domain\LocationGroup\ConnectedDestination;
use Yiisoft\Html\Html;

/**
 * Location Group Create View (Yii 3 Modern Theme)
 *
 * @var \Yiisoft\View\WebView $this
 * @var array{name: string, description: string, status: int, account_id: int} $formData
 * @var array<int, string> $accountOptions
 * @var array{google: ConnectedDestination[], facebook: ConnectedDestination[], instagram: ConnectedDestination[]} $destinationsByPlatform
 * @var string[] $selectedKeys
 * @var array<string, string> $errors
 * @var \App\Domain\User\User $currentUser
 * @var \App\Security\TenantContextInterface $context
 * @var \Yiisoft\Session\Flash\FlashInterface|null $flash
 * @var string|null $csrfToken
 * @var string|null $csrf
 */

$this->setTitle('Create Location Group');

// Page Header
$title = 'Create Location Group';
$description = 'Set up a new operational destination group for your account.';
$actions = [
    '<a href="' . \App\Environment::url('/location-group/index') . '" class="rs-btn rs-btn--outline"><i class="fa-solid fa-arrow-left rs-mr-1"></i> Back to Groups</a>'
];
include __DIR__ . '/../../Shared/Components/_page_header.php';
?>

<?php if ($flash !== null && !empty($flash->getAll())): ?>
    <?php include __DIR__ . '/../../Shared/Components/_alerts.php'; ?>
<?php endif; ?>

<?= $this->render('_form', [
    'formData' => $formData,
    'accountOptions' => $accountOptions,
    'destinationsByPlatform' => $destinationsByPlatform,
    'selectedKeys' => $selectedKeys,
    'errors' => $errors,
    'isEdit' => false,
    'csrfToken' => $csrfToken,
    'csrf' => $csrf ?? null,
]) ?>
