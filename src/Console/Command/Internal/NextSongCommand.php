<?php

declare(strict_types=1);

namespace App\Console\Command\Internal;

use App\Console\Command\CommandAbstract;
use App\Entity;
use App\Radio\AutoDJ;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class NextSongCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        EntityManagerInterface $em,
        AutoDJ $autoDJ,
        int $stationId,
        bool $asAutodj = false
    ): int {
        $station = $em->find(Entity\Station::class, $stationId);

        if (!($station instanceof Entity\Station)) {
            $io->write('false');
            return 0;
        }

        $io->write($autoDJ->annotateNextSong($station, $asAutodj));
        return 0;
    }
}
