<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * Account Save Data DTO.
 * Encapsulates input parameters for creating or updating an account.
 */
final class AccountSaveData
{
    public function __construct(
        public readonly string $businessName,
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly ?string $email = null,
        public readonly ?string $contactNumber = null,
        public readonly ?string $website = null,
        public readonly int $maxUsers = 5,
        public readonly int $status = Account::STATUS_ACTIVE,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $businessName = isset($data['business_name']) && is_string($data['business_name']) ? trim($data['business_name']) : '';
        $firstName = isset($data['first_name']) && is_string($data['first_name']) ? trim($data['first_name']) : null;
        $lastName = isset($data['last_name']) && is_string($data['last_name']) ? trim($data['last_name']) : null;
        $email = isset($data['email']) && is_string($data['email']) ? trim($data['email']) : null;
        $contactNumber = isset($data['contact_number']) && is_string($data['contact_number']) ? trim($data['contact_number']) : null;
        $website = isset($data['website']) && is_string($data['website']) ? trim($data['website']) : null;
        $maxUsers = isset($data['max_users']) && $data['max_users'] !== '' ? max(1, (int) $data['max_users']) : 5;
        $status = isset($data['status']) && $data['status'] !== '' ? (int) $data['status'] : Account::STATUS_ACTIVE;

        return new self(
            businessName: $businessName,
            firstName: $firstName,
            lastName: $lastName,
            email: $email,
            contactNumber: $contactNumber,
            website: $website,
            maxUsers: $maxUsers,
            status: $status,
        );
    }
}
