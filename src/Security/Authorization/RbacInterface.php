<?php

declare(strict_types=1);

namespace App\Security\Authorization;

use App\Domain\User\User;
use App\Security\UserAuthIdentity;

/**
 * RBAC Contract.
 * Provides hierarchical role resolution and route/permission checking.
 */
interface RbacInterface
{
    /**
     * Checks if a user has a specific permission or route authorization.
     */
    public function can(User|UserAuthIdentity|int $user, string $permission): bool;

    /**
     * Checks if a user possesses a specific role (directly or via inheritance).
     */
    public function hasRole(User|UserAuthIdentity|int $user, string $role): bool;

    /**
     * Returns all permissions granted to a role, including inherited children.
     *
     * @return string[]
     */
    public function getPermissionsForRole(string $role): array;

    /**
     * Returns all assigned role names for a user ID.
     *
     * @return string[]
     */
    public function getRolesForUser(int $userId): array;
}
