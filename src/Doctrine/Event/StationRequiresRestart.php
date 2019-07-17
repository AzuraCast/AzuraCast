<?php
namespace App\Doctrine\Event;

use App\Entity;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

/**
 * A hook into Doctrine's event listener to mark a station as
 * needing restart if one of its related entities is changed.
 */
class StationRequiresRestart implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            Events::onFlush,
        ];
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        $restart_classes = [
            Entity\StationMount::class,
            Entity\StationRemote::class,
            Entity\StationPlaylist::class,
        ];

        $collections_to_check = [
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates(),
            $uow->getScheduledEntityDeletions(),
        ];

        $stations_to_restart = [];
        foreach($collections_to_check as $collection) {
            foreach ($collection as $entity) {
                if (($entity instanceof Entity\StationMount)
                    || ($entity instanceof Entity\StationRemote && $entity->isEditable())
                    || ($entity instanceof Entity\StationPlaylist && $entity->getStation()->useManualAutoDJ())) {
                    /** @var Entity\Station $station */
                    $station = $entity->getStation();
                    $stations_to_restart[$station->getId()] = $station;
                }
            }
        }

        if (count($stations_to_restart) > 0) {
            foreach($stations_to_restart as $station) {
                $station->setNeedsRestart(true);
                $em->persist($station);

                $station_meta = $em->getClassMetadata(Entity\Station::class);
                $uow->recomputeSingleEntityChangeSet($station_meta, $station);
            }
        }
    }
}
