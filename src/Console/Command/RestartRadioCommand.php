<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Entity\Repository\StationRepository;
use App\Entity\Station;
use App\Nginx\Nginx;
use App\Radio\Configuration;
use App\Utilities\Types;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'azuracast:radio:restart',
    description: 'Restart all radio stations, or a single one if specified.',
)]
final class RestartRadioCommand extends CommandAbstract
{
    public function __construct(
        private readonly StationRepository $stationRepo,
        private readonly Configuration $configuration,
        private readonly Nginx $nginx,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('station-name', InputArgument::OPTIONAL)
            ->addOption(
                'no-supervisor-restart',
                null,
                InputOption::VALUE_NONE,
                'Do not reload Supervisord immediately with changes.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $stationName = Types::stringOrNull($input->getArgument('station-name'));
        $noSupervisorRestart = Types::bool($input->getOption('no-supervisor-restart'));

        if (!empty($stationName)) {
            $station = $this->stationRepo->findByIdentifier($stationName);

            if (!$station instanceof Station) {
                $io->error('Station not found.');
                return 1;
            }

            $stations = [$station];
        } else {
            $io->section('Restarting all radio stations...');

            $stations = $this->stationRepo->fetchAll();
        }

        $io->progressStart(count($stations));

        foreach ($stations as $station) {
            try {
                $this->configuration->writeConfiguration(
                    station: $station,
                    reloadSupervisor: !$noSupervisorRestart,
                    forceRestart: true
                );

                $this->nginx->writeConfiguration(
                    station: $station,
                    reloadIfChanged: false
                );
            } catch (Throwable $e) {
                $io->warning([
                    $station . ': ' . $e->getMessage(),
                ]);
            }

            $io->progressAdvance();
        }

        if (!$noSupervisorRestart) {
            $this->nginx->reload();
        }

        $io->progressFinish();
        return 0;
    }
}
