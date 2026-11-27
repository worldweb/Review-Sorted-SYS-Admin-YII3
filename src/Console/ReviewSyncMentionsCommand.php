<?php

declare(strict_types=1);

namespace App\Console;

use App\Domain\Review\Sync\SocialMentionSyncService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(
    name: 'review/sync-mentions',
    description: 'Synchronizes Facebook Page mentions and Instagram mentions',
)]
final class ReviewSyncMentionsCommand extends Command
{
    public function __construct(
        private SocialMentionSyncService $mentionSyncService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('platform', 'p', InputOption::VALUE_OPTIONAL, 'Platform filter: all, facebook, or instagram', 'all')
            ->addOption('channel-id', 'c', InputOption::VALUE_OPTIONAL, 'Filter by ServiceConfig ID (Facebook) or SubService ID (Instagram)')
            ->addOption('account-id', 'a', InputOption::VALUE_OPTIONAL, 'Filter by specific Account ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $platform = strtolower(trim((string) $input->getOption('platform')));
        $channelId = $input->getOption('channel-id') !== null ? (int) $input->getOption('channel-id') : null;
        $accountId = $input->getOption('account-id') !== null ? (int) $input->getOption('account-id') : null;

        if (!in_array($platform, ['all', 'facebook', 'instagram'], true)) {
            $output->writeln('<error>[MentionsSync Error] Invalid --platform value. Allowed values: all, facebook, instagram.</error>');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if ($channelId !== null && $platform === 'all') {
            $output->writeln('<error>[MentionsSync Error] --channel-id requires an explicit --platform=facebook or --platform=instagram.</error>');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $lockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sys_review_sync_mentions.lock';
        $fp = fopen($lockFile, 'c');
        if (!$fp || !flock($fp, LOCK_EX | LOCK_NB)) {
            $output->writeln('<comment>[MentionsSync] Another mentions synchronization process is already running. Exiting cleanly.</comment>');
            if ($fp) {
                fclose($fp);
            }
            return ExitCode::OK;
        }

        if ($output->isVerbose()) {
            $output->writeln(sprintf('<info>[MentionsSync] Starting mentions sync (platform: %s)...</info>', $platform));
        }

        try {
            $fbResult = ($platform === 'all' || $platform === 'facebook')
                ? $this->mentionSyncService->syncFacebookMentions($accountId, $channelId)
                : new \App\Domain\Review\Sync\ReviewSyncResult();

            $igResult = ($platform === 'all' || $platform === 'instagram')
                ? $this->mentionSyncService->syncInstagramMentions($accountId, $channelId)
                : new \App\Domain\Review\Sync\ReviewSyncResult();

            $result = $fbResult->merge($igResult);

            $hasChanges = ($result->getReviewsInserted() > 0 || $result->getReviewsUpdated() > 0);
            if ($hasChanges || $result->hasErrors() || $output->isVerbose()) {
                $output->writeln(sprintf(
                    '<info>[MentionsSync] Processed %d channel(s) (%d failed): %d fetched, %d inserted, %d updated, %d unchanged.</info>',
                    $result->getChannelsProcessed(),
                    $result->getChannelsFailed(),
                    $result->getReviewsFetched(),
                    $result->getReviewsInserted(),
                    $result->getReviewsUpdated(),
                    $result->getReviewsUnchanged()
                ));
            }

            if ($result->hasErrors() && $output->isVerbose()) {
                foreach ($result->getErrors() as $err) {
                    $output->writeln("<comment>[MentionsSync Warning] {$err}</comment>");
                }
            }

            return ExitCode::OK;
        } catch (Throwable $e) {
            $output->writeln('<error>[MentionsSync Error] Synchronization failed: ' . $e->getMessage() . '</error>');
            return ExitCode::UNSPECIFIED_ERROR;
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
}
