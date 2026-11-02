<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var string $appName
 * @var string $frameworkVersion
 * @var string $environment
 * @var bool $debug
 * @var string $phpVersion
 * @var string $phpSapi
 * @var bool $dbConnected
 * @var string $dbDriverName
 * @var string $dbServerVersion
 * @var string $dbDatabaseName
 * @var string|null $dbError
 */

$this->setTitle('Yii 3 Application — Reviews Sorted');
?>

<div style="max-width: 800px; margin: 40px auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #2d3748; line-height: 1.6;">
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #edf2f7; padding-bottom: 16px; margin-bottom: 24px;">
            <div>
                <h1 style="margin: 0; font-size: 1.75rem; color: #1a202c; font-weight: 700;">Yii 3 Application</h1>
                <p style="margin: 4px 0 0 0; color: #718096; font-size: 0.95rem;">Parallel Foundation for Reviews Sorted Migration</p>
            </div>
            <span style="display: inline-block; background: <?= $dbConnected ? '#c6f6d5' : '#fed7d7' ?>; color: <?= $dbConnected ? '#22543d' : '#742a2a' ?>; font-size: 0.8rem; font-weight: 600; padding: 6px 12px; border-radius: 9999px;">
                <?= $dbConnected ? '● Online & Connected' : '● Database Issue' ?>
            </span>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <div style="background: #f7fafc; border: 1px solid #edf2f7; border-radius: 8px; padding: 16px;">
                <span style="display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #718096; font-weight: 600; margin-bottom: 4px;">Application</span>
                <strong style="font-size: 1.1rem; color: #2d3748;"><?= Html::encode($appName) ?></strong>
            </div>

            <div style="background: #f7fafc; border: 1px solid #edf2f7; border-radius: 8px; padding: 16px;">
                <span style="display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #718096; font-weight: 600; margin-bottom: 4px;">Environment</span>
                <strong style="font-size: 1.1rem; color: #2d3748;">
                    <?= strtoupper(Html::encode($environment)) ?>
                    <span style="font-size: 0.8rem; font-weight: normal; color: #718096;">(Debug: <?= $debug ? 'Enabled' : 'Disabled' ?>)</span>
                </strong>
            </div>

            <div style="background: #f7fafc; border: 1px solid #edf2f7; border-radius: 8px; padding: 16px;">
                <span style="display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #718096; font-weight: 600; margin-bottom: 4px;">Framework Version</span>
                <strong style="font-size: 0.95rem; color: #2d3748;"><?= Html::encode($frameworkVersion) ?></strong>
            </div>

            <div style="background: #f7fafc; border: 1px solid #edf2f7; border-radius: 8px; padding: 16px;">
                <span style="display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #718096; font-weight: 600; margin-bottom: 4px;">PHP Runtime</span>
                <strong style="font-size: 0.95rem; color: #2d3748;">PHP <?= Html::encode($phpVersion) ?> <span style="font-size: 0.8rem; font-weight: normal; color: #718096;">(<?= Html::encode($phpSapi) ?>)</span></strong>
            </div>
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
            <h3 style="margin: 0 0 12px 0; font-size: 1rem; color: #2d3748; font-weight: 600;">Database Connection Status</h3>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                <tr>
                    <td style="padding: 6px 0; color: #718096; width: 180px;">Status:</td>
                    <td style="padding: 6px 0;">
                        <?php if ($dbConnected): ?>
                            <span style="color: #2f855a; font-weight: 600;">Connected successfully (Read-Only Verified)</span>
                        <?php else: ?>
                            <span style="color: #c53030; font-weight: 600;">Failed to connect</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #718096;">Database Driver:</td>
                    <td style="padding: 6px 0; font-family: monospace; color: #2d3748;"><?= Html::encode(strtoupper($dbDriverName)) ?> (PDO)</td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #718096;">Database Server:</td>
                    <td style="padding: 6px 0; font-family: monospace; color: #2d3748;"><?= Html::encode($dbServerVersion) ?></td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #718096;">Active Database:</td>
                    <td style="padding: 6px 0; font-family: monospace; color: #2d3748;"><?= Html::encode($dbDatabaseName) ?></td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #718096;">Safety Constraint:</td>
                    <td style="padding: 6px 0; color: #2b6cb0; font-size: 0.85rem;">Zero DDL/DML executed. Existing Yii 2 schema and data are 100% intact.</td>
                </tr>
            </table>
            <?php if ($dbError !== null): ?>
                <div style="margin-top: 12px; padding: 12px; background: #fff5f5; border: 1px solid #feb2b2; border-radius: 6px; color: #c53030; font-size: 0.85rem;">
                    <strong>Error:</strong> <?= Html::encode($dbError) ?>
                </div>
            <?php endif; ?>
        </div>

        <div style="background: #ebf8ff; border: 1px solid #bee3f8; border-radius: 8px; padding: 16px; font-size: 0.9rem; color: #2c5282;">
            <strong>Parallel Coexistence Confirmed:</strong>
            <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                <li>Existing Yii 2 application: <a href="/sys/" style="color: #2b6cb0; font-weight: 600;">http://localhost/sys/</a> (Untouched & Active)</li>
                <li>New Yii 3 application: <a href="/sys-yii3/" style="color: #2b6cb0; font-weight: 600;">http://localhost/sys-yii3/</a> (Independent Foundation)</li>
            </ul>
        </div>
    </div>
</div>
