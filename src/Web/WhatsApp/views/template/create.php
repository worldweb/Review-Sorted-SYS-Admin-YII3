<?php

declare(strict_types=1);

/**
 * @var string $name
 * @var string $text
 * @var int $status
 * @var int|null $targetAccountId
 * @var array<int, \App\Domain\Account\Account> $accounts
 * @var array<string, array{label: string, scope: string, example: string}> $supportedTags
 * @var array<string, string> $errors
 * @var \App\Domain\User\User $currentUser
 * @var \App\Security\TenantContextInterface $context
 * @var \Yiisoft\Session\Flash\Flash $flash
 * @var string|null $csrfToken
 */

$this->setTitle('Create WhatsApp Template');
$template = null;
$actionUrl = \App\Environment::url('/whatsapp/template/create');
?>

<div class="rs-page">
    <div class="rs-page-header rs-mb-6">
        <h1 class="rs-page-title rs-m-0">Create WhatsApp Template</h1>
        <p class="rs-text-muted rs-m-0">Build a reusable plain-text template with merge tags for WhatsApp messages.</p>
    </div>

    <?php include __DIR__ . '/_form.php'; ?>
</div>
