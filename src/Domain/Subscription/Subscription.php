<?php

declare(strict_types=1);

namespace App\Domain\Subscription;

/**
 * Subscription Domain Entity.
 * Represents an active or past recurring Stripe subscription.
 * Mapped to database table 'subscriptions'.
 */
final class Subscription
{
    /**
     * @param int $id
     * @param int $userId
     * @param string|null $planId
     * @param string|null $stripeCustomerId
     * @param string|null $stripePlanId
     * @param string|null $stripeSubscriptionId
     * @param string|null $paidAmount
     * @param string|null $paidAmountCurrency
     * @param string|null $planInterval
     * @param string|null $customerName
     * @param string|null $customerEmail
     * @param string|null $planPeriodStart
     * @param string|null $planPeriodEnd
     * @param string|null $stripeStatus
     * @param string|null $status
     * @param int|null $createdAt
     * @param int|null $updatedAt
     */
    public function __construct(
        private int $id,
        private int $userId,
        private ?string $planId = null,
        private ?string $stripeCustomerId = null,
        private ?string $stripePlanId = null,
        private ?string $stripeSubscriptionId = null,
        private ?string $paidAmount = null,
        private ?string $paidAmountCurrency = null,
        private ?string $planInterval = null,
        private ?string $customerName = null,
        private ?string $customerEmail = null,
        private ?string $planPeriodStart = null,
        private ?string $planPeriodEnd = null,
        private ?string $stripeStatus = null,
        private ?string $status = null,
        private ?int $createdAt = null,
        private ?int $updatedAt = null,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            userId: (int) ($row['user_id'] ?? 0),
            planId: isset($row['plan_id']) ? (string) $row['plan_id'] : null,
            stripeCustomerId: isset($row['stripe_customer_id']) ? (string) $row['stripe_customer_id'] : null,
            stripePlanId: isset($row['stripe_plan_id']) ? (string) $row['stripe_plan_id'] : null,
            stripeSubscriptionId: isset($row['stripe_subscription_id']) ? (string) $row['stripe_subscription_id'] : null,
            paidAmount: isset($row['paid_amount']) ? (string) $row['paid_amount'] : null,
            paidAmountCurrency: isset($row['paid_amount_currency']) ? (string) $row['paid_amount_currency'] : 'usd',
            planInterval: isset($row['plan_interval']) ? (string) $row['plan_interval'] : 'month',
            customerName: isset($row['customer_name']) ? (string) $row['customer_name'] : null,
            customerEmail: isset($row['customer_email']) ? (string) $row['customer_email'] : null,
            planPeriodStart: isset($row['plan_period_start']) ? (string) $row['plan_period_start'] : null,
            planPeriodEnd: isset($row['plan_period_end']) ? (string) $row['plan_period_end'] : null,
            stripeStatus: isset($row['stripe_status']) ? (string) $row['stripe_status'] : null,
            status: isset($row['status']) ? (string) $row['status'] : 'active',
            createdAt: isset($row['created_at']) ? (int) $row['created_at'] : null,
            updatedAt: isset($row['updated_at']) ? (int) $row['updated_at'] : null,
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getPlanId(): ?string
    {
        return $this->planId;
    }

    public function getStripeCustomerId(): ?string
    {
        return $this->stripeCustomerId;
    }

    public function getStripeSubscriptionId(): ?string
    {
        return $this->stripeSubscriptionId;
    }

    public function getPaidAmount(): ?string
    {
        return $this->paidAmount;
    }

    public function getCustomerName(): ?string
    {
        return $this->customerName;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    public function getStripeStatus(): ?string
    {
        return $this->stripeStatus;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === 'active' || $this->stripeStatus === 'active';
    }

    public function getPlanPeriodEnd(): ?string
    {
        return $this->planPeriodEnd;
    }

    public function getCreatedAt(): ?int
    {
        return $this->createdAt;
    }
}
