<?php
namespace App\Sync\Task;

use Doctrine\ORM\EntityManager;
use App\Entity;

class ReactivateStreamer extends TaskAbstract
{
    /** @var EntityManager */
    protected $em;

    /**
     * ReactivateStreamer constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function run($force = false)
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
