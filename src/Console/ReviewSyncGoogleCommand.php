<?php

declare(strict_types=1);

namespace App\Console;

use App\Domain\Review\Sync\ReviewSyncServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(
    name: 'review/sync-google',
    description: 'Synchronizes reviews for all connected Google Business Profile locations',
)]
final class ReviewSyncGoogleCommand extends Command
{
    public function __construct(
        private ReviewSyncServiceInterface $syncService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('channel-id', 'c', InputOption::VALUE_OPTIONAL, 'Filter by specific Google ServiceConfig ID')
            ->addOption('account-id', 'a', InputOption::VALUE_OPTIONAL, 'Filter by specific Account ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $channelId = $input->getOption('channel-id') !== null ? (int) $input->getOption('channel-id') : null;
        $accountId = $input->getOption('account-id') !== null ? (int) $input->getOption('account-id') : null;

        $lockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sys_review_sync_google.lock';
        $fp = fopen($lockFile, 'c');
        if (!$fp || !flock($fp, LOCK_EX | LOCK_NB)) {
            $output->writeln('<comment>[ReviewSyncGoogle] Another Google review synchronization process is already running. Exiting cleanly.</comment>');
            if ($fp) {
                fclose($fp);
            }
            return ExitCode::OK;
        }

        $now = date('Y-m-d H:i:s');
        $output->writeln("<info>[ReviewSyncGoogle] Starting Google review synchronization at {$now}...</info>");

        try {
            $result = $this->syncService->syncGoogleReviews($accountId, $channelId);

            $output->writeln(sprintf(
                '<info>[ReviewSyncGoogle] Processed %d location(s): %d fetched, %d inserted, %d updated, %d unchanged.</info>',
                $result->getChannelsProcessed(),
                $result->getReviewsFetched(),
                $result->getReviewsInserted(),
                $result->getReviewsUpdated(),
                $result->getReviewsUnchanged()
            ));

            if ($result->hasErrors()) {
                foreach ($result->getErrors() as $err) {
                    $output->writeln("<comment>[ReviewSyncGoogle Warning] {$err}</comment>");
                }
            }

            return ExitCode::OK;
        } catch (Throwable $e) {
            $output->writeln('<error>[ReviewSyncGoogle Error] Google synchronization failed: ' . $e->getMessage() . '</error>');
            return ExitCode::UNSPECIFIED_ERROR;
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
}

