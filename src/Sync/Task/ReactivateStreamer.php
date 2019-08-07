<?php
namespace App\Sync\Task;

use App\Entity;

class ReactivateStreamer extends AbstractTask
{
    public function run($force = false): void
    {
        /** @var Entity\Repository\StationStreamerRepository $streamer_repo */
        $streamer_repo = $this->em->getRepository(Entity\StationStreamer::class);

        $deactivated_streamers = $streamer_repo->getStreamersDueForReactivation();

        foreach ($deactivated_streamers as $streamer) {
            $streamer->setIsActive(true);
            $this->em->persist($streamer);
            $this->em->flush();
        }
    }
}
