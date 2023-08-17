<?php

declare(strict_types=1);

namespace App\Doctrine\Event;

use App\Entity\Attributes\AuditIgnore;
use App\Entity\Enums\AuditLogOperations;
use App\Entity\Station;
use App\Entity\StationHlsStream;
use App\Entity\StationMount;
use App\Entity\StationPlaylist;
use App\Entity\StationRemote;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use ReflectionObject;

/**
 * A hook into Doctrine's event listener to mark a station as
 * needing restart if one of its related entities is changed.
 */
final class StationRequiresRestart implements EventSubscriber
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
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        $collectionsToCheck = [
            [
                AuditLogOperations::Insert,
                $uow->getScheduledEntityInsertions(),
            ],
            [
                AuditLogOperations::Update,
                $uow->getScheduledEntityUpdates(),
            ],
            [
                AuditLogOperations::Delete,
                $uow->getScheduledEntityDeletions(),
            ],
        ];

        $stationsToRestart = [];

        foreach ($collectionsToCheck as [$changeType, $collection]) {
            foreach ($collection as $entity) {
                if (
                    ($entity instanceof StationMount)
                    || ($entity instanceof StationHlsStream)
                    || ($entity instanceof StationRemote && $entity->isEditable())
                    || ($entity instanceof StationPlaylist && $entity->getStation()->useManualAutoDJ())
                ) {
                    if (AuditLogOperations::Update === $changeType) {
                        $changes = $uow->getEntityChangeSet($entity);

                        // Look for the @AuditIgnore annotation on a property.
                        $classReflection = new ReflectionObject($entity);
                        foreach ($changes as $changeField => $changeset) {
                            $ignoreAttr = $classReflection->getProperty($changeField)->getAttributes(
                                AuditIgnore::class
                            );
                            if (!empty($ignoreAttr)) {
                                unset($changes[$changeField]);
                            }
                        }

                        if (empty($changes)) {
                            continue;
                        }
                    }

                    $station = $entity->getStation();
                    $stationsToRestart[$station->getId()] = $station;
                }
            }
        }

        if (count($stationsToRestart) > 0) {
            foreach ($stationsToRestart as $station) {
                $station->setNeedsRestart(true);
                $em->persist($station);

                $stationMeta = $em->getClassMetadata(Station::class);
                $uow->recomputeSingleEntityChangeSet($stationMeta, $station);
            }
        }
    }
}
