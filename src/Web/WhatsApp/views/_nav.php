<?php

declare(strict_types=1);

/**
 * Reusable WhatsApp Sub-Navigation Bar.
 *
 * @var string $activeTab Current active tab ('templates', 'send', 'importer', 'history')
 */

$activeTab = $activeTab ?? 'importer';

$tabs = [
    'templates' => ['label' => 'WhatsApp Templates', 'url' => '/sys-yii3/whatsapp/index', 'icon' => 'fa-file-lines'],
    'send' => ['label' => 'Send WhatsApp', 'url' => '/sys-yii3/whatsapp/send', 'icon' => 'fa-paper-plane'],
    'importer' => ['label' => 'Import Phones / Contacts', 'url' => '/sys-yii3/whatsapp/importer', 'icon' => 'fa-file-csv'],
    'history' => ['label' => 'Message History', 'url' => '/sys-yii3/whatsapp/history', 'icon' => 'fa-clock-rotate-left'],
];
?>

<div class="rs-d-flex rs-align-items-center rs-gap-2 rs-mb-6 rs-border-bottom" style="border-bottom: 1px solid var(--rs-border, #E2E8F0); padding-bottom: 8px;">
    <?php foreach ($tabs as $key => $tab): ?>
        <?php $isActive = $key === $activeTab; ?>
        <a
            href="<?= $tab['url'] ?>"
            class="rs-btn <?= $isActive ? 'rs-btn--primary' : 'rs-btn--ghost rs-text-muted' ?>"
            style="font-size: 13px; font-weight: 500; text-decoration: none;"
        >
            <i class="fa-solid <?= $tab['icon'] ?> rs-mr-1"></i> <?= $tab['label'] ?>
        </a>
    <?php endforeach; ?>
</div>
