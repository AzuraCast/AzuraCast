<?php
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
    ) {
        $station = $em->find(Entity\Station::class, $stationId);

        if (!($station instanceof Entity\Station)) {
            $io->write('false');
            return null;
        }

        $io->write($autoDJ->annotateNextSong($station, $asAutodj));
        return null;
    }
}
