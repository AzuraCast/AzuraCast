<?php

declare(strict_types=1);

namespace App\Console\Command\Playlists;

use App\Console\Command\CommandAbstract;
use App\Entity\Repository\StationRepository;
use App\Service\PlaylistConfiguration\PlaylistConfigurationImporter;
use App\Utilities\Types;
use JsonException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use const JSON_THROW_ON_ERROR;

#[AsCommand(
    name: 'azuracast:playlist:import',
    description: 'Import playlists into a station from a JSON configuration dump'
)]
final class ImportCommand extends CommandAbstract
{
    public function __construct(
        private readonly StationRepository $stationRepo,
        private readonly PlaylistConfigurationImporter $importer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('station', InputArgument::REQUIRED, 'The station ID or shortcode');
        $this->addArgument('file', InputArgument::REQUIRED, 'Path to the JSON configuration dump');
        $this->addOption(
            'name-prefix',
            null,
            InputOption::VALUE_REQUIRED,
            'Optional prefix prepended to every imported playlist name'
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

        $file = Types::string($input->getArgument('file'));
        if (!is_file($file)) {
            $io->error(sprintf('File "%s" not found', $file));
            return self::FAILURE;
        }

        $contents = file_get_contents($file);
        if ($contents === false) {
            $io->error(sprintf('Could not read file "%s"', $file));
            return self::FAILURE;
        }

        try {
            $dump = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $io->error('Invalid JSON: ' . $exception->getMessage());
            return self::FAILURE;
        }

        if (!is_array($dump)) {
            $io->error('Invalid configuration dump');
            return self::INVALID;
        }

        $namePrefix = Types::stringOrNull($input->getOption('name-prefix'), true);

        $io->title(sprintf('Importing playlists into station: %s', $station->name));

        $summary = $this->importer->import($dump, $station, $namePrefix);

        $io->definitionList(
            ['Playlists created' => $summary->playlistsCreated],
            ['Folders created' => $summary->foldersCreated],
            ['Schedules created' => $summary->schedulesCreated],
            ['Media items created' => $summary->mediaItemsCreated],
            ['Media re-linked' => $summary->mediaRelinked],
            ['Media generated' => $summary->mediaGenerated],
            ['Group members created' => $summary->membersCreated],
        );

        if (!empty($summary->warnings)) {
            $io->warning($summary->warnings);
        }

        $io->success('Import complete.');

        return self::SUCCESS;
    }
}
