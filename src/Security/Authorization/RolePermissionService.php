<?php

declare(strict_types=1);

namespace App\Security\Authorization;

use App\Domain\User\User;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * RolePermissionService.
 * Manages RBAC feature permission inspection, validation, and updates,
 * reproducing common\services\RolePermissionService from Yii 2.
 */
final class RolePermissionService
{
    public function __construct(
        private ConnectionInterface $db,
        private RbacInterface $rbac,
    ) {
    }

    /**
     * Get permission state matrix for a target role.
     *
     * @return array<string, array{
     *     key: string,
     *     label: string,
     *     description: string,
     *     category: string,
     *     editable: bool,
     *     state: string,
     *     permissions: string[],
     *     isGranted: bool
     * }>
     */
    public function getRoleMatrix(string $roleName): array
    {
        $features = FeatureRegistry::getFeatures();

        // 1. Fetch direct permissions assigned to target role
        $directRows = (new Query($this->db))
            ->select(['child'])
            ->from('{{%auth_item_child}}')
            ->where(['parent' => $roleName])
            ->all();

        $directPermissions = [];
        $directChildRoles = [];
        foreach ($directRows as $row) {
            $child = (string) $row['child'];
            $directPermissions[$child] = true;
            // If child is a known system role, track it
            if (in_array($child, [User::ROLE_WORKER, User::ROLE_ACCOUNT_ADMIN, User::ROLE_ADMIN], true)) {
                $directChildRoles[] = $child;
            }
        }

        // 2. Fetch inherited permissions from child roles
        $inheritedPermissions = [];
        foreach ($directChildRoles as $childRole) {
            $childPerms = $this->rbac->getPermissionsForRole($childRole);
            foreach ($childPerms as $perm) {
                $inheritedPermissions[$perm] = true;
            }
        }

        $matrix = [];
        foreach ($features as $key => $feature) {
            $isRoleAllowed = in_array($roleName, $feature['allowed_roles'], true);
            $isEditable = FeatureRegistry::isFeatureEditableForRole($key, $roleName);

            $allAllowed = true;
            $hasDirectCoverage = false;
            $hasInheritedCoverage = false;

            foreach ($feature['permissions'] as $permName) {
                $isDirect = $this->isPermissionCovered($permName, $directPermissions);
                $isInherited = $this->isPermissionCovered($permName, $inheritedPermissions);

                if (!$isDirect && !$isInherited) {
                    $allAllowed = false;
                }
                if ($isDirect) {
                    $hasDirectCoverage = true;
                }
                if ($isInherited) {
                    $hasInheritedCoverage = true;
                }
            }

            if ($roleName === User::ROLE_SUPER) {
                $state = 'protected';
            } elseif (!$isRoleAllowed) {
                $state = 'restricted';
            } elseif (!$feature['editable']) {
                $state = 'protected';
            } elseif ($allAllowed) {
                $state = $hasDirectCoverage ? 'allowed' : 'inherited';
            } else {
                $state = 'denied';
            }

            $matrix[$key] = [
                'key'          => $key,
                'label'        => $feature['label'],
                'description'  => $feature['description'],
                'category'     => $feature['category'],
                'editable'     => $isEditable,
                'state'        => $state, // 'allowed', 'inherited', 'protected', 'restricted', 'denied'
                'permissions'  => $feature['permissions'],
                'isGranted'    => $allAllowed,
            ];
        }

        return $matrix;
    }

    /**
     * Checks if a required permission is covered by a set of granted permissions.
     *
     * Supports:
     * - Global root wildcard ('/*')
     * - Direct exact string match
     * - User administration aliases ('/user/*' covers '/user', '/users', '/user/*', '/users/*', '/user/switch', '/users/switch', etc.)
     * - Route wildcard prefix matching ('prefix/*' covers 'prefix' and any subpath under 'prefix/')
     * - Settings and social connections prefix coverage ('/settings/facebook' or '/facebook/*' covers '/settings/facebook/*', etc.)
     *
     * @param string $requiredPerm The permission string being tested.
     * @param array<string, bool> $grantedPermissions Map of granted permission names [perm => true].
     */
    public function isPermissionCovered(string $requiredPerm, array $grantedPermissions): bool
    {
        // 1. Global wildcard covers all permissions
        if (isset($grantedPermissions['/*'])) {
            return true;
        }

        // 2. Direct exact match
        if (isset($grantedPermissions[$requiredPerm])) {
            return true;
        }

        $cleanRequired = '/' . trim($requiredPerm, '/');

        // 3. User administration aliases: '/user/*' covers all /user/* and /users/* routes
        if (isset($grantedPermissions['/user/*'])) {
            if ($cleanRequired === '/user' || str_starts_with($cleanRequired, '/user/')
                || $cleanRequired === '/users' || str_starts_with($cleanRequired, '/users/')) {
                return true;
            }
        }

        // 4. Check if any granted permission is a wildcard prefix that covers $requiredPerm
        // E.g., granted '/reviews/*' covers '/reviews' or '/reviews/export'
        foreach ($grantedPermissions as $grantedPerm => $_) {
            if (str_ends_with($grantedPerm, '/*')) {
                $prefix = substr($grantedPerm, 0, -2);
                if ($cleanRequired === $prefix || str_starts_with($cleanRequired, $prefix . '/')) {
                    return true;
                }
            }
        }

        // 5. Settings / Social connections matching:
        // '/settings/facebook' covers '/settings/facebook/*' and vice-versa
        if (str_starts_with($cleanRequired, '/settings/facebook') && (isset($grantedPermissions['/settings/facebook']) || isset($grantedPermissions['/facebook/*']))) {
            return true;
        }
        if (str_starts_with($cleanRequired, '/settings/google') && (isset($grantedPermissions['/settings/google']) || isset($grantedPermissions['/google/*']))) {
            return true;
        }

        return false;
    }

    /**
     * Update role feature permissions.
     *
     * @param string[] $selectedFeatureKeys
     */
    public function updateRolePermissions(string $targetRoleName, array $selectedFeatureKeys, User $actorUser): array
    {
        // 1. Authorization check
        if (!$actorUser->isAdmin()) {
            return [
                'success' => false,
                'message' => 'Only authorized administrators can manage role permissions.',
            ];
        }

        // 2. Reject immutable Super role
        if ($targetRoleName === User::ROLE_SUPER) {
            return [
                'success' => false,
                'message' => 'The root Super Administrator role is system-protected and immutable.',
            ];
        }

        $features = FeatureRegistry::getFeatures();
        $selectedSet = array_flip($selectedFeatureKeys);
        $changesCount = 0;

        try {
            foreach ($features as $featureKey => $feature) {
                if (!FeatureRegistry::isFeatureEditableForRole($featureKey, $targetRoleName)) {
                    continue;
                }

                $shouldEnable = isset($selectedSet[$featureKey]);

                foreach ($feature['permissions'] as $permName) {
                    // Check if route exists in auth_item; insert if missing
                    $itemExists = (new Query($this->db))
                        ->from('{{%auth_item}}')
                        ->where(['name' => $permName])
                        ->exists();

                    if (!$itemExists) {
                        $this->db->createCommand()->insert('{{%auth_item}}', [
                            'name' => $permName,
                            'type' => 2,
                            'created_at' => time(),
                            'updated_at' => time(),
                        ])->execute();
                    }

                    // Check if parent-child link exists
                    $hasChild = (new Query($this->db))
                        ->from('{{%auth_item_child}}')
                        ->where(['parent' => $targetRoleName, 'child' => $permName])
                        ->exists();

                    if ($shouldEnable && !$hasChild) {
                        $this->db->createCommand()->insert('{{%auth_item_child}}', [
                            'parent' => $targetRoleName,
                            'child' => $permName,
                        ])->execute();
                        $changesCount++;
                    } elseif (!$shouldEnable && $hasChild) {
                        $this->db->createCommand()->delete('{{%auth_item_child}}', [
                            'parent' => $targetRoleName,
                            'child' => $permName,
                        ])->execute();
                        $changesCount++;
                    }
                }
            }

            return [
                'success' => true,
                'message' => "Role permissions for '{$targetRoleName}' updated successfully ({$changesCount} rules synchronized).",
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Failed to update permissions: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * System roles available for administration.
     *
     * @return array<string, string>
     */
    public static function getEditableRoleOptions(): array
    {
        return [
            User::ROLE_ACCOUNT_ADMIN => 'Account Administrator',
            User::ROLE_WORKER        => 'User (Worker)',
            User::ROLE_ADMIN         => 'Administrator',
        ];
    }
}
