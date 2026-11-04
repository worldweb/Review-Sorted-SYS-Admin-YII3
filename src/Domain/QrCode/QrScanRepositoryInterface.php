<?php

declare(strict_types=1);

namespace App\Domain\QrCode;

use App\Security\TenantContextInterface;

/**
 * QR Code Scan Analytics Repository Interface.
 * Defines read operations for scan metrics and device history with strict tenant scoping.
 */
interface QrScanRepositoryInterface
{
    /**
     * Retrieves the total count of scans recorded for a given QR code.
     * Enforces TenantContext boundary via QR code ownership.
     */
    public function getTotalScans(int $qrCodeId, ?TenantContextInterface $context = null): int;

    /**
     * Retrieves the count of scans recorded today (within current calendar day) for a given QR code.
     * Enforces TenantContext boundary via QR code ownership.
     */
    public function getTodayScans(
        int $qrCodeId,
        ?\DateTimeZone $timeZone = null,
        ?TenantContextInterface $context = null
    ): int;

    /**
     * Retrieves the UNIX timestamp of the most recent scan recorded for a given QR code.
     * Enforces TenantContext boundary via QR code ownership.
     */
    public function getLastScanAt(int $qrCodeId, ?TenantContextInterface $context = null): ?int;

    /**
     * Retrieves an aggregated scan analytics summary (totalScans, todayScans, lastScanAt) in one call.
     *
     * @return array{totalScans: int, todayScans: int, lastScanAt: ?int}
     */
    public function getAnalyticsSummary(
        int $qrCodeId,
        ?\DateTimeZone $timeZone = null,
        ?TenantContextInterface $context = null
    ): array;

    /**
     * Retrieves paginated scan event records for a given QR code.
     * Enforces TenantContext boundary via QR code ownership.
     *
     * @return array{items: QrScan[], total: int}
     */
    public function getScansByQrCodeId(
        int $qrCodeId,
        int $page = 1,
        int $pageSize = 20,
        ?TenantContextInterface $context = null
    ): array;

    /**
     * Records a physical QR code scan event with client IP and timestamp.
     */
    public function recordScan(int $qrCodeId, string $userIp): bool;
}
