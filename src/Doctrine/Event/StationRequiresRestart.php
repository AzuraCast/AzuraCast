<?php

declare(strict_types=1);

namespace App\Doctrine\Event;

use App\Entity;
use App\Entity\Attributes\AuditIgnore;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use ReflectionObject;

/**
 * A hook into Doctrine's event listener to mark a station as
 * needing restart if one of its related entities is changed.
 */
class StationRequiresRestart implements EventSubscriber
{
    /**
     * @inheritDoc
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
        ];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        $collections_to_check = [
            Entity\AuditLog::OPER_INSERT => $uow->getScheduledEntityInsertions(),
            Entity\AuditLog::OPER_UPDATE => $uow->getScheduledEntityUpdates(),
            Entity\AuditLog::OPER_DELETE => $uow->getScheduledEntityDeletions(),
        ];

        $stations_to_restart = [];

        foreach ($collections_to_check as $change_type => $collection) {
            foreach ($collection as $entity) {
                if (
                    ($entity instanceof Entity\StationMount)
                    || ($entity instanceof Entity\StationRemote && $entity->isEditable())
                    || ($entity instanceof Entity\StationPlaylist && $entity->getStation()->useManualAutoDJ())
                ) {
                    if (Entity\AuditLog::OPER_UPDATE === $change_type) {
                        $changes = $uow->getEntityChangeSet($entity);

                        // Look for the @AuditIgnore annotation on a property.
                        $class_reflection = new ReflectionObject($entity);
                        foreach ($changes as $change_field => $changeset) {
                            $ignoreAttr = $class_reflection->getProperty($change_field)->getAttributes(
                                AuditIgnore::class
                            );
                            if (!empty($ignoreAttr)) {
                                unset($changes[$change_field]);
                            }
                        }

                        if (empty($changes)) {
                            continue;
                        }
                    }

                    $station = $entity->getStation();
                    $stations_to_restart[$station->getId()] = $station;
                }
            }
        }

        if (count($stations_to_restart) > 0) {
            foreach ($stations_to_restart as $station) {
                $station->setNeedsRestart(true);
                $em->persist($station);

                $station_meta = $em->getClassMetadata(Entity\Station::class);
                $uow->recomputeSingleEntityChangeSet($station_meta, $station);
            }
        }
    }
}
