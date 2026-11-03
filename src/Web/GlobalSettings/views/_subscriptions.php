<?php

declare(strict_types=1);

/**
 * @var array<string, ?string> $settings
 */
?>

<div class="mb-3">
    <label for="stripe_public_key" class="form-label fw-bold">Stripe Publishable Key</label>
    <input type="text" class="form-control font-monospace" id="stripe_public_key" name="settings[stripe.public_key]" value="<?= htmlspecialchars($settings['stripe.public_key'] ?? '') ?>" placeholder="pk_live_...">
</div>

<div class="mb-3">
    <label for="stripe_secret_key" class="form-label fw-bold">Stripe Secret Key</label>
    <input type="password" class="form-control font-monospace" id="stripe_secret_key" name="settings[stripe.secret_key]" value="<?= htmlspecialchars($settings['stripe.secret_key'] ?? '') ?>" placeholder="sk_live_...">
</div>

<div class="mb-3">
    <label for="stripe_webhook_secret" class="form-label fw-bold">Stripe Webhook Signing Secret</label>
    <input type="password" class="form-control font-monospace" id="stripe_webhook_secret" name="settings[stripe.webhook_secret]" value="<?= htmlspecialchars($settings['stripe.webhook_secret'] ?? '') ?>" placeholder="whsec_...">
</div>

<div class="mb-3">
    <label for="currency" class="form-label fw-bold">Default Billing Currency</label>
    <input type="text" class="form-control" id="currency" name="settings[stripe.currency]" value="<?= htmlspecialchars($settings['stripe.currency'] ?? 'usd') ?>">
</div>
