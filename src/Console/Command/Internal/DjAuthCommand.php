<?php

declare(strict_types=1);

namespace App\Console\Command\Internal;

use App\Console\Command\CommandAbstract;
use App\Entity;
use App\Radio\Adapters;
use App\Radio\Backend\Liquidsoap;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DjAuthCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        EntityManagerInterface $em,
        Adapters $adapters,
        int $stationId,
        string $djUser = '',
        string $djPassword = ''
    ): int {
        $station = $em->getRepository(Entity\Station::class)->find($stationId);

        if (!($station instanceof Entity\Station) || !$station->getEnableStreamers()) {
            $io->write('false');
            return 0;
        }

        $adapter = $adapters->getBackendAdapter($station);

        if ($adapter instanceof Liquidsoap) {
            $response = $adapter->authenticateStreamer($station, $djUser, $djPassword);
            $io->write($response);
            return 0;
        }

        $io->write('false');
        return 0;
    }
}
