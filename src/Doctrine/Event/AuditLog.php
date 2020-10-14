<?php

namespace App\Doctrine\Event;

use App\Annotations\AuditLog\Auditable;
use App\Annotations\AuditLog\AuditIdentifier;
use App\Annotations\AuditLog\AuditIgnore;
use App\Entity;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Proxy\Proxy;
use ProxyManager\Proxy\GhostObjectInterface;
use ReflectionClass;
use ReflectionObject;

/**
 * A hook into Doctrine's event listener to write changes to "Auditable"
 * entities to the audit log.
 *
 * Portions inspired by DataDog's audit bundle for Doctrine:
 * https://github.com/DATA-DOG/DataDogAuditBundle/blob/master/src/DataDog/AuditBundle/EventSubscriber/AuditSubscriber.php
 */
class AuditLog implements EventSubscriber
{
    protected Reader $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

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
        $newAuditLogs = [];

        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        $collections = [
            Entity\AuditLog::OPER_INSERT => $uow->getScheduledEntityInsertions(),
            Entity\AuditLog::OPER_UPDATE => $uow->getScheduledEntityUpdates(),
            Entity\AuditLog::OPER_DELETE => $uow->getScheduledEntityDeletions(),
        ];

        foreach ($collections as $changeType => $collection) {
            foreach ($collection as $entity) {
                // Check that the entity being managed is "Auditable".
                $reflectionClass = new ReflectionObject($entity);

                $auditable = $this->reader->getClassAnnotation($reflectionClass, Auditable::class);
                if (null === $auditable) {
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
                    $property = $reflectionClass->getProperty($changeField);
                    $annotation = $this->reader->getPropertyAnnotation($property, AuditIgnore::class);

                    if (null !== $annotation) {
                        continue;
                    }

                    // Check if either field value is an object.
                    if ($this->isEntity($em, $fieldPrev)) {
                        $fieldPrev = $this->getIdentifier(new ReflectionObject($fieldPrev), $fieldPrev);
                    }
                    if ($this->isEntity($em, $fieldNow)) {
                        $fieldNow = $this->getIdentifier(new ReflectionObject($fieldNow), $fieldNow);
                    }

                    $changes[$changeField] = [$fieldPrev, $fieldNow];
                }

                if (Entity\AuditLog::OPER_UPDATE === $changeType && empty($changes)) {
                    continue;
                }

                // Find the identifier method or property.
                $identifier = $this->getIdentifier($reflectionClass, $entity);
                if (null === $identifier) {
                    continue;
                }

                $newAuditLogs[] = new Entity\AuditLog(
                    $changeType,
                    get_class($entity),
                    $identifier,
                    null,
                    null,
                    $changes
                );
            }
        }

        // Handle changes to collections.
        $associated = [];
        $disassociated = [];

        foreach ($uow->getScheduledCollectionUpdates() as $collection) {
            /** @var PersistentCollection $collection */
            $owner = $collection->getOwner();

            $reflectionClass = new ReflectionObject($owner);
            $isAuditable = $this->reader->getClassAnnotation($reflectionClass, Auditable::class);
            if (null === $isAuditable) {
                continue;
            }

            // Ignore inverse side or one to many relations
            $mapping = $collection->getMapping();
            if (!$mapping['isOwningSide'] || $mapping['type'] !== ClassMetadataInfo::MANY_TO_MANY) {
                continue;
            }

            $ownerIdentifier = $this->getIdentifier($reflectionClass, $owner);

            foreach ($collection->getInsertDiff() as $entity) {
                $targetReflectionClass = new ReflectionObject($entity);
                $targetIsAuditable = $this->reader->getClassAnnotation($targetReflectionClass, Auditable::class);
                if (null === $targetIsAuditable) {
                    continue;
                }

                $entityIdentifier = $this->getIdentifier($targetReflectionClass, $entity);
                $associated[] = [$owner, $ownerIdentifier, $entity, $entityIdentifier];
            }
            foreach ($collection->getDeleteDiff() as $entity) {
                $targetReflectionClass = new ReflectionObject($entity);
                $targetIsAuditable = $this->reader->getClassAnnotation($targetReflectionClass, Auditable::class);
                if (null === $targetIsAuditable) {
                    continue;
                }

                $entityIdentifier = $this->getIdentifier($targetReflectionClass, $entity);
                $disassociated[] = [$owner, $ownerIdentifier, $entity, $entityIdentifier];
            }
        }

        foreach ($uow->getScheduledCollectionDeletions() as $collection) {
            /** @var PersistentCollection $collection */
            $owner = $collection->getOwner();

            $reflectionClass = new ReflectionObject($owner);
            $isAuditable = $this->reader->getClassAnnotation($reflectionClass, Auditable::class);
            if (null === $isAuditable) {
                continue;
            }

            // Ignore inverse side or one to many relations
            $mapping = $collection->getMapping();
            if (!$mapping['isOwningSide'] || $mapping['type'] !== ClassMetadataInfo::MANY_TO_MANY) {
                continue;
            }

            $ownerIdentifier = $this->getIdentifier($reflectionClass, $owner);

            foreach ($collection->toArray() as $entity) {
                $targetReflectionClass = new ReflectionObject($entity);
                $targetIsAuditable = $this->reader->getClassAnnotation($targetReflectionClass, Auditable::class);
                if (null === $targetIsAuditable) {
                    continue;
                }

                $entityIdentifier = $this->getIdentifier($targetReflectionClass, $entity);
                $disassociated[] = [$owner, $ownerIdentifier, $entity, $entityIdentifier];
            }
        }

        foreach ($associated as [$owner, $ownerIdentifier, $entity, $entityIdentifier]) {
            $newAuditLogs[] = new Entity\AuditLog(
                Entity\AuditLog::OPER_INSERT,
                get_class($owner),
                $ownerIdentifier,
                get_class($entity),
                $entityIdentifier,
                []
            );
        }

        foreach ($disassociated as [$owner, $ownerIdentifier, $entity, $entityIdentifier]) {
            $newAuditLogs[] = new Entity\AuditLog(
                Entity\AuditLog::OPER_DELETE,
                get_class($owner),
                $ownerIdentifier,
                get_class($entity),
                $entityIdentifier,
                []
            );
        }

        $auditLogMetadata = $em->getClassMetadata(Entity\AuditLog::class);
        foreach ($newAuditLogs as $auditLog) {
            $uow->persist($auditLog);
            $uow->computeChangeSet($auditLogMetadata, $auditLog);
        }
    }

    /**
     * @param EntityManagerInterface $em
     * @param object|string $class
     */
    protected function isEntity(EntityManagerInterface $em, $class): bool
    {
        if (is_object($class)) {
            $class = ($class instanceof Proxy || $class instanceof GhostObjectInterface)
                ? get_parent_class($class)
                : get_class($class);
        } elseif (!is_string($class)) {
            return false;
        }

        if (!class_exists($class)) {
            return false;
        }

        return !$em->getMetadataFactory()->isTransient($class);
    }

    /**
     * Get the identifier string for an entity, if it's set or fetchable.
     *
     * @param ReflectionClass $reflectionClass
     * @param object $entity
     */
    protected function getIdentifier(ReflectionClass $reflectionClass, $entity): ?string
    {
        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            $isIdentifier = $this->reader->getMethodAnnotation($reflectionMethod, AuditIdentifier::class);

            if (null !== $isIdentifier) {
                return (string)$reflectionMethod->invoke($entity);
            }
        }

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $isIdentifier = $this->reader->getPropertyAnnotation($reflectionProperty, AuditIdentifier::class);

            if (null !== $isIdentifier) {
                return $reflectionProperty->getValue($entity);
            }
        }

        if (method_exists($entity, 'getName')) {
            return $entity->getName();
        }

        return null;
    }
}
