<?php

declare(strict_types=1);

namespace App\Console;

use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\Setting\SettingRepositoryInterface;
use App\Domain\Subscription\SubscriptionRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(
    name: 'subscription/expire',
    description: 'Deactivates expired subscriptions and accounts after grace period',
)]
final class SubscriptionExpireCommand extends Command
{
    public function __construct(
        private ConnectionInterface $db,
        private SubscriptionRepositoryInterface $subscriptionRepo,
        private AccountRepositoryInterface $accountRepo,
        private SettingRepositoryInterface $settingRepo,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $today = date('Y-m-d H:i:s');
        $output->writeln("[SubscriptionExpire] Running subscription expiration check at {$today}");

        $graceDays = $this->settingRepo->getInt('subscription_grace_period_days', 3);
        $graceCutoff = date('Y-m-d H:i:s', strtotime("-{$graceDays} days"));

        $output->writeln("[SubscriptionExpire] Grace period: {$graceDays} days (cutoff: {$graceCutoff})");

        try {
            $distinctUserIds = $this->subscriptionRepo->findDistinctSubscribedUserIds();
        } catch (Throwable $e) {
            $output->writeln('<error>[SubscriptionExpire] Error retrieving subscribed users: ' . $e->getMessage() . '</error>');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $expiredCount = 0;

        foreach ($distinctUserIds as $userId) {
            try {
                $sub = $this->subscriptionRepo->findLatestSubscriptionByUserId($userId);
                if ($sub === null) {
                    continue;
                }

                $periodEnd = $sub->getPlanPeriodEnd();
                if ($periodEnd !== null && $periodEnd !== '' && $periodEnd < $graceCutoff && $sub->getStatus() === 'active') {
                    $this->subscriptionRepo->updateSubscription($sub->getId(), [
                        'status' => 'inactive',
                        'stripe_status' => 'past_due',
                        'expire_at' => $today,
                    ]);

                    // Resolve account ID
                    $userRow = $this->db->createCommand('SELECT account_id FROM {{%user}} WHERE id = :id', [':id' => $userId])->queryOne();
                    if ($userRow && !empty($userRow['account_id'])) {
                        $accountId = (int) $userRow['account_id'];
                        $this->accountRepo->updateSubscriptionEntitlements($accountId, 0, false);
                        $output->writeln("<info>[SubscriptionExpire] Deactivated expired account #{$accountId} (User #{$userId}, Sub #{$sub->getId()})</info>");
                    }

                    $expiredCount++;
                }
            } catch (Throwable $e) {
                $output->writeln('<error>[SubscriptionExpire] Error processing user #' . $userId . ': ' . $e->getMessage() . '</error>');
            }
        }

        $output->writeln("[SubscriptionExpire] Processed completed. Deactivated {$expiredCount} expired subscriptions.");
        return ExitCode::OK;
    }
}

