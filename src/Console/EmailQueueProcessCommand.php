<?php

declare(strict_types=1);

namespace App\Console;

use App\Domain\Email\Service\EmailQueueWorkerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(
    name: 'email/process-queue',
    description: 'Process and dispatch pending emails from the outbound queue',
)]
final class EmailQueueProcessCommand extends Command
{
    public function __construct(
        private EmailQueueWorkerService $workerService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'batch-size',
            'b',
            InputOption::VALUE_OPTIONAL,
            'Maximum number of messages to process in one batch',
            100
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $batchSize = max(1, (int) $input->getOption('batch-size'));
        if ($output->isVerbose()) {
            $output->writeln(sprintf('<info>Starting email queue worker (batch size: %d)...</info>', $batchSize));
        }

        try {
            $stats = $this->workerService->processBatchDetailed($batchSize);

            if ($stats['recovered_stale'] > 0) {
                $output->writeln(sprintf('<comment>[EmailQueue] Recovered %d stale processing email job(s).</comment>', $stats['recovered_stale']));
            }

            if ($stats['failed'] > 0) {
                $output->writeln(sprintf('<error>[EmailQueue] %d email(s) failed delivery. Check app.log for details.</error>', $stats['failed']));
            }

            if ($stats['dispatched'] > 0 || $output->isVerbose()) {
                $output->writeln(sprintf('<info>Completed. Successfully processed %d emails.</info>', $stats['dispatched']));
            }

            return ExitCode::OK;
        } catch (Throwable $e) {
            $output->writeln(sprintf('<error>[EmailQueue Error] %s</error>', $e->getMessage()));
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}
