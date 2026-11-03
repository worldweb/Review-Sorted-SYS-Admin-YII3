<?php

declare(strict_types=1);

/**
 * @var array<string, ?string> $settings
 */
?>

<div class="alert alert-info">
    <i class="bi bi-info-circle me-1"></i>Configure default permissions across system roles: <code>super</code>, <code>admin</code>, <code>account_admin</code>, <code>worker</code>.
</div>

<div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th>Feature Permission</th>
                <th class="text-center">Account Admin</th>
                <th class="text-center">Worker</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Social Connections (Facebook/Google)</td>
                <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                <td class="text-center"><i class="bi bi-x-circle-fill text-danger"></i></td>
            </tr>
            <tr>
                <td>Social Post Composer &amp; Studio</td>
                <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
            </tr>
            <tr>
                <td>Reviews &amp; Customer Feedback</td>
                <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
            </tr>
            <tr>
                <td>QR Code Studio</td>
                <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
            </tr>
            <tr>
                <td>Email &amp; WhatsApp Campaigns</td>
                <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
            </tr>
            <tr>
                <td>Billing &amp; Subscriptions</td>
                <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                <td class="text-center"><i class="bi bi-x-circle-fill text-danger"></i></td>
            </tr>
            <tr>
                <td>Live Chat Inbox</td>
                <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
            </tr>
        </tbody>
    </table>
</div>
