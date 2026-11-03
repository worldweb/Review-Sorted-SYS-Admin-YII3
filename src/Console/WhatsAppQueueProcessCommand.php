<?php

declare(strict_types=1);

namespace App\Console;

use App\Domain\WhatsApp\Service\WhatsAppQueueWorkerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(
    name: 'whatsapp/process-queue',
    description: 'Process and dispatch pending WhatsApp messages from the outbound queue',
)]
final class WhatsAppQueueProcessCommand extends Command
{
    public function __construct(
        private WhatsAppQueueWorkerService $workerService,
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
        $output->writeln(sprintf('<info>Starting WhatsApp queue worker (batch size: %d)...</info>', $batchSize));

        $dispatched = $this->workerService->processBatch($batchSize);
        $output->writeln(sprintf('<info>Completed. Successfully processed %d messages.</info>', $dispatched));

        return ExitCode::OK;
    }
}
