<?php

declare(strict_types=1);

namespace App\Domain\Review;

use App\Domain\Review\Channel\FacebookReplyClient;
use App\Domain\Review\Channel\FacebookReplyClientInterface;
use App\Domain\Review\Channel\GoogleReplyClient;
use App\Domain\Review\Channel\GoogleReplyClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * ChannelReplyDispatcher.
 * Dispatches review replies to external platforms (Google Business Profile, Facebook Graph API).
 * Replicates legacy Yii 2 GoogleResolver, FacebookResolver, and ReviewHelper::replyReview.
 */
final class ChannelReplyDispatcher implements ChannelReplyDispatcherInterface
{
    public const SERVICE_GOOGLE = 1;
    public const SERVICE_FACEBOOK = 2;

    private GoogleReplyClientInterface $googleClient;
    private FacebookReplyClientInterface $facebookClient;
    private LoggerInterface $logger;

    public function __construct(
        private ConnectionInterface $db,
        ?GoogleReplyClientInterface $googleClient = null,
        ?FacebookReplyClientInterface $facebookClient = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->googleClient = $googleClient ?? new GoogleReplyClient(null, $this->logger);
        $this->facebookClient = $facebookClient ?? new FacebookReplyClient(null, $this->logger);
    }

    public function dispatch(Review $review, string $message): ReplyDispatchResult
    {
        $message = trim($message);
        if ($message === '') {
            return ReplyDispatchResult::failure('Reply message cannot be empty.');
        }

        // Restrictions matching legacy Yii 2
        if ($review->getType() === Review::TYPE_MENTION) {
            return ReplyDispatchResult::failure('Mentions cannot be replied to via automated API.');
        }

        if (!$review->isServiceReady()) {
            return ReplyDispatchResult::failure('Connected service channel is not ready or missing authentication credentials.');
        }

        // Fetch channel and API credentials from database
        $configRow = (new Query($this->db))
            ->select([
                'sc.id',
                'sc.service_id',
                'sc.api_id',
                'sc.account_id',
                'sc.page_token',
                'sc.page_name',
                'sc.page_id',
                'service_label' => 's.label',
                'service_class' => 's.class',
                'api_client_json' => 'a.client_json',
                'api_token' => 'a.token',
                'api_key' => 'a.api_key',
                'api_client_id' => 'a.client_id',
            ])
            ->from(['sc' => '{{%service_config}}'])
            ->innerJoin(['s' => '{{%service}}'], 's.id = sc.service_id')
            ->leftJoin(['a' => '{{%api}}'], 'a.id = sc.api_id')
            ->where(['sc.id' => $review->getServiceConfigId()])
            ->one();

        if ($configRow === false || $configRow === null) {
            return ReplyDispatchResult::failure('Service configuration not found.');
        }

        $serviceId = (int) ($configRow['service_id'] ?? 0);
        $serviceLabel = strtolower((string) ($configRow['service_label'] ?? ''));

        // Check if plugin based
        if ($configRow['service_class'] === 'plugin' || str_contains($serviceLabel, 'plugin')) {
            return ReplyDispatchResult::failure('Plugin-based service channels do not support automated external replies.');
        }

        // Fallback for Google: resolve client_json via account_api if sc.api_id was not set
        if (($serviceId === self::SERVICE_GOOGLE || str_contains($serviceLabel, 'google')) && empty($configRow['api_client_json'])) {
            $accountId = isset($configRow['account_id']) ? (int) $configRow['account_id'] : 0;
            if ($accountId > 0) {
                $accountApiRow = (new Query($this->db))
                    ->select(['a.client_json'])
                    ->from(['aa' => '{{%account_api}}'])
                    ->innerJoin(['a' => '{{%api}}'], 'a.id = aa.api_id')
                    ->where(['aa.account_id' => $accountId, 'a.service_id' => self::SERVICE_GOOGLE])
                    ->one();

                if ($accountApiRow !== false && $accountApiRow !== null && !empty($accountApiRow['client_json'])) {
                    $configRow['api_client_json'] = $accountApiRow['client_json'];
                }
            }
        }

        // Dispatch to Google MyBusiness
        if ($serviceId === self::SERVICE_GOOGLE || str_contains($serviceLabel, 'google')) {
            return $this->googleClient->reply($review, $message, $configRow);
        }

        // Dispatch to Facebook Graph API
        if ($serviceId === self::SERVICE_FACEBOOK || str_contains($serviceLabel, 'facebook')) {
            return $this->facebookClient->reply($review, $message, $configRow);
        }

        return ReplyDispatchResult::failure("Replying is not supported for channel '{$configRow['service_label']}'.");
    }
}
