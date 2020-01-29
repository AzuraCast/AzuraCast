<?php
namespace App\Sync\Task;

use App\Entity;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class ReactivateStreamer extends AbstractTask
{
    protected Entity\Repository\StationStreamerRepository $streamerRepo;

    public function __construct(
        EntityManager $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        LoggerInterface $logger,
        Entity\Repository\StationStreamerRepository $streamerRepo
    ) {
        parent::__construct($em, $settingsRepo, $logger);

        $this->streamerRepo = $streamerRepo;
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
