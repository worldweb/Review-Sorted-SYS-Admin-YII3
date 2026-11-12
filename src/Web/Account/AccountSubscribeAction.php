<?php

declare(strict_types=1);

namespace App\Web\Account;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\Subscription\Provider\StripeClientInterface;
use App\Domain\Subscription\SubscriptionRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * AccountSubscribeAction.
 * Handles AJAX requests for subscription checkout, plan upgrades, and payment insertion
 * matching legacy AccountsController::actionSubscribe.
 */
final class AccountSubscribeAction
{
    private LoggerInterface $logger;

    public function __construct(
        private AccountRepositoryInterface $accountRepo,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private ResponseFactoryInterface $responseFactory,
        private SubscriptionRepositoryInterface $subscriptionRepo,
        private StripeClientInterface $stripeClient,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $this->tenantContextProvider->get();
        if ($context->isGuest()) {
            return $this->jsonResponse(['status' => false, 'error' => 'User is not authenticated.']);
        }

        $currentUserId = $context->getUserId();
        $currentUser = $this->userRepo->findById($currentUserId, $context);
        if ($currentUser === null) {
            return $this->jsonResponse(['status' => false, 'error' => 'User is not authenticated.']);
        }

        // Role authorization: only account admin or platform admin can manage subscriptions
        if (!$currentUser->isAdmin() && !$currentUser->isAccountAdmin()) {
            return $this->jsonResponse(['status' => false, 'error' => 'Forbidden: You are not authorized to manage subscriptions.']);
        }

        $billingOwnerId = $currentUser->getOwnerId() ?: $currentUser->getId();
        $accountId = $currentUser->getAccountId() ?? 0;

        $parsedBody = (array) ($request->getParsedBody() ?? []);
        $requestType = (string) ($parsedBody['request_type'] ?? '');
        $planId = isset($parsedBody['plan_id']) ? (int) $parsedBody['plan_id'] : 0;

        // =========================================================================
        // FLOW 1: PAID-TO-PAID PLAN CHANGE WITH STRIPE PRORATION (EXISTING SUBSCRIBER)
        // =========================================================================
        if ($requestType === 'change_paid_plan') {
            return $this->handleChangePaidPlan($billingOwnerId, $accountId, $planId);
        }

        // =========================================================================
        // FLOW 2: INITIAL CUSTOMER & SUBSCRIPTION CREATION (NEW SUBSCRIBER)
        // =========================================================================
        if ($requestType === 'create_customer_subscription') {
            return $this->handleCreateCustomerSubscription($billingOwnerId, $planId, $parsedBody);
        }

        // =========================================================================
        // FLOW 3: PAYMENT CONFIRMATION INSERTION (NEW SUBSCRIBER COMPLETION)
        // =========================================================================
        if ($requestType === 'payment_insert') {
            return $this->handlePaymentInsert($currentUser->getId(), $accountId, $planId, $parsedBody);
        }

        return $this->jsonResponse([
            'status' => false,
            'error' => 'Invalid or unrecognized subscription request type.',
        ]);
    }

    /**
     * Handles paid-to-paid plan upgrade with Stripe proration.
     */
    private function handleChangePaidPlan(int $billingOwnerId, int $accountId, int $planId): ResponseInterface
    {
        try {
            // 1. Locate current active subscription
            $currentSub = $this->subscriptionRepo->findActiveSubscriptionByUserId($billingOwnerId);
            if ($currentSub === null || empty($currentSub->getStripeSubscriptionId())) {
                return $this->jsonResponse([
                    'status' => false,
                    'error' => 'No active paid subscription found to change. Please complete initial subscription.',
                ]);
            }

            // 2. Validate target plan server-side
            $targetPlan = $this->subscriptionRepo->findPlanById($planId);
            if ($targetPlan === null || !$targetPlan->isActive()) {
                return $this->jsonResponse([
                    'status' => false,
                    'error' => 'The selected plan is not currently available.',
                ]);
            }

            if ((float) $targetPlan->getPlanPrice() <= 0) {
                return $this->jsonResponse([
                    'status' => false,
                    'error' => 'Free tier transitions must be processed through free downgrade, not paid plan change.',
                ]);
            }

            // 3. Same Plan Protection
            if ((int) $currentSub->getPlanId() === $targetPlan->getId()) {
                return $this->jsonResponse([
                    'status' => false,
                    'error' => 'You are already subscribed to the ' . $targetPlan->getPlanName() . ' plan.',
                ]);
            }

            // 4. Verify Stripe processor is configured
            if (!$this->stripeClient->isConfigured()) {
                return $this->jsonResponse([
                    'status' => false,
                    'error' => 'Payment processor is not configured. Real plan change cannot be performed.',
                ]);
            }

            // 5. Retrieve existing Stripe subscription
            $stripeSub = $this->stripeClient->retrieveSubscription($currentSub->getStripeSubscriptionId());
            if ($stripeSub === null || !in_array($stripeSub['status'] ?? '', ['active', 'trialing'], true)) {
                $status = $stripeSub['status'] ?? 'none';
                return $this->jsonResponse([
                    'status' => false,
                    'error' => "Current subscription is not in an active state on Stripe (Status: {$status}).",
                ]);
            }

            $itemsData = $stripeSub['items']['data'] ?? [];
            if (empty($itemsData)) {
                return $this->jsonResponse([
                    'status' => false,
                    'error' => 'Existing subscription items could not be found on Stripe.',
                ]);
            }
            $subscriptionItemId = (string) $itemsData[0]['id'];

            // 6. Create Stripe Plan for target tier
            $interval = $targetPlan->getPlanLimit() === 'year' ? 'year' : 'month';
            $stripePlan = $this->stripeClient->createPlan([
                'product' => [
                    'name' => $targetPlan->getPlanName(),
                ],
                'amount' => (int) round(((float) $targetPlan->getPlanPrice()) * 100),
                'currency' => 'usd',
                'interval' => $interval,
                'interval_count' => 1,
            ]);

            // 7. Update existing Stripe subscription with proration enabled
            $updatedSubscription = $this->stripeClient->updateSubscription($currentSub->getStripeSubscriptionId(), [
                'items' => [
                    [
                        'id' => $subscriptionItemId,
                        'plan' => $stripePlan['id'],
                    ],
                ],
                'proration_behavior' => 'create_prorations',
                'expand' => ['latest_invoice.payment_intent'],
            ]);

            // 8. Synchronize local subscription record
            $updateData = [
                'plan_id' => (string) $targetPlan->getId(),
                'stripe_plan_id' => $stripePlan['id'],
                'paid_amount' => (string) $targetPlan->getPlanPrice(),
                'paid_amount_currency' => 'usd',
                'plan_interval' => $interval,
                'plan_interval_count' => '1',
                'stripe_status' => 'succeeded',
                'status' => 'active',
            ];

            if (!empty($updatedSubscription['current_period_start'])) {
                $updateData['plan_period_start'] = date('Y-m-d H:i:s', (int) $updatedSubscription['current_period_start']);
            }
            if (!empty($updatedSubscription['current_period_end'])) {
                $updateData['plan_period_end'] = date('Y-m-d H:i:s', (int) $updatedSubscription['current_period_end']);
            }

            $this->subscriptionRepo->updateSubscription($currentSub->getId(), $updateData);

            // 9. Synchronize Account entitlements
            if ($accountId > 0) {
                $this->accountRepo->updateSubscriptionEntitlements($accountId, $targetPlan->getPlanPrice(), true);
            }

            // 10. Synchronize Stripe invoice if generated
            try {
                $latestInvoice = $updatedSubscription['latest_invoice'] ?? null;
                $latestInvoiceId = is_array($latestInvoice) ? ($latestInvoice['id'] ?? null) : $latestInvoice;
                if (!empty($latestInvoiceId)) {
                    $invoiceObj = $this->stripeClient->retrieveInvoice((string) $latestInvoiceId);
                    if ($invoiceObj !== null) {
                        $this->subscriptionRepo->syncAccountInvoice($invoiceObj, $accountId, $billingOwnerId);
                    }
                }
            } catch (Throwable $invE) {
                $this->logger->error('Invoice sync exception in change_paid_plan: ' . $invE->getMessage());
            }

            return $this->jsonResponse([
                'status' => true,
                'success' => 'Plan upgraded successfully to ' . $targetPlan->getPlanName() . '! Prorated adjustments have been applied to your billing account.',
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Error during plan change: ' . $e->getMessage());
            return $this->jsonResponse([
                'status' => false,
                'error' => 'An error occurred while updating your plan: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Handles initial customer & subscription creation on Stripe.
     *
     * @param array<string, mixed> $body
     */
    private function handleCreateCustomerSubscription(int $billingOwnerId, int $planId, array $body): ResponseInterface
    {
        try {
            // Safeguard against duplicate subscription creation
            $existingActiveSub = $this->subscriptionRepo->findActiveSubscriptionByUserId($billingOwnerId);
            if ($existingActiveSub !== null && !empty($existingActiveSub->getStripeSubscriptionId())) {
                return $this->jsonResponse([
                    'error' => 'You already have an active paid subscription. Please use the Change Plan feature to switch plans.',
                ]);
            }

            // Validate plan
            $planDetails = $this->subscriptionRepo->findPlanById($planId);
            if ($planDetails === null || !$planDetails->isActive()) {
                return $this->jsonResponse(['error' => 'Selected subscription plan is invalid or inactive.']);
            }

            // Verify Stripe processor is configured
            if (!$this->stripeClient->isConfigured()) {
                return $this->jsonResponse([
                    'error' => 'Payment processor is not configured. Please contact support.',
                ]);
            }

            $fname = trim((string) ($body['customer_fname'] ?? ''));
            $lname = trim((string) ($body['customer_lname'] ?? ''));
            $fullname = trim($fname . ' ' . $lname);
            $email = trim((string) ($body['customer_email'] ?? ''));

            $customerAddress = [
                'city' => (string) ($body['customer_city'] ?? ''),
                'line1' => (string) ($body['customer_address'] ?? ''),
                'line2' => (string) ($body['customer_address2'] ?? ''),
                'postal_code' => (string) ($body['customer_postal_code'] ?? ''),
                'state' => (string) ($body['customer_state'] ?? ''),
            ];

            $country = (string) ($body['customer_country'] ?? '');
            if ($country !== '' && preg_match('/^[A-Za-z]{2}$/', $country) && strtoupper($country) !== 'OTHER') {
                $customerAddress['country'] = strtoupper($country);
            }

            // Create Stripe customer
            $customer = $this->stripeClient->createCustomer([
                'name' => $fullname !== '' ? $fullname : $email,
                'email' => $email,
                'address' => $customerAddress,
            ]);

            $interval = $planDetails->getPlanLimit() === 'year' ? 'year' : 'month';

            // Create Stripe plan
            $plan = $this->stripeClient->createPlan([
                'product' => [
                    'name' => $planDetails->getPlanName(),
                ],
                'amount' => (int) round(((float) $planDetails->getPlanPrice()) * 100),
                'currency' => 'usd',
                'interval' => $interval,
                'interval_count' => 1,
            ]);

            // Create incomplete subscription
            $subscription = $this->stripeClient->createSubscription([
                'customer' => $customer['id'],
                'items' => [
                    ['plan' => $plan['id']],
                ],
                'payment_behavior' => 'default_incomplete',
                'payment_settings' => ['save_default_payment_method' => 'on_subscription'],
                'expand' => ['latest_invoice.payment_intent'],
            ]);

            $clientSecret = null;
            if (!empty($subscription['latest_invoice']['payment_intent']['client_secret'])) {
                $clientSecret = $subscription['latest_invoice']['payment_intent']['client_secret'];
            }

            return $this->jsonResponse([
                'subscriptionId' => $subscription['id'] ?? '',
                'clientSecret' => $clientSecret,
                'customerId' => $customer['id'] ?? '',
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Error in create_customer_subscription: ' . $e->getMessage());
            return $this->jsonResponse(['error' => 'Failed to initialize subscription: ' . $e->getMessage()]);
        }
    }

    /**
     * Handles payment confirmation and local persistence (FLOW 3).
     * Enforces strict, independent backend verification of PaymentIntent against Stripe API.
     * Never trusts frontend status or client-submitted payment objects.
     *
     * @param array<string, mixed> $body
     */
    private function handlePaymentInsert(int $userId, int $accountId, int $planId, array $body): ResponseInterface
    {
        try {
            // 1. Strict Processor Check: Entitlements are NEVER granted if Stripe is not configured
            if (!$this->stripeClient->isConfigured()) {
                return $this->jsonResponse([
                    'status' => false,
                    'error' => 'Payment processor is not configured. Live payment verification failed.',
                ]);
            }

            // 2. Validate Required Identifiers
            $paymentIntentId = (string) ($body['payment_intent_id'] ?? ($body['payment_intent']['id'] ?? ''));
            $subscriptionId = (string) ($body['subscription_id'] ?? '');
            $customerId = (string) ($body['customer_id'] ?? '');

            if ($paymentIntentId === '' || $subscriptionId === '' || $customerId === '') {
                return $this->jsonResponse([
                    'status' => false,
                    'error' => 'Missing required payment confirmation parameters.',
                ]);
            }

            // 3. Validate Plan
            $planDetails = $this->subscriptionRepo->findPlanById($planId);
            if ($planDetails === null || !$planDetails->isActive()) {
                return $this->jsonResponse([
                    'status' => false,
                    'error' => 'Selected subscription plan is invalid or inactive.',
                ]);
            }

            // 4. Idempotency Check: Handle race conditions if webhook already processed this subscription
            $existingSub = $this->subscriptionRepo->findSubscriptionByStripeId($subscriptionId);
            if ($existingSub !== null && $existingSub->isActive()) {
                if ($accountId > 0) {
                    $this->accountRepo->updateSubscriptionEntitlements($accountId, $planDetails->getPlanPrice(), true);
                }
                return $this->jsonResponse([
                    'status' => true,
                    'success' => 'Subscription is already active!',
                    'subscription_record_id' => $existingSub->getId(),
                ]);
            }

            // 5. Independent Backend Verification: Retrieve PaymentIntent from Stripe API
            $verifiedIntent = $this->stripeClient->retrievePaymentIntent($paymentIntentId);
            if ($verifiedIntent === null) {
                return $this->jsonResponse([
                    'status' => false,
                    'error' => 'Unable to verify payment with payment processor.',
                ]);
            }

            // Verify Status
            $status = (string) ($verifiedIntent['status'] ?? '');
            if ($status !== 'succeeded') {
                return $this->jsonResponse([
                    'status' => false,
                    'error' => "Payment verification failed: Payment status on Stripe is '{$status}'.",
                ]);
            }

            // Verify Customer Association
            $intentCustomer = (string) ($verifiedIntent['customer'] ?? '');
            if ($intentCustomer !== '' && $intentCustomer !== $customerId) {
                return $this->jsonResponse([
                    'status' => false,
                    'error' => 'Payment customer does not match the subscription account.',
                ]);
            }

            // Verify Paid Amount and Currency
            $expectedAmountCents = (int) round(((float) $planDetails->getPlanPrice()) * 100);
            $actualAmountCents = (int) ($verifiedIntent['amount_received'] ?? ($verifiedIntent['amount'] ?? 0));
            if ($actualAmountCents < $expectedAmountCents) {
                return $this->jsonResponse([
                    'status' => false,
                    'error' => 'Payment amount verification failed: paid amount does not match plan price.',
                ]);
            }

            $paidCurrency = strtolower((string) ($verifiedIntent['currency'] ?? 'usd'));
            $paidAmount = ((float) $actualAmountCents) / 100;
            $created = date('Y-m-d H:i:s', (int) ($verifiedIntent['created'] ?? time()));

            // 6. Retrieve Subscription and Customer Details from Stripe
            $planPriceId = null;
            $planInterval = $planDetails->getPlanLimit() === 'year' ? 'year' : 'month';
            $planIntervalCount = '1';
            $currentPeriodStart = $created;
            $currentPeriodEnd = null;
            $customerName = '';
            $customerEmail = '';
            $defaultPaymentMethod = null;
            $defaultSource = null;

            $subData = $this->stripeClient->retrieveSubscription($subscriptionId);
            if ($subData !== null) {
                $defaultPaymentMethod = (string) ($subData['default_payment_method'] ?? '');
                $defaultSource = (string) ($subData['default_source'] ?? '');
                $planObj = $subData['plan'] ?? ($subData['items']['data'][0]['plan'] ?? null);
                if ($planObj) {
                    $planPriceId = (string) ($planObj['id'] ?? '');
                    $planInterval = (string) ($planObj['interval'] ?? 'month');
                    $planIntervalCount = (string) ($planObj['interval_count'] ?? '1');
                }
                if (!empty($subData['current_period_start'])) {
                    $currentPeriodStart = date('Y-m-d H:i:s', (int) $subData['current_period_start']);
                }
                if (!empty($subData['current_period_end'])) {
                    $currentPeriodEnd = date('Y-m-d H:i:s', (int) $subData['current_period_end']);
                }
            }

            $cusData = $this->stripeClient->retrieveCustomer($customerId);
            if ($cusData !== null) {
                $customerName = (string) ($cusData['name'] ?? '');
                $customerEmail = (string) ($cusData['email'] ?? '');
            }

            // 7. Activate Account Entitlements ONLY after successful backend verification
            if ($accountId > 0) {
                $this->accountRepo->updateSubscriptionEntitlements($accountId, $planDetails->getPlanPrice(), true);
            }

            // 8. Idempotently Insert or Update Subscription Record
            if ($existingSub !== null) {
                $subRecordId = $existingSub->getId();
                $this->subscriptionRepo->updateSubscription($subRecordId, [
                    'plan_id' => (string) $planDetails->getId(),
                    'stripe_customer_id' => $customerId,
                    'stripe_plan_id' => $planPriceId,
                    'stripe_subscription_id' => $subscriptionId,
                    'default_payment_method' => $defaultPaymentMethod,
                    'default_source' => $defaultSource,
                    'paid_amount' => (string) $paidAmount,
                    'paid_amount_currency' => $paidCurrency,
                    'plan_interval' => $planInterval,
                    'plan_interval_count' => $planIntervalCount,
                    'customer_name' => $customerName,
                    'customer_email' => $customerEmail,
                    'plan_period_start' => $currentPeriodStart,
                    'plan_period_end' => $currentPeriodEnd,
                    'stripe_status' => 'succeeded',
                    'status' => 'active',
                    'expire_at' => null,
                ]);
            } else {
                $subRecordId = $this->subscriptionRepo->insertSubscription([
                    'user_id' => $userId,
                    'plan_id' => (string) $planDetails->getId(),
                    'stripe_customer_id' => $customerId,
                    'stripe_plan_id' => $planPriceId,
                    'stripe_subscription_id' => $subscriptionId,
                    'default_payment_method' => $defaultPaymentMethod,
                    'default_source' => $defaultSource,
                    'paid_amount' => (string) $paidAmount,
                    'paid_amount_currency' => $paidCurrency,
                    'plan_interval' => $planInterval,
                    'plan_interval_count' => $planIntervalCount,
                    'customer_name' => $customerName,
                    'customer_email' => $customerEmail,
                    'plan_period_start' => $currentPeriodStart,
                    'plan_period_end' => $currentPeriodEnd,
                    'stripe_status' => 'succeeded',
                    'status' => 'active',
                    'expire_at' => null,
                ]);
            }

            // 9. Synchronize Initial Invoice from Stripe
            if (!empty($subData['latest_invoice'])) {
                try {
                    $latestInvoice = $subData['latest_invoice'];
                    $latestInvoiceId = is_array($latestInvoice) ? ($latestInvoice['id'] ?? null) : $latestInvoice;
                    if (!empty($latestInvoiceId)) {
                        $invoiceObj = $this->stripeClient->retrieveInvoice((string) $latestInvoiceId);
                        if ($invoiceObj !== null) {
                            $this->subscriptionRepo->syncAccountInvoice($invoiceObj, $accountId, $userId);
                        }
                    }
                } catch (Throwable $invE) {
                    $this->logger->error('Failed to sync initial invoice in payment_insert: ' . $invE->getMessage());
                }
            }

            return $this->jsonResponse([
                'status' => true,
                'success' => 'Subscription created successfully!',
                'subscription_record_id' => $subRecordId,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Stripe API error in payment_insert: ' . $e->getMessage());
            return $this->jsonResponse(['status' => false, 'error' => 'Payment processing error: ' . $e->getMessage()]);
        }
    }

    private function jsonResponse(array $data): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'application/json');
        $response->getBody()->write((string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        return $response;
    }
}
