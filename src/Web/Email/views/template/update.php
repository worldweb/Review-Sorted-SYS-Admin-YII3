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

$this->setTitle('Update Email Template: ' . $template->getName());
$actionUrl = \App\Environment::url('/email/template/update?id=') . $template->getId();
$accounts = [];
$targetAccountId = $template->getAccountId();
?>

<div class="rs-page">
    <div class="rs-page-header rs-d-flex rs-justify-content-between rs-align-items-center rs-mb-6" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
        <div>
            <h1 class="rs-page-title rs-m-0" style="font-size: 24px; font-weight: 700; margin: 0;">Edit Template</h1>
            <p class="rs-text-muted rs-m-0" style="color: #64748b; font-size: 14px; margin-top: 4px;">Update the content, name, and settings of this template.</p>
        </div>
        <div>
            <a href="<?= \App\Environment::url('/email/templates') ?>" class="rs-btn rs-btn--ghost" style="border: 1px solid #cbd5e1; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 500; color: #475569; display: inline-flex; align-items: center;">← Back</a>
        </div>
    </div>

    <?php include __DIR__ . '/_form.php'; ?>
</div>
