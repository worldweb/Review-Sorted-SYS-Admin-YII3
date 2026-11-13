<?php

declare(strict_types=1);

namespace App\Security\Authorization;

use App\Domain\User\User;
use App\Security\UserAuthIdentity;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * Database RBAC Manager.
 * Reads Yii 2 database RBAC schema (auth_item, auth_item_child, auth_assignment)
 * with complete support for role inheritance and route wildcard matching.
 */
final class DatabaseRbacManager implements RbacInterface
{
    /** @var array<string, string[]>|null In-memory cache of role -> child items */
    private ?array $hierarchyCache = null;

    /** @var array<int, string[]> In-memory cache of userId -> assigned roles */
    private array $userRolesCache = [];

    public function __construct(
        private ConnectionInterface $db,
    ) {
    }

    public function can(User|UserAuthIdentity|int $user, string $permission): bool
    {
        $userId = $this->resolveUserId($user);
        if ($userId <= 0) {
            return false;
        }

        $roles = $this->getRolesForUser($userId);
        if (empty($roles)) {
            return false;
        }

        $candidates = $this->getCandidatePermissions($permission);

        foreach ($roles as $role) {
            $granted = $this->getPermissionsForRole($role);

            // Check if any candidate pattern is granted to this role
            foreach ($candidates as $candidate) {
                if (in_array($candidate, $granted, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Generates all candidate permission patterns that could authorize the target path.
     * Ordered from most specific to least specific.
     *
     * @return string[]
     */
    public function getCandidatePermissions(string $path): array
    {
        $clean = '/' . trim($path, '/');
        if ($clean === '/' || $clean === '') {
            return ['/', '/*'];
        }

        $candidates = [$clean, '/' . ltrim($path, '/')];

        // Route aliases for known plural/singular pairs
        if ($clean === '/users' || str_starts_with($clean, '/users/')) {
            $candidates[] = '/user/*';
            $candidates[] = '/user';
        } elseif ($clean === '/user' || str_starts_with($clean, '/user/')) {
            $candidates[] = '/user/*';
            $candidates[] = '/users';
        }

        // Route aliases for settings entry point
        if ($clean === '/settings' || $clean === '/settings/index') {
            $candidates[] = '/settings/facebook';
            $candidates[] = '/settings/facebook/*';
            $candidates[] = '/settings/google';
            $candidates[] = '/settings/google/*';
        }

        // Direct directory wildcard candidate (e.g. /reviews -> /reviews/*)
        $candidates[] = $clean . '/*';

        // Multi-level hierarchical prefix matching
        // e.g. for /settings/facebook/create:
        // -> /settings/facebook/*, /settings/facebook
        // -> /settings/*, /settings
        $segments = explode('/', trim($clean, '/'));
        for ($i = count($segments) - 1; $i >= 1; $i--) {
            $prefix = '/' . implode('/', array_slice($segments, 0, $i));
            $candidates[] = $prefix . '/*';
            $candidates[] = $prefix;
        }

        // Platform super wildcard
        $candidates[] = '/*';

        return array_values(array_unique($candidates));
    }

    public function hasRole(User|UserAuthIdentity|int $user, string $role): bool
    {
        $userId = $this->resolveUserId($user);
        if ($userId <= 0) {
            return false;
        }

        $assignedRoles = $this->getRolesForUser($userId);
        foreach ($assignedRoles as $assignedRole) {
            if ($assignedRole === $role) {
                return true;
            }

            // Check if assigned role inherits target role (e.g., super inherits admin, account_admin inherits worker)
            $allRoleItems = $this->getPermissionsForRole($assignedRole);
            if (in_array($role, $allRoleItems, true)) {
                return true;
            }
        }

        return false;
    }

    public function getPermissionsForRole(string $role): array
    {
        $this->ensureHierarchyLoaded();

        $visited = [];
        return $this->traverseHierarchy($role, $visited);
    }

    public function getRolesForUser(int $userId): array
    {
        if (isset($this->userRolesCache[$userId])) {
            return $this->userRolesCache[$userId];
        }

        $rows = (new Query($this->db))
            ->select(['item_name'])
            ->from('{{%auth_assignment}}')
            ->where(['user_id' => (string) $userId])
            ->all();

        $roles = [];
        foreach ($rows as $row) {
            if (isset($row['item_name']) && is_string($row['item_name'])) {
                $roles[] = $row['item_name'];
            }
        }

        $this->userRolesCache[$userId] = $roles;
        return $roles;
    }

    private function resolveUserId(User|UserAuthIdentity|int $user): int
    {
        if (is_int($user)) {
            return $user;
        }
        return $user->getId();
    }

    private function ensureHierarchyLoaded(): void
    {
        if ($this->hierarchyCache !== null) {
            return;
        }

        $rows = (new Query($this->db))
            ->select(['parent', 'child'])
            ->from('{{%auth_item_child}}')
            ->all();

        $graph = [];
        foreach ($rows as $row) {
            $parent = (string) $row['parent'];
            $child = (string) $row['child'];
            $graph[$parent][] = $child;
        }

        $this->hierarchyCache = $graph;
    }

    /**
     * @param array<string, bool> $visited
     * @return string[]
     */
    private function traverseHierarchy(string $item, array &$visited): array
    {
        if (isset($visited[$item])) {
            return [];
        }
        $visited[$item] = true;

        $results = [$item];
        if (isset($this->hierarchyCache[$item])) {
            foreach ($this->hierarchyCache[$item] as $child) {
                $childResults = $this->traverseHierarchy($child, $visited);
                $results = array_merge($results, $childResults);
            }
        }

        return array_unique($results);
    }
}
