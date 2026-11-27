<?php

declare(strict_types=1);

namespace App\Console;

use App\Domain\Email\Service\EmailStatusSyncService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(
    name: 'email/update-status',
    description: 'Poll Mailgun Events API to update delivery status of sent emails',
)]
final class EmailStatusUpdateCommand extends Command
{
    public function __construct(
        private EmailStatusSyncService $statusSyncService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'limit',
            'l',
            InputOption::VALUE_OPTIONAL,
            'Maximum number of pending-delivery emails to check in one run',
            100
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = max(1, (int) $input->getOption('limit'));
        if ($output->isVerbose()) {
            $output->writeln(sprintf('<info>Checking Mailgun delivery status (limit: %d)...</info>', $limit));
        }

        try {
            $stats = $this->statusSyncService->syncPendingStatuses($limit);

            if ($stats['errors'] > 0) {
                $output->writeln(sprintf('<error>[EmailStatus] %d status check(s) failed. Check app.log for details.</error>', $stats['errors']));
            }

            $hasUpdates = ($stats['delivered'] > 0 || $stats['accepted'] > 0 || $stats['failed'] > 0);
            if ($hasUpdates || $output->isVerbose()) {
                $output->writeln(sprintf(
                    '<info>[EmailStatus] Checked %d email(s): %d delivered, %d accepted, %d failed.</info>',
                    $stats['checked'],
                    $stats['delivered'],
                    $stats['accepted'],
                    $stats['failed']
                ));
            }

            return ExitCode::OK;
        } catch (Throwable $e) {
            $output->writeln(sprintf('<error>[EmailStatus Error] %s</error>', $e->getMessage()));
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}
