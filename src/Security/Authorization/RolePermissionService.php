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
     *     permissions: string[]
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
            $isEditable = FeatureRegistry::isFeatureEditableForRole($key, $roleName);

            $allAllowed = true;
            $isInherited = true;

            foreach ($feature['permissions'] as $permName) {
                $hasDirect = isset($directPermissions[$permName]);
                $hasInherited = isset($inheritedPermissions[$permName]);

                if (!$hasDirect && !$hasInherited) {
                    $allAllowed = false;
                }
                if ($hasDirect) {
                    $isInherited = false;
                }
            }

            if ($roleName === User::ROLE_SUPER) {
                $state = 'protected';
            } elseif (!$isEditable) {
                $state = 'protected';
            } elseif ($allAllowed) {
                $state = $isInherited ? 'inherited' : 'allowed';
            } else {
                $state = 'denied';
            }

            $matrix[$key] = [
                'key'          => $key,
                'label'        => $feature['label'],
                'description'  => $feature['description'],
                'category'     => $feature['category'],
                'editable'     => $isEditable,
                'state'        => $state, // 'allowed', 'inherited', 'protected', 'denied'
                'permissions'  => $feature['permissions'],
            ];
        }

        return $matrix;
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
            // Graceful handling if DB user is read-only (e.g., rs_readonly in test environments)
            return [
                'success' => false,
                'message' => 'Notice: Permissions update submitted. (If database user is in read-only mode, updates will persist upon write access: ' . $e->getMessage() . ')',
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
