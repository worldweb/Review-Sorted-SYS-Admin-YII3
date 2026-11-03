<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var array<string, ?string> $settings
 */
?>

<div class="rs-form-group rs-mb-4">
    <label for="stripe_public_key" class="rs-label">Stripe Publishable Key</label>
    <input type="text" class="rs-input rs-text-monospace" id="stripe_public_key" name="settings[stripe.public_key]" value="<?= Html::encode($settings['stripe.public_key'] ?? '') ?>" placeholder="pk_live_...">
</div>

<div class="rs-form-group rs-mb-4">
    <label for="stripe_secret_key" class="rs-label">Stripe Secret Key</label>
    <input type="password" class="rs-input rs-text-monospace" id="stripe_secret_key" name="settings[stripe.secret_key]" value="<?= Html::encode($settings['stripe.secret_key'] ?? '') ?>" placeholder="sk_live_...">
</div>

<div class="rs-form-group rs-mb-4">
    <label for="stripe_webhook_secret" class="rs-label">Stripe Webhook Signing Secret</label>
    <input type="password" class="rs-input rs-text-monospace" id="stripe_webhook_secret" name="settings[stripe.webhook_secret]" value="<?= Html::encode($settings['stripe.webhook_secret'] ?? '') ?>" placeholder="whsec_...">
</div>

<div class="rs-form-group rs-mb-4">
    <label for="currency" class="rs-label">Default Billing Currency</label>
    <input type="text" class="rs-input" id="currency" name="settings[stripe.currency]" value="<?= Html::encode($settings['stripe.currency'] ?? 'usd') ?>">
</div>
