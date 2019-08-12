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
            'inserts' => $uow->getScheduledEntityInsertions(),
            'updates' => $uow->getScheduledEntityUpdates(),
            'deletes' => $uow->getScheduledEntityDeletions(),
        ];

        $stations_to_restart = [];
        foreach($collections_to_check as $change_type => $collection) {
            foreach ($collection as $entity) {
                if (($entity instanceof Entity\StationMount)
                    || ($entity instanceof Entity\StationRemote && $entity->isEditable())
                    || ($entity instanceof Entity\StationPlaylist && $entity->getStation()->useManualAutoDJ())) {
                    if ('updates' === $change_type) {
                        $changes = $uow->getEntityChangeSet($entity);
                        unset($changes['listeners_unique'], $changes['listeners_total']);

                        if (empty($changes)) {
                            continue;
                        }
                    }

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
