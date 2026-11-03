<?php

declare(strict_types=1);

use App\Domain\LocationGroup\ConnectedDestination;
use App\Domain\LocationGroup\LocationGroup;
use Yiisoft\Html\Html;

/**
 * Location Group Update View (Yii 3 Modern Theme)
 *
 * @var \Yiisoft\View\WebView $this
 * @var LocationGroup $model
 * @var array{id: int, name: string, description: string, status: int, account_id: int} $formData
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

$this->setTitle('Edit ' . $model->getName() . ' — Location Group');

// Page Header
$title = 'Edit Location Group: ' . $model->getName();
$description = 'Update group details and adjust member social destinations.';
$actions = [
    '<a href="/sys-yii3/location-group/view?id=' . $model->getId() . '" class="rs-btn rs-btn--outline"><i class="fa-solid fa-eye rs-mr-1"></i> View Group</a>',
    '<a href="/sys-yii3/location-group/index" class="rs-btn rs-btn--outline"><i class="fa-solid fa-arrow-left rs-mr-1"></i> Back to Groups</a>'
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
    'isEdit' => true,
    'csrfToken' => $csrfToken,
    'csrf' => $csrf ?? null,
]) ?>
