<?php

declare(strict_types=1);

namespace App\Console;

use App\Infrastructure\Persistence\Migration\MigrationSyncService;
use App\Infrastructure\Persistence\Schema\SchemaSynchronizationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Yiisoft\Yii\Console\ExitCode;

/**
 * DatabaseSyncCommand.
 * Headless CLI command to bootstrap a fresh database or sync existing database schema and migrations.
 * Solves the bootstrapping blocker (FUNC-01) by allowing non-interactive headless database setup.
 */
#[AsCommand(
    name: 'db/sync',
    description: 'Synchronizes live database schema with canonical manifest and applies pending migrations headlessly',
)]
final class DatabaseSyncCommand extends Command
{
    public function __construct(
        private SchemaSynchronizationService $schemaSyncService,
        private ?MigrationSyncService $migrationSyncService = null,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Display synchronization plan without executing changes')
            ->addOption('skip-migrations', null, InputOption::VALUE_NONE, 'Skip executing database migration classes')
            ->addOption('skip-schema', null, InputOption::VALUE_NONE, 'Skip schema manifest synchronization');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isDryRun = (bool) $input->getOption('dry-run');
        $skipMigrations = (bool) $input->getOption('skip-migrations');
        $skipSchema = (bool) $input->getOption('skip-schema');

        $output->writeln('<info>===================================================</info>');
        $output->writeln('<info> SYS Yii3 Database Synchronization & Bootstrap CLI </info>');
        $output->writeln('<info>===================================================</info>');

        if ($isDryRun) {
            $output->writeln('<comment>[Mode: DRY-RUN - No changes will be applied]</comment>');
        }

        try {
            // 1. Schema Manifest Synchronization
            if (!$skipSchema) {
                $output->writeln('');
                $output->writeln('<info>--- Phase 1: Schema Synchronization ---</info>');

                $plan = $this->schemaSyncService->generatePlan();
                $output->writeln(sprintf('Target Database: <comment>%s</comment>', (string) ($plan['database'] ?? 'unknown')));

                $safeCount = count($plan['categories'][SchemaSynchronizationService::RISK_SAFE_ADDITION] ?? []);
                $modCount = count($plan['categories'][SchemaSynchronizationService::RISK_MODIFICATION] ?? []);
                $reviewCount = count($plan['categories'][SchemaSynchronizationService::RISK_MANUAL_REVIEW] ?? []);
                $destructCount = count($plan['categories'][SchemaSynchronizationService::RISK_DESTRUCTIVE] ?? []);

                $output->writeln(sprintf('Pending Safe Additions: <comment>%d</comment>', $safeCount));
                $output->writeln(sprintf('Pending Safe Modifications: <comment>%d</comment>', $modCount));
                $output->writeln(sprintf('Manual Review Items (skipped): <comment>%d</comment>', $reviewCount));
                $output->writeln(sprintf('Destructive Changes (never auto-executed): <comment>%d</comment>', $destructCount));

                if (!$isDryRun) {
                    if ($safeCount > 0 || $modCount > 0) {
                        $output->writeln('Executing safe schema changes...');
                        $report = $this->schemaSyncService->executePlan();

                        if (!empty($report['error'])) {
                            $output->writeln(sprintf('<error>Schema synchronization error: %s</error>', $report['error']));
                            return ExitCode::UNSPECIFIED_ERROR;
                        }

                        $output->writeln(sprintf('<info>✓ Successfully executed %d safe schema operation(s).</info>', (int) ($report['successCount'] ?? 0)));
                        if (($report['failedCount'] ?? 0) > 0) {
                            $output->writeln(sprintf('<error>✗ %d operation(s) failed.</error>', (int) $report['failedCount']));
                            return ExitCode::UNSPECIFIED_ERROR;
                        }
                    } else {
                        $output->writeln('<info>✓ Database schema is already in sync with manifest.</info>');
                    }
                }
            }

            // 2. Migration Class Execution
            if (!$skipMigrations && $this->migrationSyncService !== null) {
                $output->writeln('');
                $output->writeln('<info>--- Phase 2: Database Migrations ---</info>');

                $migStatus = $this->migrationSyncService->getMigrationStatus();
                $pendingCount = count($migStatus['pending'] ?? []);
                $output->writeln(sprintf('Pending Migration Classes: <comment>%d</comment>', $pendingCount));

                if (!$isDryRun) {
                    if ($pendingCount > 0) {
                        $output->writeln('Applying pending migrations...');
                        $migReport = $this->migrationSyncService->applyPendingMigrations();

                        if (!empty($migReport['error'])) {
                            $output->writeln(sprintf('<error>Migration execution error: %s</error>', $migReport['error']));
                            return ExitCode::UNSPECIFIED_ERROR;
                        }

                        $applied = (array) ($migReport['applied'] ?? []);
                        $output->writeln(sprintf('<info>✓ Successfully applied %d migration(s): %s</info>', count($applied), implode(', ', $applied)));
                    } else {
                        $output->writeln('<info>✓ All migration classes are already applied.</info>');
                    }
                }
            }

            $output->writeln('');
            $output->writeln('<info>===================================================</info>');
            $output->writeln('<info> Database Synchronization Completed Successfully! </info>');
            $output->writeln('<info>===================================================</info>');

            return ExitCode::OK;
        } catch (Throwable $e) {
            $output->writeln(sprintf('<error>Fatal error during database synchronization: %s</error>', $e->getMessage()));
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}
