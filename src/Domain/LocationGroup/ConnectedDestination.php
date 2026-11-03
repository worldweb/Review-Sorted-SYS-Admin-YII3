<?php

declare(strict_types=1);

namespace App\Domain\LocationGroup;

/**
 * Value Object representing a connected social publishing or review destination.
 * Maps to Google Business Profile locations, Facebook Pages, or Instagram Business accounts.
 */
final class ConnectedDestination
{
    public const PLATFORM_GOOGLE = 'google';
    public const PLATFORM_FACEBOOK = 'facebook';
    public const PLATFORM_INSTAGRAM = 'instagram';

    public function __construct(
        private string $key,
        private int $configId,
        private ?int $subId,
        private string $label,
        private string $platform,
        private int $accountId,
        private ?string $accountName = null,
        private ?string $pageName = null,
        private bool $isLinked = true,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getConfigId(): int
    {
        return $this->configId;
    }

    public function getSubId(): ?int
    {
        return $this->subId;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function getAccountName(): ?string
    {
        return $this->accountName;
    }

    public function getPageName(): ?string
    {
        return $this->pageName;
    }

    public function isLinked(): bool
    {
        return $this->isLinked;
    }

    /**
     * @return array<string, mixed> Array representation matching legacy actionGetDestinations JSON
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'config_id' => $this->configId,
            'sub_id' => $this->subId,
            'label' => $this->label,
            'platform' => $this->platform,
            'account_id' => $this->accountId,
            'account_name' => $this->accountName ?? '',
            'page_name' => $this->pageName ?? '',
        ];
    }
}
