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
    name: 'review/sync-facebook-replies',
    description: 'Synchronizes Facebook Page post replies and comments',
)]
final class ReviewSyncFacebookRepliesCommand extends Command
{
    public function __construct(
        private SocialMentionSyncService $mentionSyncService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('channel-id', 'c', InputOption::VALUE_OPTIONAL, 'Filter by specific Facebook ServiceConfig ID')
            ->addOption('account-id', 'a', InputOption::VALUE_OPTIONAL, 'Filter by specific Account ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $channelId = $input->getOption('channel-id') !== null ? (int) $input->getOption('channel-id') : null;
        $accountId = $input->getOption('account-id') !== null ? (int) $input->getOption('account-id') : null;

        $lockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sys_review_sync_fb_replies.lock';
        $fp = fopen($lockFile, 'c');
        if (!$fp || !flock($fp, LOCK_EX | LOCK_NB)) {
            $output->writeln('<comment>[FbRepliesSync] Another Facebook replies synchronization process is already running. Exiting cleanly.</comment>');
            if ($fp) {
                fclose($fp);
            }
            return ExitCode::OK;
        }

        if ($output->isVerbose()) {
            $output->writeln('<info>[FbRepliesSync] Starting Facebook post replies synchronization...</info>');
        }

        try {
            $result = $this->mentionSyncService->syncFacebookPostReplies($accountId, $channelId);

            $hasChanges = ($result->getReviewsInserted() > 0 || $result->getReviewsUpdated() > 0);
            if ($hasChanges || $result->hasErrors() || $output->isVerbose()) {
                $output->writeln(sprintf(
                    '<info>[FbRepliesSync] Processed %d channel(s) (%d failed): %d fetched, %d inserted, %d updated, %d unchanged.</info>',
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
                    $output->writeln("<comment>[FbRepliesSync Warning] {$err}</comment>");
                }
            }

            return ExitCode::OK;
        } catch (Throwable $e) {
            $output->writeln('<error>[FbRepliesSync Error] Synchronization failed: ' . $e->getMessage() . '</error>');
            return ExitCode::UNSPECIFIED_ERROR;
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
}
