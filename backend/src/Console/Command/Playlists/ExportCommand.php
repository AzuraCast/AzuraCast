<?php

declare(strict_types=1);

namespace App\Console\Command\Playlists;

use App\Console\Command\CommandAbstract;
use App\Entity\Repository\StationPlaylistRepository;
use App\Entity\Repository\StationRepository;
use App\Service\PlaylistConfiguration\PlaylistConfigurationExporter;
use App\Utilities\Types;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

#[AsCommand(
    name: 'azuracast:playlist:export',
    description: "Export a station's playlist/s as a portable JSON configuration dump"
)]
final class ExportCommand extends CommandAbstract
{
    public function __construct(
        private readonly StationRepository $stationRepo,
        private readonly StationPlaylistRepository $playlistRepo,
        private readonly PlaylistConfigurationExporter $exporter
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('station', InputArgument::REQUIRED, 'The station ID or shortcode');
        $this->addArgument(
            'playlist-id',
            InputArgument::OPTIONAL,
            'Export only this playlist ID (defaults to all playlists on the station)'
        );
        $this->addOption(
            'output',
            'o',
            InputOption::VALUE_REQUIRED,
            'Write the dump to this file path instead of standard output'
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

        $playlistId = Types::stringOrNull($input->getArgument('playlist-id'), true);
        if ($playlistId !== null) {
            $playlist = $this->playlistRepo->requireForStation($playlistId, $station);
            $dump = $this->exporter->exportPlaylist($playlist);
        } else {
            $dump = $this->exporter->exportStationPlaylists($station);
        }

        $json = json_encode(
            $dump,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        $outputPath = Types::stringOrNull($input->getOption('output'), true);
        if ($outputPath !== null) {
            if (false === file_put_contents($outputPath, $json)) {
                throw new RuntimeException(sprintf('Could not write to "%s"', $outputPath));
            }
            $io->success(sprintf('Exported playlist configuration to "%s"', $outputPath));
        } else {
            $output->writeln($json, OutputInterface::OUTPUT_RAW);
        }

        return self::SUCCESS;
    }
}
