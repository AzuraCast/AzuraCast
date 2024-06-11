<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Entity\Repository\StationQueueRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:station-queues:clear',
    description: 'Clear all unplayed station queues.'
)]
final class ClearQueuesCommand extends CommandAbstract
{
    public function __construct(
        private readonly StationQueueRepository $queueRepo,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Clear all station queues.
        $this->queueRepo->clearUnplayed();

        $io->success('Unplayed station queues cleared.');
        return 0;
    }
}
