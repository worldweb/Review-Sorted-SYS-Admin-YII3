<?php

declare(strict_types=1);

namespace App\Console;

use App\Domain\SocialPost\SocialPostRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(
    name: 'social-post/process-queue',
    description: 'Process and dispatch scheduled social posts across destinations',
)]
final class SocialPostQueueProcessCommand extends Command
{
    public function __construct(
        private SocialPostRepositoryInterface $postRepo,
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
        $output->writeln(sprintf('<info>Starting social post queue worker (batch size: %d)...</info>', $batchSize));

        $pending = $this->postRepo->findPendingDestinations($batchSize);
        $output->writeln(sprintf('<info>Found %d pending destinations scheduled for delivery.</info>', count($pending)));

        return ExitCode::OK;
    }
}
