<?php

declare(strict_types=1);

namespace App\Web\Shared\Navigation;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use App\Security\Authorization\RbacInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * NavigationMenuProvider.
 * Dynamically computes application sidebar navigation items, nested submenus,
 * unread badges, and switchable workspaces strictly enforcing Yii 2 RBAC rules.
 */
final class NavigationMenuProvider
{
    public function __construct(
        private RbacInterface $rbac,
        private UserRepositoryInterface $userRepo,
        private ?AccountRepositoryInterface $accountRepo = null,
        private ?ConnectionInterface $db = null,
    ) {
    }

    /**
     * @return array<int, array{type?: string, label: string, url?: string, icon?: string, active?: bool, badge?: string|int}>
     */
    public function getMenuItems(User $currentUser, string $currentPath): array
    {
        $userId = $currentUser->getId();
        $isAdmin = $currentUser->isAdmin();
        $isAccountAdmin = $currentUser->isAccountAdmin();

        $items = [];

        // 1. Dashboard (Home) - Preserved per user requirement
        $items[] = [
            'type' => 'item',
            'label' => 'Dashboard',
            'url' => '/sys-yii3/',
            'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>',
            'active' => $currentPath === '/sys-yii3' || $currentPath === '/sys-yii3/' || $currentPath === '/',
        ];

        // 2. Reviews & Mentions with dynamic unread count
        $unreadReviews = 0;
        if ($this->db !== null) {
            try {
                $q = (new Query($this->db))
                    ->from('{{%review}}')
                    ->where(['!=', 'read', 1]);
                if (!$isAdmin && $currentUser->getAccountId() !== null) {
                    $q->andWhere(['account_id' => $currentUser->getAccountId()]);
                }
                $unreadReviews = (int) $q->count();
            } catch (\Throwable) {
                $unreadReviews = 0;
            }
        }

        $items[] = [
            'type' => 'item',
            'label' => "Reviews & Mentions ($unreadReviews)",
            'url' => '/sys-yii3/reviews',
            'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>',
            'active' => str_starts_with($currentPath, '/sys-yii3/reviews'),
        ];

        // 3. Social Connections
        if ($isAdmin || $this->rbac->can($userId, '/settings/facebook') || $this->rbac->can($userId, '/facebook/*')) {
            $isSocialActive = str_starts_with($currentPath, '/sys-yii3/settings') || str_starts_with($currentPath, '/sys-yii3/facebook') || str_starts_with($currentPath, '/sys-yii3/google');
            $items[] = [
                'type' => 'item',
                'label' => 'Social Connections',
                'url' => '/sys-yii3/settings/facebook',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>',
                'active' => $isSocialActive,
            ];

            if ($isSocialActive) {
                $isFbActive = (str_contains($currentPath, '/facebook') || str_contains($currentPath, '/settings/facebook')) && !str_contains($currentPath, 'facebook-api');
                $isFbApiActive = str_contains($currentPath, 'facebook-api');
                $isGoogleActive = (str_contains($currentPath, '/google') || str_contains($currentPath, '/settings/google')) && !str_contains($currentPath, 'google-api');
                $isGoogleApiActive = str_contains($currentPath, 'google-api');

                $items[] = ['type' => 'header', 'label' => 'FACEBOOK'];
                $items[] = [
                    'type' => 'subitem',
                    'label' => 'Facebook Pages',
                    'url' => '/sys-yii3/settings/facebook',
                    'active' => $isFbActive,
                ];
                if ($isAdmin || $currentUser->getRoleName() === 'super') {
                    $items[] = [
                        'type' => 'subitem',
                        'label' => 'Facebook Apps',
                        'url' => '/sys-yii3/settings/facebook-api',
                        'active' => $isFbApiActive,
                    ];
                }
                $items[] = ['type' => 'divider'];
                $items[] = ['type' => 'header', 'label' => 'GOOGLE'];
                $items[] = [
                    'type' => 'subitem',
                    'label' => 'Google Accounts',
                    'url' => '/sys-yii3/settings/google',
                    'active' => $isGoogleActive,
                ];
                if ($isAdmin || $currentUser->getRoleName() === 'super') {
                    $items[] = [
                        'type' => 'subitem',
                        'label' => 'Google Projects',
                        'url' => '/sys-yii3/settings/google-api',
                        'active' => $isGoogleApiActive,
                    ];
                }
            }
        }

        // 4. Social Posting
        if ($isAdmin || $this->rbac->can($userId, '/social-post/*')) {
            $isSocialPostActive = str_starts_with($currentPath, '/sys-yii3/social-post');
            $items[] = [
                'type' => 'item',
                'label' => 'Social Posting',
                'url' => '/sys-yii3/social-post/create',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19l7-7 3 3-7 7-3-3z"></path><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"></path><path d="M2 2l7.586 7.586"></path><circle cx="11" cy="11" r="2"></circle></svg>',
                'active' => $isSocialPostActive,
            ];

            if ($isSocialPostActive) {
                $isCreate = str_contains($currentPath, '/social-post/create');
                $isHistory = !$isCreate;

                $items[] = ['type' => 'header', 'label' => 'SOCIAL PUBLISHING'];
                $items[] = [
                    'type' => 'subitem',
                    'label' => 'Create Post',
                    'url' => '/sys-yii3/social-post/create',
                    'active' => $isCreate,
                ];
                $items[] = [
                    'type' => 'subitem',
                    'label' => 'Publishing History',
                    'url' => '/sys-yii3/social-post',
                    'active' => $isHistory,
                ];
            }
        }

        // 5. E-mails
        if ($isAdmin || $this->rbac->can($userId, '/email/*')) {
            $isEmailActive = str_starts_with($currentPath, '/sys-yii3/email');
            $items[] = [
                'type' => 'item',
                'label' => 'E-mails',
                'url' => '/sys-yii3/email/index',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>',
                'active' => $isEmailActive,
            ];

            if ($isEmailActive) {
                $isSend = str_contains($currentPath, '/email/send');
                $isQueue = str_contains($currentPath, '/email/queue');
                $isImporter = str_contains($currentPath, '/email/importer');
                $isTpl = !$isSend && !$isQueue && !$isImporter;

                $items[] = ['type' => 'header', 'label' => 'COMMUNICATION'];
                $items[] = [
                    'type' => 'subitem',
                    'label' => 'Email Templates',
                    'url' => '/sys-yii3/email/index',
                    'active' => $isTpl,
                ];
                $items[] = [
                    'type' => 'subitem',
                    'label' => 'Send Campaign',
                    'url' => '/sys-yii3/email/send',
                    'active' => $isSend,
                ];
                $items[] = [
                    'type' => 'subitem',
                    'label' => 'Delivery Queue',
                    'url' => '/sys-yii3/email/queue',
                    'active' => $isQueue,
                ];
                $items[] = ['type' => 'divider'];
                $items[] = ['type' => 'header', 'label' => 'DATA & LOGS'];
                $items[] = [
                    'type' => 'subitem',
                    'label' => 'Import Contacts',
                    'url' => '/sys-yii3/email/importer',
                    'active' => $isImporter,
                ];
            }
        }

        // 6. WhatsApp Messages
        if ($isAdmin || $this->rbac->can($userId, '/whatsapp/*')) {
            $isWhatsappActive = str_starts_with($currentPath, '/sys-yii3/whatsapp');
            $items[] = [
                'type' => 'item',
                'label' => 'WhatsApp Messages',
                'url' => '/sys-yii3/whatsapp/index',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>',
                'active' => $isWhatsappActive,
            ];

            if ($isWhatsappActive) {
                $isSend = str_contains($currentPath, '/whatsapp/send');
                $isHistory = str_contains($currentPath, '/whatsapp/history');
                $isImporter = str_contains($currentPath, '/whatsapp/importer');
                $isTpl = !$isSend && !$isHistory && !$isImporter;

                $items[] = ['type' => 'header', 'label' => 'WHATSAPP MESSAGING'];
                $items[] = [
                    'type' => 'subitem',
                    'label' => 'Templates',
                    'url' => '/sys-yii3/whatsapp/index',
                    'active' => $isTpl,
                ];
                $items[] = [
                    'type' => 'subitem',
                    'label' => 'Send Campaign',
                    'url' => '/sys-yii3/whatsapp/send',
                    'active' => $isSend,
                ];
                $items[] = [
                    'type' => 'subitem',
                    'label' => 'Message History',
                    'url' => '/sys-yii3/whatsapp/history',
                    'active' => $isHistory,
                ];
                $items[] = ['type' => 'divider'];
                $items[] = ['type' => 'header', 'label' => 'DATA & LOGS'];
                $items[] = [
                    'type' => 'subitem',
                    'label' => 'Import Contacts',
                    'url' => '/sys-yii3/whatsapp/importer',
                    'active' => $isImporter,
                ];
            }
        }

        // 7. QR Code
        $hasQrAccess = false;
        if ($isAdmin) {
            $hasQrAccess = true;
        } elseif ($currentUser->getAccountId() !== null && $this->accountRepo !== null) {
            $acc = $this->accountRepo->findById($currentUser->getAccountId());
            if ($acc !== null && $acc->isQrCodeEnabled()) {
                $hasQrAccess = true;
            }
        } elseif ($this->rbac->can($userId, '/qrcode/*')) {
            $hasQrAccess = true;
        }

        if ($hasQrAccess) {
            $items[] = [
                'type' => 'item',
                'label' => 'QR Code',
                'url' => '/sys-yii3/qrcode/index',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect><line x1="14" y1="14" x2="14" y2="14"></line><line x1="17" y1="14" x2="17" y2="14"></line><line x1="20" y1="14" x2="20" y2="14"></line><line x1="14" y1="17" x2="14" y2="17"></line><line x1="14" y1="20" x2="14" y2="20"></line></svg>',
                'active' => str_starts_with($currentPath, '/sys-yii3/qrcode') || str_starts_with($currentPath, '/qrcode'),
            ];
        }

        // 8. User Management
        if ($isAdmin || $isAccountAdmin || $this->rbac->can($userId, '/user/*')) {
            $isUserActive = str_starts_with($currentPath, '/sys-yii3/user') || str_starts_with($currentPath, '/sys-yii3/users');
            $items[] = [
                'type' => 'item',
                'label' => 'User Management',
                'url' => '/sys-yii3/users',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
                'active' => $isUserActive,
            ];
        }

        // 9. Accounts (Restored original label 'Accounts')
        if ($isAdmin || $isAccountAdmin || $this->rbac->can($userId, '/accounts/*')) {
            $isAccountActive = str_starts_with($currentPath, '/sys-yii3/accounts');
            $items[] = [
                'type' => 'item',
                'label' => 'Accounts',
                'url' => '/sys-yii3/accounts',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>',
                'active' => $isAccountActive,
            ];
        }

        // 10. Location Groups
        if ($isAdmin || $isAccountAdmin || $this->rbac->can($userId, '/location-group/*')) {
            $items[] = [
                'type' => 'item',
                'label' => 'Location Groups',
                'url' => '/sys-yii3/location-group/index',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>',
                'active' => str_starts_with($currentPath, '/sys-yii3/location-group') || str_starts_with($currentPath, '/location-group'),
            ];
        }

        // 11. Subscriptions (Platform Admins only)
        if ($isAdmin) {
            $items[] = [
                'type' => 'item',
                'label' => 'Subscriptions',
                'url' => '/sys-yii3/subscription/index',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>',
                'active' => str_starts_with($currentPath, '/sys-yii3/subscription'),
            ];
        }

        return $items;
    }

    /**
     * Retrieves switchable workspace accounts for multi-account users.
     *
     * @return array<int, string>
     */
    public function getSwitchableAccounts(int $userId): array
    {
        return $this->userRepo->findSwitchableAccounts($userId);
    }
}
