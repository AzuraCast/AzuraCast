<?php

declare(strict_types=1);

namespace App\Console\Command\Media;

use App\Entity\Repository\StationMediaRepository;
use App\Entity\Repository\StationRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:media:clear-computed-autocue',
    description: 'Clear computed AutoCue values.',
)]
final class ClearComputedAutoCueCommand extends AbstractBatchMediaCommand
{
    public function __construct(
        StationRepository $stationRepo,
        private readonly StationMediaRepository $mediaRepo,
    ) {
        parent::__construct($stationRepo);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $station = $this->getStation($input);
        $path = $this->getPath($input);

        $io->title('Clear Computed AutoCue Values');

        if ($station === null) {
            $io->section('Clearing computed AutoCue values for all stations...');
        } else {
            $io->writeln("Clearing computed AutoCue values for station: {$station->name}");
        }

        $recordsAffected = $this->mediaRepo->clearComputedAutoCue($station?->media_storage_location, $path);

        $io->writeln("Cleared computed AutoCue values for {$recordsAffected} record(s).");

        return 0;
    }
}
