<?php

declare(strict_types=1);

use App\Domain\QrCode\QrCode;
use Yiisoft\Html\Html;

/**
 * QR Code Studio 3-Step Wizard View (Yii 3 Modern Theme)
 * Facilitates interactive QR code creation and updates through:
 *  - Step 1: Destination URL validation and configuration.
 *  - Step 2: Visual customization (Templates slider, Logo gallery/uploader, Frame selector).
 *  - Step 3: Finalization, name assignment, status selection, and real-time live preview.
 *
 * @var \Yiisoft\View\WebView $this
 * @var QrCode|null $model
 * @var \App\Domain\User\User $currentUser
 * @var \App\Security\TenantContextInterface $context
 * @var \Yiisoft\Session\Flash\FlashInterface|null $flash
 * @var string|null $csrfToken
 * @var string|null $csrf
 */

$qrcodeId = $model !== null ? $model->getId() : null;
$isUpdate = ($qrcodeId !== null);

$pageTitle = $isUpdate
    ? ('Update QR Code: ' . ($model->getName() ?? '#' . $qrcodeId))
    : 'Create QR Code';
$this->setTitle($pageTitle);

$baseUrl = \App\Environment::appBasePath();
$ajaxUrl = $baseUrl . '/qrcode/ajax' . ($isUpdate ? '?id=' . $qrcodeId : '');
$submitUrl = $isUpdate ? ($baseUrl . '/qrcode/update?id=' . $qrcodeId) : ($baseUrl . '/qrcode/create');

// Initial selected template and preview image determination
$selectedTemplate = $model?->getTemplateName() ?? 'default';
$initialPreviewImage = $baseUrl . '/assets/qr/templates/' . $selectedTemplate . '.png';
if ($isUpdate && !empty($model->getQrcode())) {
    $qrPath = $model->getQrcode();
    if (str_starts_with($qrPath, 'http://') || str_starts_with($qrPath, 'https://')) {
        $initialPreviewImage = $qrPath;
    } else {
        $initialPreviewImage = $baseUrl . '/' . ltrim($qrPath, '/');
    }
}
?>

<!-- External Dependencies: FontAwesome, Select2, and Bootstrap Grid -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= \App\Shared\AssetVersion::append(\App\Environment::url('/assets/qr/css/qr-code-form.css')) ?>">
<link rel="stylesheet" href="<?= \App\Shared\AssetVersion::append(\App\Environment::url('/assets/qr/css/qr-code-overrides.css')) ?>">

<!-- RS Sidebar Protection & Studio Style Neutralization -->
<style>
.rs-sidebar__nav ul.nav,
.rs-sidebar__nav ul {
    display: flex !important;
    flex-direction: column !important;
    margin: 0 !important;
    padding: 0 !important;
    list-style: none !important;
}
.rs-sidebar__nav li {
    display: block !important;
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
}
.rs-sidebar__nav a {
    display: flex !important;
    align-items: center !important;
    padding: var(--rs-space-2, 8px) var(--rs-space-3, 12px) !important;
    margin-bottom: var(--rs-space-1, 4px) !important;
    border-radius: var(--rs-radius-md, 8px) !important;
    font-size: var(--rs-font-size-sm, 14px) !important;
    font-weight: 500 !important;
    text-decoration: none !important;
    color: var(--rs-text-main, #1E293B) !important;
    box-sizing: border-box !important;
    transition: background-color var(--rs-transition-fast, 150ms ease), color var(--rs-transition-fast, 150ms ease) !important;
}
.rs-sidebar__nav a:hover {
    background-color: var(--rs-bg-app, #F1F5F9) !important;
    color: var(--rs-primary, #234137) !important;
    text-decoration: none !important;
}
.rs-sidebar__nav li.active > a {
    background-color: rgba(35, 65, 55, 0.05) !important;
    color: var(--rs-primary, #234137) !important;
    position: relative !important;
}
.rs-sidebar__nav li.active > a::before {
    content: '' !important;
    position: absolute !important;
    left: calc(var(--rs-space-3, 12px) * -1) !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    height: 60% !important;
    width: 4px !important;
    background-color: var(--rs-primary, #234137) !important;
    border-radius: 0 4px 4px 0 !important;
}
.rs-sidebar__nav .rs-sidebar__icon {
    margin-right: var(--rs-space-3, 12px) !important;
    width: 20px !important;
    height: 20px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    opacity: 0.6 !important;
    flex-shrink: 0 !important;
}
.rs-sidebar__nav a:hover .rs-sidebar__icon {
    opacity: 0.8 !important;
}
.rs-sidebar__nav li.active > a .rs-sidebar__icon {
    opacity: 1 !important;
    color: var(--rs-primary, #234137) !important;
}
</style>

<div class="user-create">
    <div class="rs-qr-page">
        <!-- Page Header -->
        <div class="rs-qr-header rs-d-flex rs-align-items-center rs-justify-content-between rs-flex-wrap rs-gap-3">
            <div>
                <a href="<?= \App\Environment::url('/qrcode/index') ?>" class="rs-btn rs-btn--sm rs-btn--ghost rs-text-muted" style="text-decoration: none; margin-bottom: 8px; display: inline-block;">
                    <i class="fa-solid fa-arrow-left rs-mr-1"></i> Back to QR Codes
                </a>
                <h1 class="rs-qr-title" style="margin: 0; font-size: 24px; font-weight: 700; color: var(--rs-primary, #234137);">
                    <?= Html::encode($pageTitle) ?>
                </h1>
                <p class="rs-qr-description" style="margin: 4px 0 0 0; color: var(--rs-text-secondary, #7B8A8B); font-size: 14px;">
                    Configure destination URLs, apply high-contrast branded styling, and preview real-time generation.
                </p>
            </div>
        </div>

        <!-- Stepper Indicators -->
        <div class="rs-qr-stepper">
            <ul class="nav nav-pills step-menu-wrap" id="pills-tab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active disabled" id="pills-select-type-tab" data-toggle="pill" href="#pills-select-type" role="tab" aria-controls="pills-select-type" aria-selected="true">
                        <span class="step-num">1</span> Setup
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link disabled" id="pills-customize-tab" data-toggle="pill" href="#pills-customize" role="tab" aria-controls="pills-customize" aria-selected="false">
                        <span class="step-num">2</span> Customize
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link disabled" id="pills-done-tab" data-toggle="pill" href="#pills-done" role="tab" aria-controls="pills-done" aria-selected="false">
                        <span class="step-num">3</span> Done
                    </a>
                </li>
            </ul>
        </div>

        <!-- Studio Loader Indicator -->
        <div id="loader" style="display: none;"></div>

        <!-- Workspace (Studio + Live Preview Sidebar) -->
        <div class="rs-qr-workspace">
            <div class="rs-qr-studio">
                <form
                    id="stepForm"
                    novalidate
                    action="<?= Html::encode($ajaxUrl) ?>"
                    method="POST"
                    enctype="multipart/form-data"
                    class="<?= ($isUpdate ? 'active-form-edit-mode' : '') ?> rs-qr-content"
                >
                    <?php 
                    $effectiveCsrf = $csrfToken ?? $csrf ?? '';
                    if (!empty($effectiveCsrf)): 
                    ?>
                        <input type="hidden" name="_csrf" value="<?= Html::encode($effectiveCsrf) ?>">
                    <?php endif; ?>
                    <input type="hidden" id="qrcode_id" name="id" value="<?= $qrcodeId ?? '' ?>">
                    <input type="hidden" id="get_qrcode_id" value="<?= $qrcodeId ?? '' ?>">

                    <div class="steps">
                        <!-- ========================================== -->
                        <!-- STEP 1: SETUP (URL INPUT)                  -->
                        <!-- ========================================== -->
                        <div class="step">
                            <div class="rs-qr-panel rs-qr-section step-content-wrap">
                                <div class="title-content-caption">
                                    <h2>Website URL</h2>
                                    <p>Paste a URL below to link with your QR code.</p>
                                </div>
                                <div class="form-group url-input">
                                    <label for="qr_gen_form_website_url">Enter URL</label>
                                    <input
                                        required
                                        type="url"
                                        class="form-control rs-form-control"
                                        id="qr_gen_form_website_url"
                                        name="url"
                                        value="<?= Html::encode($model?->getUrl() ?? '') ?>"
                                        placeholder="https://www.google.com"
                                    >
                                    <span id="qr_gen_form_website_url_error" class="rs-form-error"></span>
                                </div>
                            </div>
                        </div>

                        <!-- ========================================== -->
                        <!-- STEP 2: CUSTOMIZE                          -->
                        <!-- ========================================== -->
                        <div class="step">
                            <div class="rs-qr-panel rs-qr-section customize-step">
                                <div class="title-content-caption">
                                    <h2>Design your QR Code</h2>
                                    <p>Customize the design of your QR code with templates, logos, and frames.</p>
                                </div>

                                <div class="tab-wrapper rs-qr-tabs-wrapper">
                                    <ul class="tabs rs-qr-tabs">
                                        <li class="tab-link active" data-tab="1">
                                            <div class="icon">
                                                <img src="<?= $baseUrl ?>/assets/qr/step-icons/Template.svg" alt="Template">
                                            </div>
                                            <div class="text">Templates</div>
                                        </li>
                                        <li class="tab-link" data-tab="2">
                                            <div class="icon">
                                                <img src="<?= $baseUrl ?>/assets/qr/step-icons/Logo.svg" alt="Logo">
                                            </div>
                                            <div class="text">Logo</div>
                                        </li>
                                        <li class="tab-link" data-tab="3">
                                            <div class="icon">
                                                <img src="<?= $baseUrl ?>/assets/qr/step-icons/Frame.svg" alt="Frame">
                                            </div>
                                            <div class="text">Frame</div>
                                        </li>
                                    </ul>
                                </div>

                                <div class="content-wrapper">
                                    <!-- TAB 1: TEMPLATES -->
                                    <div id="tab-1" class="tab-content active">
                                        <div class="tab-content-inner">
                                            <div class="template-headiing">
                                                <span>Select a Template</span>
                                            </div>
                                            <div class="templates-list-wrap">
                                                <div id="slideprev" class="arrow-templates prev-templates"><i class="fa-solid fa-caret-left"></i></div>
                                                <div id="templates_scroll" class="templates-list-caption">
                                                    <?php
                                                    $templates = [
                                                        'bitcoin' => 'bitcoin',
                                                        'classic-frame' => 'classic frame',
                                                        'default' => 'default',
                                                        'easy' => 'easy',
                                                        'facebook' => 'facebook',
                                                        'jungle' => 'jungle',
                                                        'instagram-frame' => 'instagram frame',
                                                        'mosaic' => 'mosaic',
                                                        'ninja' => 'ninja',
                                                        'rain' => 'rain',
                                                        'youtube' => 'youtube',
                                                    ];
                                                    $selectedTemplate = $model?->getTemplateName() ?? 'default';
                                                    foreach ($templates as $tmplKey => $tmplLabel):
                                                        $isTmplActive = ($selectedTemplate === $tmplKey);
                                                    ?>
                                                        <div class="templates-list-item <?= $isTmplActive ? 'active' : '' ?>">
                                                            <div class="templates-img">
                                                                <img src="<?= $baseUrl ?>/assets/qr/templates/<?= $tmplKey ?>.png" alt="<?= Html::encode($tmplLabel) ?>" title="<?= Html::encode($tmplLabel) ?>">
                                                                <input type="hidden" name="template<?= $isTmplActive ? '_name' : '' ?>" value="<?= Html::encode($tmplKey) ?>">
                                                                <div class="remove-text"><i class="fas fa-trash-alt"></i> REMOVE</div>
                                                            </div>
                                                            <div class="templates-info"><?= Html::encode($tmplLabel) ?></div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <div id="slidenext" class="arrow-templates next-templates"><i class="fa-solid fa-caret-right"></i></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- TAB 2: LOGO -->
                                    <div id="tab-2" class="tab-content">
                                        <div class="tab-content-inner">
                                            <div class="template-headiing logo">
                                                <span>SELECT A LOGO</span>
                                            </div>
                                            <p>This will appear at the center of your QR code.</p>

                                            <div class="site-qr-code-logo-type-main-wrap">
                                                <div class="sub-heading">Choose logo type</div>
                                                <div class="site-qr-code-logo-type">
                                                    <?php
                                                    $currentLogoType = $model?->getLogoType() ?? 'default';
                                                    ?>
                                                    <input type="radio" id="uploaded" name="logo_type" value="uploaded" <?= ($currentLogoType === 'uploaded') ? 'checked' : '' ?>>
                                                    <label for="uploaded">Uploaded</label><br>
                                                    <input type="radio" id="default" name="logo_type" value="default" <?= ($currentLogoType !== 'uploaded') ? 'checked' : '' ?>>
                                                    <label for="default">Gallery</label><br>
                                                </div>
                                            </div>

                                            <!-- Gallery Option -->
                                            <div class="site-qrcode-logo-section-wrap-option-1">
                                                <div class="sub-heading">Choose from gallery</div>
                                                <div class="logo-gallery">
                                                    <?php
                                                    $galleryLogos = [
                                                        'app-store', 'bitcoin', 'calender', 'email', 'facebook', 'gmail',
                                                        'google-bussiness', 'instagram', 'linkedin', 'mp3', 'pdf',
                                                        'phone-call', 'pintrest', 'play-store', 'ratings', 'youtube'
                                                    ];
                                                    $selectedLogo = $model?->getDefaultLogoName() ?? '';
                                                    foreach ($galleryLogos as $logoKey):
                                                        $isLogoActive = ($selectedLogo === $logoKey);
                                                    ?>
                                                        <div class="logo-item <?= $isLogoActive ? 'active' : '' ?>">
                                                            <img class="logo" src="<?= $baseUrl ?>/assets/qr/logos/<?= $logoKey ?>.png" alt="<?= $logoKey ?>">
                                                            <input type="hidden" name="default_logo<?= $isLogoActive ? '_name' : '' ?>" value="<?= $logoKey ?>" class="currenlty">
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>

                                            <!-- Upload Option -->
                                            <div class="site-qrcode-logo-section-wrap-option-2" style="display: none;">
                                                <div class="sub-heading">Upload Custom Logo</div>
                                                <div class="fileupload-wrap form-container">
                                                    <div class="upload-files-container">
                                                        <div class="drag-file-area">
                                                            <span class="material-icons-outlined upload-icon"> <i class="fa-solid fa-cloud-arrow-up"></i></span>
                                                            <label class="label">
                                                                <span class="browse-files">
                                                                    <input type="file" class="default-file-input" id="qr_gen_form_logo" name="upload_logo" accept="image/jpg, image/png, image/jpeg">
                                                                    <span>Drag file here or</span>
                                                                    <span class="browse-files-text">browse file</span>
                                                                </span>
                                                                <?php if ($model?->isUploadedLogo() && !empty($model->getUploadLogo())): ?>
                                                                    <img src="<?= $baseUrl . '/' . ltrim($model->getUploadLogo(), '/') ?>" class="existing-image" alt="Uploaded Logo">
                                                                <?php endif; ?>
                                                            </label>
                                                            <div class="dynamic-message">
                                                                Minimum resolution: 250x250 pixels. Maximum: 1080x1080. 500 KB max file size.<br>
                                                                <span class="text-error-msg" style="color: #DC2626;"></span>
                                                            </div>
                                                            <div id="image_preview" style="width: 100%;"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- TAB 3: FRAMES -->
                                    <div id="tab-3" class="tab-content">
                                        <div class="tab-content-inner">
                                            <div class="template-headiing logo">
                                                <span>SELECT A FRAME</span>
                                            </div>
                                            <p>Make your QR code print-ready by adding a branded frame.</p>

                                            <div class="qr-frame-container">
                                                <?php
                                                $frames = [
                                                    'default', 'balloon-bottom', 'balloon-top', 'banner-bottom',
                                                    'banner-top', 'box-bottom', 'box-Top', 'focus',
                                                    'default-7', 'balloon-bottom-1', 'balloon-top-2', 'banner-bottom-3',
                                                    'banner-top-4', 'box-bottom-5', 'box-Top-6', 'focus-8'
                                                ];
                                                $selectedFrame = $model?->getFrameName() ?? ($selectedTemplate === 'classic-frame' ? 'banner-bottom' : '');
                                                foreach ($frames as $frameKey):
                                                    $isFrameActive = (!empty($selectedFrame) && $selectedFrame === $frameKey);
                                                ?>
                                                    <div class="qr-frame-item <?= $isFrameActive ? 'active' : '' ?>">
                                                        <img src="<?= $baseUrl ?>/assets/qr/frames/<?= $frameKey ?>.png" alt="<?= $frameKey ?>" title="<?= $frameKey ?>">
                                                        <input type="hidden" name="frame<?= $isFrameActive ? '_name' : '' ?>" value="<?= $frameKey ?>" class="currenlty">
                                                        <div class="remove-text"><i class="fas fa-trash-alt"></i> REMOVE</div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ========================================== -->
                        <!-- STEP 3: DONE (NAME & STATUS)               -->
                        <!-- ========================================== -->
                        <div class="step">
                            <div class="rs-qr-panel rs-qr-section complete-step">
                                <div class="title-content-caption">
                                    <h2><b>Congratulations!</b> Your QR Code is ready.</h2>
                                    <p>Name your code and review settings before generating.</p>
                                </div>
                                <div class="form-group name-qr">
                                    <label for="qr-code-name">Enter a name for your QR code</label>
                                    <input
                                        class="form-control rs-form-control"
                                        type="text"
                                        placeholder="Name your QR code"
                                        name="name"
                                        id="qr-code-name"
                                        value="<?= Html::encode($model?->getName() ?? '') ?>"
                                        required
                                    >
                                    <span id="qr_gen_form_name_error" class="rs-form-error" style="color: #DC2626;"></span>
                                </div>

                                <?php if ($isUpdate): ?>
                                    <div class="form-group name-qr" style="margin-top: 16px;">
                                        <label for="qr-code-status">QR Code Status</label>
                                        <select class="form-control rs-form-control form-select" name="status" id="qr-code-status">
                                            <option value="active" <?= ($model?->getStatus() === 'active') ? 'selected' : '' ?>>Active</option>
                                            <option value="deactive" <?= ($model?->getStatus() === 'deactive') ? 'selected' : '' ?>>Deactive</option>
                                        </select>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Step Navigation Actions -->
                    <div class="rs-qr-navigation actions">
                        <button type="button" class="rs-btn rs-btn--secondary" id="prevBtn" style="display: none;">
                            <i class="fa-solid fa-angle-left"></i> Previous
                        </button>
                        <button type="button" class="rs-btn rs-btn--primary" id="nextBtn">
                            Next <i class="fa-solid fa-angle-right"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Live Preview Sidebar -->
            <div class="rs-qr-preview-sidebar">
                <div class="rs-qr-preview-card">
                    <div class="rs-qr-preview-header">
                        <h3>Live Preview</h3>
                    </div>
                    <div class="rs-qr-preview-body" id="qrCodeImage">
                        <div class="site-gen-qr-code-design-wrap rs-qr-generated-wrapper">
                            <img class="qr-code-image" src="<?= Html::encode($initialPreviewImage) ?>" alt="Generated QR Code">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts for Stepper, Select2, and Dynamic Generation -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script src="<?= \App\Shared\AssetVersion::append(\App\Environment::url('/assets/qr/js/qr-code-form.js')) ?>"></script>
