<?php

declare(strict_types=1);

namespace App\Console;

use App\Domain\SocialPost\Service\SocialPostQueueWorkerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(
    name: 'social-post/process-queue',
    description: 'Process and dispatch scheduled social posts across destinations',
)]
final class SocialPostQueueProcessCommand extends Command
{
    public function __construct(
        private SocialPostQueueWorkerService $workerService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'batch-size',
            'b',
            InputOption::VALUE_OPTIONAL,
            'Maximum number of queued destinations to process in one batch',
            50
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $batchSize = max(1, (int) $input->getOption('batch-size'));
        if ($output->isVerbose()) {
            $output->writeln(sprintf('<info>Starting social post queue worker (batch size: %d)...</info>', $batchSize));
        }

        try {
            $stats = $this->workerService->processBatchDetailed($batchSize);

            if ($stats['recovered_stale'] > 0) {
                $output->writeln(sprintf('<comment>Recovered %d stale processing destination(s).</comment>', $stats['recovered_stale']));
            }

            if ($stats['claimed'] === 0) {
                if ($output->isVerbose()) {
                    $output->writeln('<info>Queue worker completed: No pending destinations eligible for processing (resolvers inactive or queue empty).</info>');
                }
                return ExitCode::OK;
            }

            $output->writeln(sprintf(
                '<info>Batch summary: %d claimed | %d published | %d retried | %d failed | %d quarantined ambiguous</info>',
                $stats['claimed'],
                $stats['published'],
                $stats['retried'],
                $stats['failed'],
                $stats['ambiguous']
            ));

            return ExitCode::OK;
        } catch (Throwable $e) {
            $output->writeln(sprintf('<error>[SocialPostQueue Error] %s</error>', $e->getMessage()));
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}

