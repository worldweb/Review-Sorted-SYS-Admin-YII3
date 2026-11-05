<?php

declare(strict_types=1);

use App\Shared\AssetVersion;
use Yiisoft\Html\Html;

/**
 * Guest Layout (Yii 3 Modern Theme)
 * Used for public authentication, client registration, and password recovery pages.
 *
 * @var \App\Shared\ApplicationParams $applicationParams
 * @var Yiisoft\Aliases\Aliases $aliases
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var string $content
 * @var string|null $csrf
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */

$baseUrl = isset($aliases) ? (string) $aliases->get('@baseUrl') : '/';
$base = ($baseUrl !== '' && $baseUrl !== '/') ? rtrim($baseUrl, '/') : '';
$themeUrl = $base . '/themes/modern';
$asset = static fn (string $path): string => AssetVersion::append($themeUrl . $path);

$pageTitle = $this->getTitle();
if (empty($pageTitle)) {
    $pageTitle = $applicationParams->name;
} else {
    $pageTitle = $pageTitle . ' — ' . $applicationParams->name;
}

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Html::encode($applicationParams->locale) ?>">
<head>
    <meta charset="<?= Html::encode($applicationParams->charset) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="<?= $base ?>/favicon.ico" type="image/x-icon">
    <?php if (!empty($csrf)): ?>
        <meta name="csrf-token" content="<?= Html::encode($csrf) ?>">
    <?php endif; ?>
    <title><?= Html::encode($pageTitle) ?></title>

    <!-- Enterprise Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;600;700;900&display=swap" rel="stylesheet">

    <!-- Modern Theme CSS Stylesheets -->
    <link rel="stylesheet" href="<?= $asset('/css/variables.css') ?>">
    <link rel="stylesheet" href="<?= $asset('/css/base.css') ?>">
    <link rel="stylesheet" href="<?= $asset('/css/typography.css') ?>">
    <link rel="stylesheet" href="<?= $asset('/css/layout.css') ?>">
    <link rel="stylesheet" href="<?= $asset('/css/buttons.css') ?>">
    <link rel="stylesheet" href="<?= $asset('/css/forms.css') ?>">
    <link rel="stylesheet" href="<?= $asset('/css/cards.css') ?>">
    <link rel="stylesheet" href="<?= $asset('/css/alerts.css') ?>">
    <link rel="stylesheet" href="<?= $asset('/css/utilities.css') ?>">
    <link rel="stylesheet" href="<?= $asset('/css/responsive.css') ?>">

    <style>
        .rs-auth-layout {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--rs-bg-surface, #FEFBF6);
            padding: var(--rs-space-6) var(--rs-space-4);
        }
        .rs-auth-card {
            width: 100%;
            max-width: 480px;
            background: var(--rs-white, #FFFFFF);
            border-radius: var(--rs-radius-lg, 12px);
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--rs-border, #E2E8F0);
            padding: var(--rs-space-8) var(--rs-space-6);
        }
        .rs-auth-header {
            text-align: center;
            margin-bottom: var(--rs-space-6);
        }
        .rs-auth-title {
            font-size: var(--rs-font-size-2xl, 24px);
            font-weight: 700;
            color: var(--rs-primary, #234137);
            margin: 0 0 var(--rs-space-2) 0;
        }
        .rs-auth-subtitle {
            font-size: var(--rs-font-size-sm, 14px);
            color: var(--rs-text-secondary, #7B8A8B);
            margin: 0;
        }
    </style>

    <?php $this->head() ?>
</head>
<body class="rs-app-wrapper rs-layout-guest">
<?php $this->beginBody() ?>

<div class="rs-auth-container rs-w-100">
    <?= $content ?>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage(); ?>
