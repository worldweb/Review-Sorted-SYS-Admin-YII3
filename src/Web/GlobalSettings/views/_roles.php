<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var array<string, ?string> $settings
 */
?>

<div class="rs-alert rs-alert--info rs-mb-4">
    Configure default permissions across system roles: <code>super</code>, <code>admin</code>, <code>account_admin</code>, <code>worker</code>.
</div>

<div class="rs-table-wrapper" style="overflow-x: auto;">
    <table class="rs-table rs-table--hover" style="width: 100%;">
        <thead>
            <tr>
                <th>Feature Permission</th>
                <th style="text-align: center; width: 160px;">Account Admin</th>
                <th style="text-align: center; width: 160px;">Worker</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="rs-font-semibold">Social Connections (Facebook/Google)</td>
                <td style="text-align: center;"><span class="rs-badge rs-badge--success">Allowed</span></td>
                <td style="text-align: center;"><span class="rs-badge rs-badge--danger">Restricted</span></td>
            </tr>
            <tr>
                <td class="rs-font-semibold">Social Post Composer &amp; Studio</td>
                <td style="text-align: center;"><span class="rs-badge rs-badge--success">Allowed</span></td>
                <td style="text-align: center;"><span class="rs-badge rs-badge--success">Allowed</span></td>
            </tr>
            <tr>
                <td class="rs-font-semibold">Reviews &amp; Customer Feedback</td>
                <td style="text-align: center;"><span class="rs-badge rs-badge--success">Allowed</span></td>
                <td style="text-align: center;"><span class="rs-badge rs-badge--success">Allowed</span></td>
            </tr>
            <tr>
                <td class="rs-font-semibold">QR Code Studio</td>
                <td style="text-align: center;"><span class="rs-badge rs-badge--success">Allowed</span></td>
                <td style="text-align: center;"><span class="rs-badge rs-badge--success">Allowed</span></td>
            </tr>
            <tr>
                <td class="rs-font-semibold">Email &amp; WhatsApp Campaigns</td>
                <td style="text-align: center;"><span class="rs-badge rs-badge--success">Allowed</span></td>
                <td style="text-align: center;"><span class="rs-badge rs-badge--success">Allowed</span></td>
            </tr>
            <tr>
                <td class="rs-font-semibold">Billing &amp; Subscriptions</td>
                <td style="text-align: center;"><span class="rs-badge rs-badge--success">Allowed</span></td>
                <td style="text-align: center;"><span class="rs-badge rs-badge--danger">Restricted</span></td>
            </tr>
        </tbody>
    </table>
</div>
