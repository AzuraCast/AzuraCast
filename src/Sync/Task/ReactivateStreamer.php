<?php
namespace App\Sync\Task;

use App\Entity;
use Doctrine\ORM\EntityManager;

class ReactivateStreamer extends AbstractTask
{
    /** @var Entity\Repository\StationStreamerRepository */
    protected Entity\Repository\StationStreamerRepository $streamerRepo;

    public function __construct(
        EntityManager $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        Entity\Repository\StationStreamerRepository $streamerRepo
    ) {
        $this->streamerRepo = $streamerRepo;

        parent::__construct($em, $settingsRepo);
    }

    public function run($force = false): void
    {
        $deactivated_streamers = $this->streamerRepo->getStreamersDueForReactivation();

        foreach ($deactivated_streamers as $streamer) {
            $streamer->setIsActive(true);
            $this->em->persist($streamer);
            $this->em->flush();
        }
    }
}
