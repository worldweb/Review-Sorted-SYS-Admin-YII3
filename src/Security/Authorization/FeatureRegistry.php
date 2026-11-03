<?php

declare(strict_types=1);

namespace App\Security\Authorization;

use App\Domain\User\User;

/**
 * FeatureRegistry.
 * Provides an evidence-based central registry mapping verified business features to RBAC route permissions,
 * exactly reproducing common\rbac\FeatureRegistry from Yii 2.
 */
final class FeatureRegistry
{
    /**
     * Returns all verified business features and their underlying RBAC route permissions mapping.
     *
     * @return array<string, array{
     *     key: string,
     *     label: string,
     *     description: string,
     *     category: string,
     *     permissions: string[],
     *     allowed_roles: string[],
     *     editable: bool
     * }>
     */
    public static function getFeatures(): array
    {
        return [
            'reviews' => [
                'key'           => 'reviews',
                'label'         => 'Reviews & Mentions',
                'description'   => 'View, filter, export, and reply to customer reviews.',
                'category'      => 'Core Features',
                'permissions'   => ['/reviews/*'],
                'allowed_roles' => [User::ROLE_ADMIN, User::ROLE_ACCOUNT_ADMIN, User::ROLE_WORKER],
                'editable'      => true,
            ],
            'social_posting' => [
                'key'           => 'social_posting',
                'label'         => 'Social Media Posting',
                'description'   => 'Create, schedule, edit, cancel, and publish social posts.',
                'category'      => 'Outreach & Marketing',
                'permissions'   => ['/social-post/*'],
                'allowed_roles' => [User::ROLE_ADMIN, User::ROLE_ACCOUNT_ADMIN],
                'editable'      => true,
            ],
            'whatsapp' => [
                'key'           => 'whatsapp',
                'label'         => 'WhatsApp Messaging',
                'description'   => 'Manage WhatsApp templates, contact imports, and dispatch message queues.',
                'category'      => 'Outreach & Marketing',
                'permissions'   => ['/whatsapp/*'],
                'allowed_roles' => [User::ROLE_ADMIN, User::ROLE_ACCOUNT_ADMIN],
                'editable'      => true,
            ],
            'email' => [
                'key'           => 'email',
                'label'         => 'Email Campaigns',
                'description'   => 'Manage email templates, list imports, and outbound email dispatch.',
                'category'      => 'Outreach & Marketing',
                'permissions'   => ['/email/*', '/email-importer/*', '/email-send/*'],
                'allowed_roles' => [User::ROLE_ADMIN, User::ROLE_ACCOUNT_ADMIN],
                'editable'      => true,
            ],
            'qrcode' => [
                'key'           => 'qrcode',
                'label'         => 'QR Code Generator',
                'description'   => 'Create, download, and track scan activity for branded QR codes.',
                'category'      => 'Tools & Integrations',
                'permissions'   => ['/qrcode/*'],
                'allowed_roles' => [User::ROLE_ADMIN, User::ROLE_ACCOUNT_ADMIN],
                'editable'      => true,
            ],
            'user_management' => [
                'key'           => 'user_management',
                'label'         => 'Account User Administration',
                'description'   => 'Create, manage, and switch sub-users and workers within business accounts.',
                'category'      => 'Account Management',
                'permissions'   => ['/user/*', '/users/switch', '/user/switch'],
                'allowed_roles' => [User::ROLE_ADMIN, User::ROLE_ACCOUNT_ADMIN],
                'editable'      => true,
            ],
            'account_management' => [
                'key'           => 'account_management',
                'label'         => 'Account & Store Management',
                'description'   => 'Manage business account profile, account settings, and store locations.',
                'category'      => 'Account Management',
                'permissions'   => ['/accounts/*'],
                'allowed_roles' => [User::ROLE_ADMIN, User::ROLE_ACCOUNT_ADMIN],
                'editable'      => true,
            ],
            'social_connections' => [
                'key'           => 'social_connections',
                'label'         => 'Social & Platform Connections',
                'description'   => 'Connect and link Facebook Pages and Google Business Profile locations.',
                'category'      => 'Tools & Integrations',
                'permissions'   => [
                    '/settings/facebook', '/facebook/*',
                    '/settings/google', '/google/*',
                ],
                'allowed_roles' => [User::ROLE_ADMIN, User::ROLE_ACCOUNT_ADMIN],
                'editable'      => true,
            ],
            'global_settings' => [
                'key'           => 'global_settings',
                'label'         => 'Administrator Global Settings',
                'description'   => 'Configure workspace branding, system security, and role permissions.',
                'category'      => 'System Administration',
                'permissions'   => ['/global-settings/*'],
                'allowed_roles' => [User::ROLE_ADMIN],
                'editable'      => false, // SYSTEM PROTECTED
            ],
        ];
    }

    /**
     * Get a specific feature by key.
     *
     * @return array{key: string, label: string, description: string, category: string, permissions: string[], allowed_roles: string[], editable: bool}|null
     */
    public static function getFeature(string $key): ?array
    {
        $features = self::getFeatures();
        return $features[$key] ?? null;
    }

    /**
     * Check if a feature is editable for a target role.
     */
    public static function isFeatureEditableForRole(string $featureKey, string $roleName): bool
    {
        $feature = self::getFeature($featureKey);
        if ($feature === null || empty($feature['editable'])) {
            return false;
        }

        if ($roleName === User::ROLE_SUPER) {
            return false; // Super role is immutable
        }

        return in_array($roleName, $feature['allowed_roles'], true);
    }
}
