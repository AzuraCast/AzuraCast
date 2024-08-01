<?php

declare(strict_types=1);

namespace App\Console\Command\Media;

use App\Console\Command\CommandAbstract;
use App\Container\EntityManagerAwareTrait;
use App\Entity\Repository\StationRepository;
use App\Entity\Station;
use App\Utilities\Types;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

abstract class AbstractBatchMediaCommand extends CommandAbstract
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly StationRepository $stationRepo,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'station-name',
            InputArgument::OPTIONAL,
            'The shortcode for the station (i.e. "my_station_name") to only apply to one station.'
        );
        $this->addArgument(
            'path',
            InputArgument::OPTIONAL,
            'Optionally specify a path (of either a file or a directory) to only apply to that item.'
        );
    }

    protected function getStation(InputInterface $input): ?Station
    {
        $stationName = Types::stringOrNull($input->getArgument('station-name'), true);
        if (null === $stationName) {
            return null;
        }

        $station = $this->stationRepo->findByIdentifier($stationName);
        if (!$station instanceof Station) {
            throw new InvalidArgumentException('Station not found.');
        }

        return $station;
    }

    protected function getPath(InputInterface $input): ?string
    {
        return Types::stringOrNull($input->getArgument('path'), true);
    }
}
