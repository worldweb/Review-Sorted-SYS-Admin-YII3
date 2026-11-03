<?php

declare(strict_types=1);

namespace App\Security\Authorization;

use App\Domain\User\User;
use App\Security\TenantContextInterface;

/**
 * Access Decision Manager.
 * Combines RBAC permission graph resolution with TenantContext isolation rules.
 * All access control checks are enforced server-side.
 */
final class AccessDecisionManager
{
    public function __construct(
        private RbacInterface $rbac,
    ) {
    }

    /**
     * Determines whether the current user in TenantContext can view a target user.
     */
    public function canViewUser(TenantContextInterface $context, User $targetUser): bool
    {
        if ($context->isGuest()) {
            return false;
        }

        $currentUserId = $context->getUserId();

        // Users can always view their own profile
        if ($currentUserId === $targetUser->getId()) {
            return true;
        }

        // Platform administrators can view any user
        if ($context->isUnrestricted() || $this->rbac->hasRole($currentUserId, User::ROLE_ADMIN)) {
            return true;
        }

        // Account administrators can only view users belonging to their own tenant account
        if ($context->isAccountAdmin()) {
            $contextAccountId = $context->getAccountId();
            return $contextAccountId !== null && $contextAccountId === $targetUser->getAccountId();
        }

        // Standard workers cannot view other users' profiles
        return false;
    }

    /**
     * Determines whether the current user can edit a target user.
     */
    public function canEditUser(TenantContextInterface $context, User $targetUser): bool
    {
        if ($context->isGuest()) {
            return false;
        }

        $currentUserId = $context->getUserId();

        // Super admin cannot be edited by non-super users
        if ($targetUser->isSuperAdmin() && !$context->isSuperAdmin()) {
            return false;
        }

        // Self-editing is allowed (profile update)
        if ($currentUserId === $targetUser->getId()) {
            return true;
        }

        // Platform administrators can edit users
        if ($context->isUnrestricted() || $this->rbac->hasRole($currentUserId, User::ROLE_ADMIN)) {
            return true;
        }

        // Account administrators can only edit users within their account (excluding administrators)
        if ($context->isAccountAdmin()) {
            $contextAccountId = $context->getAccountId();
            return $contextAccountId !== null &&
                   $contextAccountId === $targetUser->getAccountId() &&
                   !$targetUser->isAdmin();
        }

        return false;
    }

    /**
     * Determines whether the current user can create a user with a specific target role.
     */
    public function canCreateUserRole(TenantContextInterface $context, string $targetRole): bool
    {
        if ($context->isGuest()) {
            return false;
        }

        $currentUserId = $context->getUserId();

        // Only platform admins can create administrators
        if ($targetRole === User::ROLE_ADMIN || $targetRole === User::ROLE_SUPER) {
            return $this->rbac->hasRole($currentUserId, User::ROLE_ADMIN);
        }

        // Platform admins and Account admins can create Account Administrators
        if ($targetRole === User::ROLE_ACCOUNT_ADMIN) {
            return $this->rbac->hasRole($currentUserId, User::ROLE_ADMIN);
        }

        // Account admins and platform admins can create standard workers
        if ($targetRole === User::ROLE_WORKER) {
            return $context->isAccountAdmin() || $this->rbac->hasRole($currentUserId, User::ROLE_ADMIN);
        }

        return false;
    }

    /**
     * Determines whether the current user can delete a target user.
     */
    public function canDeleteUser(TenantContextInterface $context, User $targetUser): bool
    {
        if ($context->isGuest()) {
            return false;
        }

        // Cannot delete super admin (reproducing Yii 2 User::beforeDelete)
        if ($targetUser->isSuperAdmin()) {
            return false;
        }

        // Cannot delete self
        if ($context->getUserId() === $targetUser->getId()) {
            return false;
        }

        // Platform administrators can delete users
        if ($context->isUnrestricted() || $this->rbac->hasRole($context->getUserId(), User::ROLE_ADMIN)) {
            return true;
        }

        // Account administrators can delete workers in their own account
        if ($context->isAccountAdmin()) {
            $contextAccountId = $context->getAccountId();
            return $contextAccountId !== null &&
                   $contextAccountId === $targetUser->getAccountId() &&
                   $targetUser->isWorker();
        }

        return false;
    }

    /**
     * Determines whether the current user can access a specific tenant workspace/account.
     */
    public function canAccessAccount(TenantContextInterface $context, int $targetAccountId): bool
    {
        if ($context->isGuest()) {
            return false;
        }

        // Platform administrators have unrestricted access
        if ($context->isUnrestricted() || $this->rbac->hasRole($context->getUserId(), User::ROLE_ADMIN)) {
            return true;
        }

        // Tenant users can only access their assigned account
        return $context->canAccessAccount($targetAccountId);
    }

    /**
     * Determines whether the current user can switch to an account.
     * Enforces the multi-account sub-user ownership linkage discovered in Yii 2 UserController::actionSwitch.
     */
    public function canSwitchToAccount(TenantContextInterface $context, int $targetAccountId): bool
    {
        if ($context->isGuest()) {
            return false;
        }

        // Platform administrators can access any account
        if ($context->isUnrestricted() || $this->rbac->hasRole($context->getUserId(), User::ROLE_ADMIN)) {
            return true;
        }

        // Must possess the /user/switch permission or be an account administrator
        return $context->isAccountAdmin() 
            || $this->rbac->can($context->getUserId(), '/user/switch')
            || $this->rbac->can($context->getUserId(), '/users/switch')
            || $this->rbac->can($context->getUserId(), '/user/*');
    }

    public function getRbac(): RbacInterface
    {
        return $this->rbac;
    }
}
