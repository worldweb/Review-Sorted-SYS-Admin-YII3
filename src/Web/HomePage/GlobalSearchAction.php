<?php

declare(strict_types=1);

namespace App\Web\HomePage;

use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * Global Search endpoint for Command Palette modal.
 */
final class GlobalSearchAction
{
    public function __construct(
        private ConnectionInterface $db,
        private TenantContextProviderInterface $tenantContextProvider,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $this->tenantContextProvider->get();
        $response = $this->responseFactory->createResponse(200)->withHeader('Content-Type', 'application/json');

        if ($context->isGuest()) {
            $response = $response->withStatus(401);
            $response->getBody()->write((string) json_encode(['success' => false, 'error' => 'Authentication required']));
            return $response;
        }

        $params = $request->getQueryParams();
        $queryStr = trim((string) ($params['q'] ?? ''));

        if (mb_strlen($queryStr) === 0) {
            $response->getBody()->write((string) json_encode([
                'success' => true,
                'query' => '',
                'total' => 0,
                'categories' => [],
            ]));
            return $response;
        }

        if (mb_strlen($queryStr) > 100) {
            $queryStr = mb_substr($queryStr, 0, 100);
        }

        $categories = [];
        $total = 0;
        $isUnrestricted = $context->isUnrestricted();
        $accountId = $context->getAccountId();

        // 1. Navigation items
        $navMatches = $this->searchNavigation($queryStr, $isUnrestricted);
        if (!empty($navMatches)) {
            $categories[] = [
                'name' => 'Navigation & Settings',
                'key' => 'navigation',
                'icon' => 'compass',
                'items' => $navMatches,
            ];
            $total += count($navMatches);
        }

        // 2. Accounts / Workspaces
        try {
            $accQuery = (new Query($this->db))
                ->select(['id', 'business_name', 'email'])
                ->from('{{%account}}')
                ->where(['like', 'business_name', $queryStr]);

            if (!$isUnrestricted && $accountId !== null) {
                $accQuery->andWhere(['id' => $accountId]);
            }

            $accRows = $accQuery->limit(5)->all();
            if (!empty($accRows)) {
                $accItems = [];
                foreach ($accRows as $r) {
                    $accItems[] = [
                        'title' => (string) ($r['business_name'] ?? 'Account #' . $r['id']),
                        'subtitle' => 'Workspace / Account',
                        'url' => \App\Environment::url('/accounts/view?id=' . $r['id']),
                    ];
                }
                $categories[] = [
                    'name' => 'Workspaces & Accounts',
                    'key' => 'accounts',
                    'icon' => 'building',
                    'items' => $accItems,
                ];
                $total += count($accItems);
            }
        } catch (\Throwable) {
        }

        // 3. Users
        try {
            $userQuery = (new Query($this->db))
                ->select(['id', 'name', 'email'])
                ->from('{{%user}}')
                ->where(['or', ['like', 'name', $queryStr], ['like', 'email', $queryStr]]);

            if (!$isUnrestricted && $accountId !== null) {
                $userQuery->andWhere(['account_id' => $accountId]);
            }

            $userRows = $userQuery->limit(5)->all();
            if (!empty($userRows)) {
                $userItems = [];
                foreach ($userRows as $u) {
                    $userItems[] = [
                        'title' => (string) ($u['name'] ?? 'User #' . $u['id']),
                        'subtitle' => (string) ($u['email'] ?? 'User'),
                        'url' => \App\Environment::url('/user/view?id=' . $u['id']),
                    ];
                }
                $categories[] = [
                    'name' => 'Users',
                    'key' => 'users',
                    'icon' => 'users',
                    'items' => $userItems,
                ];
                $total += count($userItems);
            }
        } catch (\Throwable) {
        }

        // 4. Reviews
        try {
            $reviewQuery = (new Query($this->db))
                ->select(['id', 'customer_name', 'review_text'])
                ->from('{{%review}}')
                ->where(['or', ['like', 'customer_name', $queryStr], ['like', 'review_text', $queryStr]]);

            if (!$isUnrestricted && $accountId !== null) {
                $reviewQuery->andWhere(['account_id' => $accountId]);
            }

            $revRows = $reviewQuery->limit(5)->all();
            if (!empty($revRows)) {
                $revItems = [];
                foreach ($revRows as $rv) {
                    $snippet = mb_strimwidth((string) ($rv['review_text'] ?? ''), 0, 50, '...');
                    $revItems[] = [
                        'title' => (string) ($rv['customer_name'] ?? 'Customer Review'),
                        'subtitle' => $snippet ?: 'Customer Feedback',
                        'url' => \App\Environment::url('/reviews/view?id=' . $rv['id']),
                    ];
                }
                $categories[] = [
                    'name' => 'Reviews',
                    'key' => 'reviews',
                    'icon' => 'message-square',
                    'items' => $revItems,
                ];
                $total += count($revItems);
            }
        } catch (\Throwable) {
        }

        $response->getBody()->write((string) json_encode([
            'success' => true,
            'query' => $queryStr,
            'total' => $total,
            'categories' => $categories,
        ]));

        return $response;
    }

    private function searchNavigation(string $q, bool $isAdmin): array
    {
        $allNav = [
            ['title' => 'Dashboard', 'subtitle' => 'Application overview and KPIs', 'url' => \App\Environment::url('/')],
            ['title' => 'Reviews & Mentions', 'subtitle' => 'Customer feedback and ratings', 'url' => \App\Environment::url('/reviews')],
            ['title' => 'Social Connections', 'subtitle' => 'Facebook and Google accounts', 'url' => \App\Environment::url('/settings/facebook')],
            ['title' => 'Social Posting', 'subtitle' => 'Compose and schedule multi-channel posts', 'url' => \App\Environment::url('/social-post/create')],
            ['title' => 'E-mails', 'subtitle' => 'Email templates and campaign dispatch', 'url' => \App\Environment::url('/email/index')],
            ['title' => 'WhatsApp Messages', 'subtitle' => 'WhatsApp templates and campaigns', 'url' => \App\Environment::url('/whatsapp/index')],
            ['title' => 'QR Code', 'subtitle' => 'QR Code studio, customization, and landing pages', 'url' => \App\Environment::url('/qrcode/index')],
            ['title' => 'User Management', 'subtitle' => 'Manage administrators and workspace users', 'url' => \App\Environment::url('/users')],
            ['title' => 'Accounts', 'subtitle' => 'Workspace settings and seat allocations', 'url' => \App\Environment::url('/accounts')],
            ['title' => 'Location Groups', 'subtitle' => 'Group social destinations for posting', 'url' => \App\Environment::url('/location-group/index')],
        ];

        if ($isAdmin) {
            $allNav[] = ['title' => 'Subscriptions', 'subtitle' => 'Platform subscription plans and tiers', 'url' => \App\Environment::url('/subscription/index')];
            $allNav[] = ['title' => 'Global Settings', 'subtitle' => 'Platform configuration and migration', 'url' => \App\Environment::url('/global-settings')];
        }

        $matches = [];
        $lowerQ = mb_strtolower($q);
        foreach ($allNav as $item) {
            if (str_contains(mb_strtolower($item['title']), $lowerQ) || str_contains(mb_strtolower($item['subtitle']), $lowerQ)) {
                $matches[] = $item;
            }
        }

        return array_slice($matches, 0, 5);
    }
}
