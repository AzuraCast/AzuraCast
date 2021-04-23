<?php

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use Psr\Log\LoggerInterface;

class ReactivateStreamerTask extends AbstractTask
{
    public function __construct(
        protected Entity\Repository\StationStreamerRepository $streamerRepo,
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        parent::__construct($em, $logger);
    }

    public function run(bool $force = false): void
    {
        $deactivated_streamers = $this->streamerRepo->getStreamersDueForReactivation();

        foreach ($deactivated_streamers as $streamer) {
            $streamer->setIsActive(true);
            $this->em->persist($streamer);
            $this->em->flush();
        }
    }
}
