<?php

declare(strict_types=1);

namespace App\Console;

use App\Domain\SocialConnection\GoogleLocationMetadataService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(
    name: 'google/fetch-location-metadata',
    description: 'Retrieves Google Place IDs and Maps URLs for Google Business Profile locations',
)]
final class GoogleLocationMetadataCommand extends Command
{
    public function __construct(
        private GoogleLocationMetadataService $metadataService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('account-id', 'a', InputOption::VALUE_OPTIONAL, 'Filter by specific Account ID')
            ->addOption('config-id', 'c', InputOption::VALUE_OPTIONAL, 'Filter by specific ServiceConfig ID')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limit number of locations to process (for batch processing)', 0)
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force re-fetching metadata even if already populated')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview actions without modifying database records');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $accountId = $input->getOption('account-id') !== null ? (int) $input->getOption('account-id') : null;
        $configId = $input->getOption('config-id') !== null ? (int) $input->getOption('config-id') : null;
        $limit = (int) $input->getOption('limit');
        $force = (bool) $input->getOption('force');
        $dryRun = (bool) $input->getOption('dry-run');

        // Mutex lock to prevent overlapping runs
        $lockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sys_google_location_metadata.lock';
        $fp = fopen($lockFile, 'c');
        if (!$fp || !flock($fp, LOCK_EX | LOCK_NB)) {
            $output->writeln('<comment>[GoogleLocationMetadata] Another metadata retrieval process is already running. Exiting cleanly.</comment>');
            if ($fp) {
                fclose($fp);
            }
            return ExitCode::OK;
        }

        $now = date('Y-m-d H:i:s');
        $output->writeln('<info>===============================================================</info>');
        $output->writeln("<info> Google Location Metadata Retrieval & Backfill CLI ({$now}) </info>");
        $output->writeln('<info>===============================================================</info>');

        if ($dryRun) {
            $output->writeln('<comment>[Mode: DRY-RUN - No database records will be modified]</comment>');
        }
        if ($force) {
            $output->writeln('<comment>[Mode: FORCE - Re-fetching metadata for already populated locations]</comment>');
        }
        if ($limit > 0) {
            $output->writeln("<comment>[Batch Limit: Processing up to {$limit} location(s)]</comment>");
        }

        try {
            $progressCallback = static function (array $item) use ($output): void {
                $statusTag = match ($item['status']) {
                    'success' => '<info>[SUCCESS]</info>',
                    'skipped' => '<comment>[SKIPPED]</comment>',
                    default => '<error>[FAILED]</error>',
                };

                $output->writeln(sprintf(
                    ' %s #%d (%s): %s',
                    $statusTag,
                    $item['config_id'],
                    $item['label'],
                    $item['message']
                ));
            };

            $summary = $this->metadataService->processAllLocations(
                accountId: $accountId,
                configId: $configId,
                limit: $limit,
                force: $force,
                dryRun: $dryRun,
                progressCallback: $progressCallback,
            );

            $output->writeln('');
            $output->writeln('<info>===================== SUMMARY =====================</info>');
            $output->writeln(sprintf('<info>Total Eligible Locations: %d</info>', $summary['total_eligible']));
            $output->writeln(sprintf('<info>Processed:                %d</info>', $summary['processed']));
            $output->writeln(sprintf('<info>Successfully Updated:     %d</info>', $summary['updated']));
            $output->writeln(sprintf('<comment>Skipped:                  %d</comment>', $summary['skipped']));
            $output->writeln(sprintf('<error>Failed:                   %d</error>', $summary['failed']));
            $output->writeln('<info>===================================================</info>');

            if (!empty($summary['errors'])) {
                $output->writeln('');
                $output->writeln('<comment>Error Details (First 10):</comment>');
                foreach (array_slice($summary['errors'], 0, 10) as $err) {
                    $output->writeln(" - <comment>{$err}</comment>");
                }
            }

            return ExitCode::OK;
        } catch (Throwable $e) {
            $output->writeln('<error>[Fatal Error] Metadata retrieval command failed: ' . $e->getMessage() . '</error>');
            return ExitCode::UNSPECIFIED_ERROR;
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
}
