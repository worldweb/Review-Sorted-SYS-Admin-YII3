<?php

declare(strict_types=1);

namespace App\Domain\User;

/**
 * User Save Data DTO.
 * Encapsulates input parameters for creating or updating a user.
 */
final class UserSaveData
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $password = null,
        public readonly ?string $passwordConfirm = null,
        public readonly string $role = User::ROLE_WORKER,
        public readonly int $status = User::STATUS_ACTIVE,
        public readonly ?int $accountId = null,
        public readonly ?int $ownerId = null,
        public readonly array $extraAccounts = [],
    ) {
    }

    public static function fromArray(array $data, string $defaultRole = User::ROLE_WORKER, ?int $defaultAccountId = null): self
    {
        $name = isset($data['name']) && is_string($data['name']) ? trim($data['name']) : '';
        $email = isset($data['email']) && is_string($data['email']) ? trim($data['email']) : '';
        $password = isset($data['password']) && is_string($data['password']) && $data['password'] !== '' ? $data['password'] : null;
        $passwordConfirm = isset($data['password_conf']) && is_string($data['password_conf']) ? $data['password_conf'] : null;

        $role = isset($data['newRole']) && is_string($data['newRole']) && $data['newRole'] !== ''
            ? trim($data['newRole'])
            : $defaultRole;

        $status = isset($data['status']) && $data['status'] !== ''
            ? (int) $data['status']
            : User::STATUS_ACTIVE;

        $accountId = isset($data['account_id']) && $data['account_id'] !== ''
            ? (int) $data['account_id']
            : $defaultAccountId;

        $extraAccounts = isset($data['extra_accounts']) && is_array($data['extra_accounts'])
            ? array_map('intval', $data['extra_accounts'])
            : [];

        return new self(
            name: $name,
            email: $email,
            password: $password,
            passwordConfirm: $passwordConfirm,
            role: $role,
            status: $status,
            accountId: $accountId,
            extraAccounts: $extraAccounts,
        );
    }
}
