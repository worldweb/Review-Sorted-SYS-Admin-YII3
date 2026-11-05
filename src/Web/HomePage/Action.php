<?php

declare(strict_types=1);

namespace App\Web\HomePage;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\Review\ReviewRepositoryInterface;
use App\Domain\SocialConnection\ServiceConfig;
use App\Domain\SocialConnection\SubService;
use App\Domain\SocialPost\SocialPost;
use App\Domain\SocialPost\SocialPostDestination;
use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use App\Environment;
use App\Security\TenantContextInterface;
use App\Security\TenantContextProviderInterface;
use Composer\InstalledVersions;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Operational Home/Dashboard Action (Priority 1).
 * Displays operational business KPIs, upcoming scheduled posts, failure alerts,
 * and quick actions scoped to the active tenant workspace.
 */
final readonly class Action
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ConnectionInterface $db,
        private TenantContextProviderInterface $tenantContextProvider,
        private ReviewRepositoryInterface $reviewRepo,
        private UserRepositoryInterface $userRepo,
        private AccountRepositoryInterface $accountRepo,
        private ResponseFactoryInterface $responseFactory,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $this->tenantContextProvider->get();
        if ($context->isGuest()) {
            return $this->responseFactory
                ->createResponse(302)
                ->withHeader('Location', Environment::url('/login'));
        }

        /** @var User|null $currentUser */
        $currentUser = $request->getAttribute('currentUser');
        if ($currentUser === null && $context->isAuthenticated()) {
            $currentUser = $this->userRepo->findById($context->getUserId(), $context);
        }

        // 1. Resolve Workspace Business Name
        $workspaceName = null;
        $accountId = $context->getAccountId();
        if ($accountId !== null && $accountId > 0) {
            $account = $this->accountRepo->findById($accountId, $context);
            $workspaceName = $account?->getBusinessName();
        }

        // 2. Technical Diagnostics Check (Read-only metadata)
        $dbConnected = false;
        $dbServerVersion = 'Unknown';
        $dbDatabaseName = 'Unknown';
        $dbDriverName = 'Unknown';
        $dbError = null;

        try {
            $this->db->open();
            $dbDriverName = $this->db->getDriverName();
            $result = $this->db->createCommand('SELECT VERSION() as version, DATABASE() as db_name')->queryOne();

            if ($result !== false && is_array($result)) {
                $dbConnected = true;
                $dbServerVersion = (string) ($result['version'] ?? 'Connected');
                $dbDatabaseName = (string) ($result['db_name'] ?? 'Connected');
            }
        } catch (Throwable $e) {
            $dbConnected = false;
            $dbError = $e->getMessage();
        }

        // 3. KPI A: Unread Reviews (scoped to tenant context)
        $metrics = $this->reviewRepo->getMetrics($context);
        $unreadReviews = (int) ($metrics['unread'] ?? 0);
        $totalReviews = (int) ($metrics['total'] ?? 0);

        $nowDate = date('Y-m-d H:i:s');

        // 4. KPI B: Scheduled Posts (unique parent posts with queued destinations scheduled_at > NOW())
        $scheduledCount = 0;
        try {
            $scheduledQuery = (new Query($this->db))
                ->from(['spd' => '{{%social_post_destination}}'])
                ->where(['spd.status' => SocialPostDestination::STATUS_QUEUED])
                ->andWhere(['>', 'spd.scheduled_at', $nowDate]);

            if (!$context->isUnrestricted()) {
                $accessible = $context->getAccessibleAccountIds();
                if (!empty($accessible)) {
                    $scheduledQuery->andWhere(['in', 'spd.account_id', $accessible]);
                    $scheduledCount = (int) $scheduledQuery->count('DISTINCT spd.social_post_id');
                }
            } else {
                $scheduledCount = (int) $scheduledQuery->count('DISTINCT spd.social_post_id');
            }
        } catch (Throwable) {
            $scheduledCount = 0;
        }

        // 5. KPI C: Publishing Failures (failed destinations requiring attention, excluding user cancellations)
        $totalFailed = 0;
        $ambiguousFailed = 0;
        $regularFailed = 0;
        try {
            $failureQuery = (new Query($this->db))
                ->select([
                    'total_failed' => new Expression('COUNT(*)'),
                    'ambiguous_failed' => new Expression("SUM(CASE WHEN error_message LIKE 'Ambiguous delivery:%' THEN 1 ELSE 0 END)"),
                    'regular_failed' => new Expression("SUM(CASE WHEN error_message NOT LIKE 'Ambiguous delivery:%' OR error_message IS NULL THEN 1 ELSE 0 END)"),
                ])
                ->from('{{%social_post_destination}}')
                ->where(['status' => SocialPostDestination::STATUS_FAILED])
                ->andWhere(['or', ['error_message' => null], ['!=', 'error_message', 'Cancelled by user']]);

            if (!$context->isUnrestricted()) {
                $accessible = $context->getAccessibleAccountIds();
                if (!empty($accessible)) {
                    $failureQuery->andWhere(['in', 'account_id', $accessible]);
                    $failedStats = $failureQuery->one() ?: [];
                    $totalFailed = (int) ($failedStats['total_failed'] ?? 0);
                    $ambiguousFailed = (int) ($failedStats['ambiguous_failed'] ?? 0);
                    $regularFailed = (int) ($failedStats['regular_failed'] ?? 0);
                }
            } else {
                $failedStats = $failureQuery->one() ?: [];
                $totalFailed = (int) ($failedStats['total_failed'] ?? 0);
                $ambiguousFailed = (int) ($failedStats['ambiguous_failed'] ?? 0);
                $regularFailed = (int) ($failedStats['regular_failed'] ?? 0);
            }
        } catch (Throwable) {
            $totalFailed = 0;
            $ambiguousFailed = 0;
            $regularFailed = 0;
        }

        // 6. KPI D: Active Social Channels (linked Facebook Pages, Instagram Accounts, Google Locations)
        $facebookCount = 0;
        $googleCount = 0;
        $instagramCount = 0;
        try {
            // Facebook Pages (service_config service_id = 2 and linked = 1)
            $fbQuery = (new Query($this->db))
                ->from('{{%service_config}}')
                ->where(['service_id' => ServiceConfig::SERVICE_FACEBOOK, 'linked' => ServiceConfig::LINKED_YES]);

            // Google Locations (service_config service_id = 1 and linked = 1)
            $googleQuery = (new Query($this->db))
                ->from('{{%service_config}}')
                ->where(['service_id' => ServiceConfig::SERVICE_GOOGLE, 'linked' => ServiceConfig::LINKED_YES]);

            // Instagram Accounts (sub_service where parent service_config is linked)
            $igQuery = (new Query($this->db))
                ->from(['sub' => '{{%sub_service}}'])
                ->innerJoin(['sc' => '{{%service_config}}'], 'sc.id = sub.service_config_id')
                ->where(['sc.linked' => ServiceConfig::LINKED_YES, 'sub.type' => SubService::TYPE_INSTAGRAM]);

            if (!$context->isUnrestricted()) {
                $accessible = $context->getAccessibleAccountIds();
                if (!empty($accessible)) {
                    $fbQuery->andWhere(['in', 'account_id', $accessible]);
                    $googleQuery->andWhere(['in', 'account_id', $accessible]);
                    $igQuery->andWhere(['in', 'sub.account_id', $accessible]);
                    $facebookCount = (int) $fbQuery->count();
                    $googleCount = (int) $googleQuery->count();
                    $instagramCount = (int) $igQuery->count();
                }
            } else {
                $facebookCount = (int) $fbQuery->count();
                $googleCount = (int) $googleQuery->count();
                $instagramCount = (int) $igQuery->count();
            }
        } catch (Throwable) {
            $facebookCount = 0;
            $googleCount = 0;
            $instagramCount = 0;
        }

        $activeChannelsCount = $facebookCount + $googleCount + $instagramCount;

        // 7. Upcoming Scheduled Posts (next 5 posts chronologically, scheduled_at > NOW())
        $upcomingPosts = [];
        try {
            $postIdsQuery = (new Query($this->db))
                ->select([
                    'social_post_id',
                    'next_scheduled' => new Expression('MIN(scheduled_at)'),
                ])
                ->from('{{%social_post_destination}}')
                ->where(['status' => SocialPostDestination::STATUS_QUEUED])
                ->andWhere(['>', 'scheduled_at', $nowDate]);

            $hasAccess = true;
            if (!$context->isUnrestricted()) {
                $accessible = $context->getAccessibleAccountIds();
                if (empty($accessible)) {
                    $hasAccess = false;
                } else {
                    $postIdsQuery->andWhere(['in', 'account_id', $accessible]);
                }
            }

            if ($hasAccess) {
                $postRows = $postIdsQuery
                    ->groupBy(['social_post_id'])
                    ->orderBy(['next_scheduled' => SORT_ASC, 'social_post_id' => SORT_ASC])
                    ->limit(5)
                    ->all();

                if (!empty($postRows)) {
                    $postIds = array_map(static fn (array $r) => (int) $r['social_post_id'], $postRows);

                    $postDetails = (new Query($this->db))
                        ->select([
                            'sp.*',
                            'author_email' => 'u.email',
                            'account_business_name' => 'a.business_name',
                        ])
                        ->from(['sp' => '{{%social_post}}'])
                        ->leftJoin(['u' => '{{%user}}'], 'u.id = sp.user_id')
                        ->leftJoin(['a' => '{{%account}}'], 'a.id = sp.account_id')
                        ->where(['in', 'sp.id', $postIds])
                        ->all();

                    $postsById = [];
                    foreach ($postDetails as $pRow) {
                        $postsById[(int) $pRow['id']] = SocialPost::fromDatabaseRow($pRow);
                    }

                    $destRows = (new Query($this->db))
                        ->select([
                            'spd.*',
                            'destination_name' => 'COALESCE(sc.label, sub.name, spd.platform)',
                        ])
                        ->from(['spd' => '{{%social_post_destination}}'])
                        ->leftJoin(['sc' => '{{%service_config}}'], 'sc.id = spd.service_config_id')
                        ->leftJoin(['sub' => '{{%sub_service}}'], 'sub.id = spd.sub_service_id')
                        ->where(['in', 'spd.social_post_id', $postIds])
                        ->orderBy(['spd.id' => SORT_ASC])
                        ->all();

                    $destinationsByPost = [];
                    foreach ($destRows as $dRow) {
                        $dest = SocialPostDestination::fromDatabaseRow($dRow);
                        $destinationsByPost[$dest->getSocialPostId()][] = $dest;
                    }

                    foreach ($postIds as $id) {
                        if (isset($postsById[$id])) {
                            $post = $postsById[$id];
                            $postDests = $destinationsByPost[$id] ?? [];
                            $upcomingPosts[] = $post->withDestinations($postDests);
                        }
                    }
                }
            }
        } catch (Throwable) {
            $upcomingPosts = [];
        }

        $frameworkVersion = 'Yii 3 (yiisoft/app 1.4.0, di: '
            . (InstalledVersions::getPrettyVersion('yiisoft/di') ?? '1.4.x')
            . ', runner: '
            . (InstalledVersions::getPrettyVersion('yiisoft/yii-runner-http') ?? '3.2.x')
            . ')';

        return $this->viewRenderer->render(__DIR__ . '/template', [
            'appName' => 'Reviews Sorted',
            'frameworkVersion' => $frameworkVersion,
            'environment' => Environment::appEnv(),
            'debug' => Environment::appDebug(),
            'phpVersion' => PHP_VERSION,
            'phpSapi' => PHP_SAPI,
            'dbConnected' => $dbConnected,
            'dbDriverName' => $dbDriverName,
            'dbServerVersion' => $dbServerVersion,
            'dbDatabaseName' => $dbDatabaseName,
            'dbError' => $dbError,
            'currentUser' => $currentUser,
            'tenantContext' => $context,
            'workspaceName' => $workspaceName,
            'kpi' => [
                'unreadReviews' => $unreadReviews,
                'totalReviews' => $totalReviews,
                'scheduledPostsCount' => $scheduledCount,
                'totalFailed' => $totalFailed,
                'ambiguousFailed' => $ambiguousFailed,
                'regularFailed' => $regularFailed,
                'activeChannelsCount' => $activeChannelsCount,
                'facebookChannelsCount' => $facebookCount,
                'googleChannelsCount' => $googleCount,
                'instagramChannelsCount' => $instagramCount,
            ],
            'upcomingPosts' => $upcomingPosts,
            'failureAlert' => [
                'hasFailures' => $totalFailed > 0,
                'totalFailed' => $totalFailed,
                'ambiguousFailed' => $ambiguousFailed,
                'regularFailed' => $regularFailed,
                'failedQueueUrl' => Environment::url('/social-post?status=failed'),
            ],
        ]);
    }
}
