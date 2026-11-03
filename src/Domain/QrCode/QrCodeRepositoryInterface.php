<?php

declare(strict_types=1);

namespace App\Domain\QrCode;

use App\Security\TenantContextInterface;

/**
 * QR Code Repository Interface.
 * Defines read operations for QR code configurations with strict tenant isolation.
 */
interface QrCodeRepositoryInterface
{
    /**
     * Finds a single QR code by its ID.
     * Enforces TenantContext boundary via creator user's account ownership.
     */
    public function findById(int $id, ?TenantContextInterface $context = null): ?QrCode;

    /**
     * Executes a paginated, filtered search of QR codes enforcing TenantContext.
     *
     * @return array{items: QrCode[], total: int}
     */
    public function search(
        ?string $search = null,
        int $page = 1,
        int $pageSize = 20,
        ?TenantContextInterface $context = null
    ): array;

    /**
     * Counts total QR codes matching the search filter and tenant boundary.
     */
    public function count(?string $search = null, ?TenantContextInterface $context = null): int;

    /**
     * Retrieves all QR codes belonging to a specific account ID.
     * Enforces TenantContext boundary.
     *
     * @return QrCode[]
     */
    public function findByAccountId(int $accountId, ?TenantContextInterface $context = null): array;
}
