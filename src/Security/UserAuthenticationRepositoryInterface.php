<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Interface for User Authentication Repository.
 * Dedicated, restricted repository used exclusively by the authentication layer.
 */
interface UserAuthenticationRepositoryInterface
{
    /**
     * Look up an authentication identity by primary login email.
     */
    public function findAuthIdentityByEmail(string $email): ?UserAuthIdentity;

    /**
     * Look up an authentication identity by user primary key ID (e.g. for session restoration).
     */
    public function findAuthIdentityById(int $id): ?UserAuthIdentity;

    /**
     * Look up an authentication identity by authentication / remember-me key.
     */
    public function findAuthIdentityByAuthKey(string $authKey): ?UserAuthIdentity;
}
