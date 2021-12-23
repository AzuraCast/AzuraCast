<?php

declare(strict_types=1);

namespace App\Console\Command\Internal;

use App\Console\Command\CommandAbstract;
use App\Entity;
use App\Radio\Adapters;
use App\Radio\Backend\Liquidsoap;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:internal:djoff',
    description: 'Indicate that a DJ has finished streaming to a station.',
)]
class DjOffCommand extends CommandAbstract
{
    public function __construct(
        protected Adapters $adapters,
        protected EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('station-id', InputArgument::REQUIRED)
            ->addOption('dj-user', null, InputOption::VALUE_REQUIRED, '', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $stationId = (int)$input->getArgument('station-id');
        $djUser = $input->getOption('dj-user');

        $station = $this->em->find(Entity\Station::class, $stationId);

        if (!($station instanceof Entity\Station) || !$station->getEnableStreamers()) {
            return 1;
        }

        $adapter = $this->adapters->getBackendAdapter($station);

        if ($adapter instanceof Liquidsoap) {
            $io->write($adapter->onDisconnect($station, $djUser));
            return 0;
        }

        $io->write('received');
        return 0;
    }
}
