<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Not Found / Error View (Yii 3 Modern Theme)
 *
 * @var Yiisoft\View\WebView $this
 * @var \App\Shared\ApplicationParams $applicationParams
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 */

$this->setTitle('404 Page Not Found');
$path = $currentRoute->getUri()?->getPath() ?? 'unknown';
?>

<div class="rs-auth-layout">
    <div class="rs-auth-card" style="max-width: 520px; text-align: center; margin: 40px auto;">
        <div class="rs-d-flex rs-justify-content-center rs-mb-4" style="display: flex; justify-content: center; margin-bottom: 20px;">
            <div style="width: 64px; height: 64px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center;">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
        </div>

        <h1 class="rs-auth-title rs-mb-2" style="font-size: 1.5rem; margin-bottom: 8px;">Page Not Found</h1>

        <div class="rs-text-muted rs-mb-6" style="margin-bottom: 20px;">
            The requested URI <code class="rs-text-monospace" style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px;"><?= Html::encode($path) ?></code> was not found on this server.
        </div>

        <div class="rs-p-4 rs-border rs-radius-md rs-mb-6" style="background: #f8fafc; border: 1px solid var(--rs-border, #E2E8F0); border-radius: 8px; padding: 16px; margin-bottom: 24px; font-size: 0.875rem; color: #64748b; text-align: left;">
            <p style="margin: 0 0 8px 0;">The page you are looking for may have been moved, deleted, or does not exist.</p>
            <p style="margin: 0;">Please contact platform support if you believe this is in error.</p>
        </div>

        <a href="/sys-yii3/" class="rs-btn rs-btn--primary rs-btn--block rs-btn--lg" style="text-decoration: none;">
            Return to Dashboard
        </a>
    </div>
</div>
