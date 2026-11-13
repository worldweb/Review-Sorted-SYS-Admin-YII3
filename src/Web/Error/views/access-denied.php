<?php

declare(strict_types=1);

use App\Environment;
use Yiisoft\Html\Html;

/**
 * Access Denied (403 Forbidden) View (Yii 3 Modern Theme)
 *
 * @var Yiisoft\View\WebView $this
 * @var string $path
 * @var \App\Domain\User\User|null $currentUser
 */

$this->setTitle('403 Access Denied');
?>

<div class="rs-card" style="max-width: 560px; margin: 40px auto; text-align: center; padding: 36px 32px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);">
    <div class="rs-d-flex rs-justify-content-center rs-mb-4" style="display: flex; justify-content: center; margin-bottom: 24px;">
        <div style="width: 72px; height: 72px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center;">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </div>
    </div>

    <h1 class="rs-h2 rs-mb-2" style="font-size: 1.625rem; font-weight: 700; color: var(--rs-text-main, #0f172a); margin-bottom: 8px;">
        Access Denied
    </h1>

    <p style="font-size: 1.0625rem; color: var(--rs-text-muted, #475569); margin-bottom: 20px; font-weight: 500;">
        You don't have permission to access this page.
    </p>

    <div class="rs-p-4 rs-border rs-radius-md rs-mb-4" style="background: #f8fafc; border: 1px solid var(--rs-border, #E2E8F0); border-radius: 8px; padding: 16px; margin-bottom: 24px; font-size: 0.875rem; color: #64748b; text-align: left;">
        <p style="margin: 0 0 8px 0;">
            Please contact your administrator if you believe you should have access.
        </p>
        <p style="margin: 0; font-size: 0.8125rem;">
            Requested resource: <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: #334155; font-family: monospace;"><?= Html::encode($path) ?></code>
        </p>
    </div>

    <div class="rs-d-flex rs-gap-3 rs-justify-content-center" style="display: flex; gap: 12px; justify-content: center;">
        <a href="<?= Environment::url('/') ?>" class="rs-btn rs-btn--primary" style="min-width: 160px; text-decoration: none;">
            Return to Dashboard
        </a>
    </div>
</div>

