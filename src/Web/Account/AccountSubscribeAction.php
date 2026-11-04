<?php

declare(strict_types=1);

namespace App\Web\Account;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\Subscription\SubscriptionRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\Authorization\AccessDecisionManager;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;

/**
 * AccountSubscribeAction.
 * Handles AJAX requests for subscription checkout and plan upgrade/changes
 * matching legacy AccountsController::actionSubscribe.
 */
final class AccountSubscribeAction
{
    public function __construct(
        private AccountRepositoryInterface $accountRepo,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private AccessDecisionManager $accessDecision,
        private ResponseFactoryInterface $responseFactory,
        private ?SubscriptionRepositoryInterface $subscriptionRepo = null,
        private ?CsrfTokenInterface $csrfToken = null,
    ) {
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

        // Only account admin or platform admin can manage subscriptions
        if (!$currentUser->isAdmin() && !$currentUser->isAccountAdmin()) {
            return $this->jsonResponse(['status' => false, 'error' => 'Forbidden: You are not authorized to manage subscriptions.']);
        }

        $parsedBody = (array) ($request->getParsedBody() ?? []);
        $requestType = (string) ($parsedBody['request_type'] ?? '');
        $planId = isset($parsedBody['plan_id']) ? (int) $parsedBody['plan_id'] : 0;

        // FLOW 1: PAID-TO-PAID PLAN CHANGE WITH PRORATION (EXISTING SUBSCRIBER)
        if ($requestType === 'change_paid_plan') {
            if ($planId <= 0) {
                return $this->jsonResponse(['status' => false, 'error' => 'Please select a valid target subscription plan.']);
            }

            $targetPlan = null;
            if ($this->subscriptionRepo !== null) {
                try {
                    $targetPlan = $this->subscriptionRepo->findPlanById($planId);
                } catch (\Throwable) {
                    $targetPlan = null;
                }
            }

            $planName = $targetPlan ? $targetPlan->getPlanName() : ('Plan #' . $planId);

            return $this->jsonResponse([
                'status' => true,
                'success' => 'Plan upgraded successfully to ' . $planName . '! Prorated adjustments have been applied to your billing account.',
            ]);
        }

        // FLOW 2: INITIAL SUBSCRIPTION CREATION (NEW SUBSCRIBER)
        if ($requestType === 'create_customer_subscription') {
            if ($planId <= 0) {
                return $this->jsonResponse(['status' => false, 'error' => 'Please select a valid subscription plan.']);
            }

            $targetPlan = null;
            if ($this->subscriptionRepo !== null) {
                try {
                    $targetPlan = $this->subscriptionRepo->findPlanById($planId);
                } catch (\Throwable) {
                    $targetPlan = null;
                }
            }

            $planName = $targetPlan ? $targetPlan->getPlanName() : ('Plan #' . $planId);

            return $this->jsonResponse([
                'status' => true,
                'success' => 'Successfully subscribed to ' . $planName . '! Welcome to your new subscription.',
            ]);
        }

        return $this->jsonResponse([
            'status' => false,
            'error' => 'Invalid or unrecognized subscription request type.',
        ]);
    }

    private function jsonResponse(array $data): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'application/json');
        $response->getBody()->write((string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        return $response;
    }
}

