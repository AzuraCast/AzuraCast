<?php

declare(strict_types=1);

namespace App\Doctrine\Event;

use App\Entity;
use App\Entity\Attributes\Auditable;
use App\Entity\Attributes\AuditIgnore;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\UnitOfWork;
use ProxyManager\Proxy\GhostObjectInterface;
use ReflectionClass;
use ReflectionObject;
use Stringable;

/**
 * A hook into Doctrine's event listener to write changes to "Auditable"
 * entities to the audit log.
 *
 * Portions inspired by DataDog's audit bundle for Doctrine:
 * https://github.com/DATA-DOG/DataDogAuditBundle/blob/master/src/DataDog/AuditBundle/EventSubscriber/AuditSubscriber.php
 */
class AuditLog implements EventSubscriber
{
    /**
     * @return string[]
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

        $singleAuditLogs = $this->handleSingleUpdates($em, $uow);
        $collectionAuditLogs = $this->handleCollectionUpdates($uow);
        $newAuditLogs = array_merge($singleAuditLogs, $collectionAuditLogs);

        if (!empty($newAuditLogs)) {
            $auditLogMetadata = $em->getClassMetadata(Entity\AuditLog::class);
            foreach ($newAuditLogs as $auditLog) {
                $uow->persist($auditLog);
                $uow->computeChangeSet($auditLogMetadata, $auditLog);
            }
        }
    }

    /** @return Entity\AuditLog[] */
    protected function handleSingleUpdates(
        EntityManagerInterface $em,
        UnitOfWork $uow
    ): array {
        $newRecords = [];

        $collections = [
            Entity\AuditLog::OPER_INSERT => $uow->getScheduledEntityInsertions(),
            Entity\AuditLog::OPER_UPDATE => $uow->getScheduledEntityUpdates(),
            Entity\AuditLog::OPER_DELETE => $uow->getScheduledEntityDeletions(),
        ];

        foreach ($collections as $changeType => $collection) {
            foreach ($collection as $entity) {
                // Check that the entity being managed is "Auditable".
                $reflectionClass = new ReflectionObject($entity);
                if (!$this->isAuditable($reflectionClass)) {
                    continue;
                }

                // Get the changes made to the entity.
                $changesRaw = $uow->getEntityChangeSet($entity);

                // Look for the @AuditIgnore annotation on properties.
                $changes = [];

                foreach ($changesRaw as $changeField => [$fieldPrev, $fieldNow]) {
                    // With new entity creation, fields left NULL are still included.
                    if ($fieldPrev === $fieldNow) {
                        continue;
                    }

                    // Ensure the property isn't ignored.
                    $ignoreAttr = $reflectionClass->getProperty($changeField)->getAttributes(AuditIgnore::class);
                    if (!empty($ignoreAttr)) {
                        continue;
                    }

                    // Check if either field value is an object.
                    if ($this->isEntity($em, $fieldPrev)) {
                        $fieldPrev = $this->getIdentifier($fieldPrev);
                    }
                    if ($this->isEntity($em, $fieldNow)) {
                        $fieldNow = $this->getIdentifier($fieldNow);
                    }

                    $changes[$changeField] = [$fieldPrev, $fieldNow];
                }

                if (Entity\AuditLog::OPER_UPDATE === $changeType && empty($changes)) {
                    continue;
                }

                // Find the identifier method or property.
                $identifier = $this->getIdentifier($entity);

                $newRecords[] = new Entity\AuditLog(
                    $changeType,
                    get_class($entity),
                    $identifier,
                    null,
                    null,
                    $changes
                );
            }
        }

        return $newRecords;
    }

    /** @return Entity\AuditLog[] */
    protected function handleCollectionUpdates(
        UnitOfWork $uow
    ): array {
        $newRecords = [];
        $associated = [];
        $disassociated = [];

        foreach ($uow->getScheduledCollectionUpdates() as $collection) {
            /** @var PersistentCollection $collection */
            $owner = $collection->getOwner();

            if (null === $owner) {
                continue;
            }

            $reflectionClass = new ReflectionObject($owner);
            if (!$this->isAuditable($reflectionClass)) {
                continue;
            }

            // Ignore inverse side or one to many relations
            $mapping = $collection->getMapping();
            if (null === $mapping) {
                continue;
            }
            if (!$mapping['isOwningSide'] || $mapping['type'] !== ClassMetadataInfo::MANY_TO_MANY) {
                continue;
            }

            $ownerIdentifier = $this->getIdentifier($owner);

            foreach ($collection->getInsertDiff() as $entity) {
                $targetReflectionClass = new ReflectionObject($entity);
                if (!$this->isAuditable($targetReflectionClass)) {
                    continue;
                }

                $entityIdentifier = $this->getIdentifier($entity);
                $associated[] = [$owner, $ownerIdentifier, $entity, $entityIdentifier];
            }
            foreach ($collection->getDeleteDiff() as $entity) {
                $targetReflectionClass = new ReflectionObject($entity);
                if (!$this->isAuditable($targetReflectionClass)) {
                    continue;
                }

                $entityIdentifier = $this->getIdentifier($entity);
                $disassociated[] = [$owner, $ownerIdentifier, $entity, $entityIdentifier];
            }
        }

        foreach ($uow->getScheduledCollectionDeletions() as $collection) {
            /** @var PersistentCollection $collection */
            $owner = $collection->getOwner();

            if (null === $owner) {
                continue;
            }

            $reflectionClass = new ReflectionObject($owner);
            if (!$this->isAuditable($reflectionClass)) {
                continue;
            }

            // Ignore inverse side or one to many relations
            $mapping = $collection->getMapping();
            if (null === $mapping) {
                continue;
            }
            if (!$mapping['isOwningSide'] || $mapping['type'] !== ClassMetadataInfo::MANY_TO_MANY) {
                continue;
            }

            $ownerIdentifier = $this->getIdentifier($owner);

            foreach ($collection->toArray() as $entity) {
                $targetReflectionClass = new ReflectionObject($entity);
                if (!$this->isAuditable($targetReflectionClass)) {
                    continue;
                }

                $entityIdentifier = $this->getIdentifier($entity);
                $disassociated[] = [$owner, $ownerIdentifier, $entity, $entityIdentifier];
            }
        }

        foreach ($associated as [$owner, $ownerIdentifier, $entity, $entityIdentifier]) {
            $newRecords[] = new Entity\AuditLog(
                Entity\AuditLog::OPER_INSERT,
                get_class($owner),
                $ownerIdentifier,
                get_class($entity),
                $entityIdentifier,
                []
            );
        }

        foreach ($disassociated as [$owner, $ownerIdentifier, $entity, $entityIdentifier]) {
            $newRecords[] = new Entity\AuditLog(
                Entity\AuditLog::OPER_DELETE,
                get_class($owner),
                $ownerIdentifier,
                get_class($entity),
                $entityIdentifier,
                []
            );
        }

        return $newRecords;
    }

    protected function isEntity(EntityManagerInterface $em, mixed $class): bool
    {
        if (is_object($class)) {
            $class = ($class instanceof Proxy || $class instanceof GhostObjectInterface)
                ? get_parent_class($class)
                : get_class($class);
        }

        if (!is_string($class)) {
            return false;
        }

        if (!class_exists($class)) {
            return false;
        }

        return !$em->getMetadataFactory()->isTransient($class);
    }

    protected function isAuditable(ReflectionClass $refl): bool
    {
        $auditable = $refl->getAttributes(Auditable::class);
        return !empty($auditable);
    }

    /**
     * Get the identifier string for an entity, if it's set or fetchable.
     *
     * @param object $entity
     */
    protected function getIdentifier(object $entity): string
    {
        if ($entity instanceof Stringable) {
            return (string)$entity;
        }

        if (method_exists($entity, 'getName')) {
            return $entity->getName();
        }

        if ($entity instanceof Entity\Interfaces\IdentifiableEntityInterface) {
            $entityId = $entity->getId();
            if (null !== $entityId) {
                return (string)$entityId;
            }
        }

        return spl_object_hash($entity);
    }
}
