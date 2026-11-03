<?php

declare(strict_types=1);

use App\Domain\Email\EmailTemplate;
use Yiisoft\Html\Html;

/**
 * @var EmailTemplate $template
 * @var string $name
 * @var string $text
 * @var int $status
 * @var array<string, array{label: string, scope: string, example: string}> $supportedTags
 * @var array<string, string> $errors
 * @var \App\Domain\User\User $currentUser
 * @var \App\Security\TenantContextInterface $context
 * @var \Yiisoft\Session\Flash\Flash $flash
 * @var string|null $csrfToken
 */

$this->setTitle('Edit Template: ' . $template->getName());
$actionUrl = '/sys-yii3/email/template/update?id=' . $template->getId();
$accounts = [];
$targetAccountId = $template->getAccountId();
?>

<div class="rs-page">
    <div class="rs-page-header rs-mb-6">
        <h1 class="rs-page-title rs-m-0">Edit E-mail Template: <?= Html::encode($template->getName()) ?></h1>
        <p class="rs-text-muted rs-m-0">Modify template title, status, and message HTML body.</p>
    </div>

    <?php include __DIR__ . '/_form.php'; ?>
</div>
