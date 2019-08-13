<?php
namespace App\Doctrine\Event;

use App\Annotations\AuditLog\Auditable;
use App\Annotations\AuditLog\AuditIdentifier;
use App\Annotations\AuditLog\AuditIgnore;
use App\Entity;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

/**
 * A hook into Doctrine's event listener to write changes to "Auditable"
 * entities to the audit log.
 */
class AuditLog implements EventSubscriber
{
    /** @var Reader */
    protected $reader;

    /**
     * @param Reader $reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

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

        $collections = [
            Entity\AuditLog::OPER_INSERT => $uow->getScheduledEntityInsertions(),
            Entity\AuditLog::OPER_UPDATE => $uow->getScheduledEntityUpdates(),
            Entity\AuditLog::OPER_DELETE => $uow->getScheduledEntityDeletions(),
        ];

        foreach($collections as $changeType => $collection) {
            foreach ($collection as $entity) {
                // Check that the entity being managed is "Auditable".
                $reflectionClass = new \ReflectionObject($entity);

                $auditable = $this->reader->getClassAnnotation($reflectionClass, Auditable::class);
                if (null === $auditable) {
                    continue;
                }

                // Get the class name.
                $classNameParts = explode('\\', get_class($entity));
                $className = array_pop($classNameParts);

                // Get the changes made to the entity.
                $changes = [];

                if (Entity\AuditLog::OPER_UPDATE === $changeType) {
                    $changes = $uow->getEntityChangeSet($entity);

                    // Look for the @AuditIgnore annotation on properties.
                    foreach ($changes as $change_field => $field_changes) {
                        $property = $reflectionClass->getProperty($change_field);
                        $annotation = $this->reader->getPropertyAnnotation($property, AuditIgnore::class);

                        if (null !== $annotation) {
                            unset($changes[$change_field]);
                        }
                    }

                    if (empty($changes)) {
                        continue;
                    }
                }

                // Find the identifier method or property.
                $identifier = $this->getIdentifier($reflectionClass, $entity);
                if (null === $identifier) {
                    continue;
                }

                $auditLog = new Entity\AuditLog(
                    $changeType,
                    $className,
                    $identifier,
                    null,
                    null,
                    $changes
                );

                $uow->persist($auditLog);

                $metadata = $em->getClassMetadata(Entity\AuditLog::class);
                $uow->computeChangeSet($metadata, $auditLog);
            }
        }
    }

    /**
     * Get the identifier string for an entity, if it's set or fetchable.
     *
     * @param \ReflectionClass $reflectionClass
     * @param object $entity
     * @return string|null
     */
    public function getIdentifier(\ReflectionClass $reflectionClass, $entity): ?string
    {
        foreach($reflectionClass->getMethods() as $reflectionMethod) {
            $isIdentifier = $this->reader->getMethodAnnotation($reflectionMethod, AuditIdentifier::class);

            if (null !== $isIdentifier) {
                return (string)$reflectionMethod->invoke($entity);
            }
        }

        foreach($reflectionClass->getProperties() as $reflectionProperty) {
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
