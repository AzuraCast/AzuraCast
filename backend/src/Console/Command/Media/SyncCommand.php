<?php

declare(strict_types=1);

namespace App\Console\Command\Media;

use App\Console\Command\CommandAbstract;
use App\Entity\Repository\StationRepository;
use App\Sync\Task\CheckMediaTask;
use App\Utilities\Types;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:media:sync',
    description: 'Sync the media of one station with its media storage.',
)]
final class SyncCommand extends CommandAbstract
{
    public function __construct(
        private readonly StationRepository $stationRepo,
        private readonly CheckMediaTask $checkMediaTask,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            name: 'station',
            mode: InputArgument::REQUIRED,
            description: 'The station ID or shortcode'
        );

        $this->addOption(
            name: 'allow-empty-storage',
            mode: InputOption::VALUE_NONE,
            description: 'Remove media even when the storage shows no files'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $stationName = Types::string($input->getArgument('station'));
        $station = $this->stationRepo->findByIdentifier($stationName);
        if ($station === null) {
            $io->error('Station not found');
            return self::FAILURE;
        }

        $allowEmptyStorage = Types::bool($input->getOption('allow-empty-storage'));

        $io->title("Sync Media for Station: {$station->name}");
        $io->note('This affects every station that uses this media storage.');

        $stats = $this->checkMediaTask->importMusic($station->media_storage_location, $allowEmptyStorage);
        if ($stats === null) {
            $io->error('The media sync was skipped. See the log above for the reason.');
            return self::FAILURE;
        }

        $io->horizontalTable(
            array_keys($stats),
            [array_values($stats)]
        );

        return self::SUCCESS;
    }
}
