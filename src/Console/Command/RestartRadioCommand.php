<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Entity;
use App\Radio\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:radio:restart',
    description: 'Restart all radio stations, or a single one if specified.',
)]
class RestartRadioCommand extends CommandAbstract
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected Entity\Repository\StationRepository $stationRepo,
        protected Configuration $configuration,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('station-name', InputArgument::OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $stationName = $input->getArgument('station-name');

        if (!empty($stationName)) {
            $station = $this->stationRepo->findByIdentifier($stationName);

            if (!$station instanceof Entity\Station) {
                $io->error('Station not found.');
                return 1;
            }

            $stations = [$station];
        } else {
            $io->section('Restarting all radio stations...');

            /** @var Entity\Station[] $stations */
            $stations = $this->stationRepo->fetchAll();
        }

        $io->progressStart(count($stations));

        foreach ($stations as $station) {
            $this->configuration->writeConfiguration($station, true);
            $io->progressAdvance();
        }

        $io->progressFinish();
        return 0;
    }
}
