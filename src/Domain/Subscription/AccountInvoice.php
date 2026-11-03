<?php

declare(strict_types=1);

namespace App\Domain\Subscription;

/**
 * AccountInvoice Domain Entity.
 * Represents an invoice issued via Stripe Billing.
 * Mapped to database table 'account_invoice'.
 */
final class AccountInvoice
{
    /**
     * @param int $id
     * @param int $accountId
     * @param int $userId
     * @param string|null $stripeCustomerId
     * @param string|null $stripeSubscriptionId
     * @param string $stripeInvoiceId
     * @param string|null $invoiceNumber
     * @param float $amountPaid
     * @param int $amountCents
     * @param string $currency
     * @param string $status
     * @param string|null $billingReason
     * @param string|null $invoicePdfUrl
     * @param string|null $hostedInvoiceUrl
     * @param int|null $periodStart
     * @param int|null $periodEnd
     * @param int|null $paidAt
     * @param int $createdAt
     * @param int $updatedAt
     */
    public function __construct(
        private int $id,
        private int $accountId,
        private int $userId,
        private ?string $stripeCustomerId,
        private ?string $stripeSubscriptionId,
        private string $stripeInvoiceId,
        private ?string $invoiceNumber,
        private float $amountPaid,
        private int $amountCents,
        private string $currency = 'usd',
        private string $status = 'paid',
        private ?string $billingReason = null,
        private ?string $invoicePdfUrl = null,
        private ?string $hostedInvoiceUrl = null,
        private ?int $periodStart = null,
        private ?int $periodEnd = null,
        private ?int $paidAt = null,
        private int $createdAt = 0,
        private int $updatedAt = 0,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            accountId: (int) ($row['account_id'] ?? 0),
            userId: (int) ($row['user_id'] ?? 0),
            stripeCustomerId: isset($row['stripe_customer_id']) ? (string) $row['stripe_customer_id'] : null,
            stripeSubscriptionId: isset($row['stripe_subscription_id']) ? (string) $row['stripe_subscription_id'] : null,
            stripeInvoiceId: (string) ($row['stripe_invoice_id'] ?? ''),
            invoiceNumber: isset($row['invoice_number']) ? (string) $row['invoice_number'] : null,
            amountPaid: (float) ($row['amount_paid'] ?? 0.0),
            amountCents: (int) ($row['amount_cents'] ?? 0),
            currency: (string) ($row['currency'] ?? 'usd'),
            status: (string) ($row['status'] ?? 'paid'),
            billingReason: isset($row['billing_reason']) ? (string) $row['billing_reason'] : null,
            invoicePdfUrl: isset($row['invoice_pdf_url']) ? (string) $row['invoice_pdf_url'] : null,
            hostedInvoiceUrl: isset($row['hosted_invoice_url']) ? (string) $row['hosted_invoice_url'] : null,
            periodStart: isset($row['period_start']) ? (int) $row['period_start'] : null,
            periodEnd: isset($row['period_end']) ? (int) $row['period_end'] : null,
            paidAt: isset($row['paid_at']) ? (int) $row['paid_at'] : null,
            createdAt: (int) ($row['created_at'] ?? 0),
            updatedAt: (int) ($row['updated_at'] ?? 0),
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getStripeInvoiceId(): string
    {
        return $this->stripeInvoiceId;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function getAmountPaid(): float
    {
        return $this->amountPaid;
    }

    public function getFormattedAmount(): string
    {
        return '$' . number_format($this->amountPaid, 2);
    }

    public function getCurrency(): string
    {
        return strtoupper($this->currency);
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getHostedInvoiceUrl(): ?string
    {
        return $this->hostedInvoiceUrl;
    }

    public function getInvoicePdfUrl(): ?string
    {
        return $this->invoicePdfUrl;
    }

    public function getPaidAt(): ?int
    {
        return $this->paidAt;
    }
}
