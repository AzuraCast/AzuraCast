<?php

namespace App\Doctrine;

use Closure;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\ORMInvalidArgumentException;

class DecoratedEntityManager extends EntityManagerDecorator implements ReloadableEntityManagerInterface
{
    protected Closure $createEm;

    public function __construct(callable $createEm)
    {
        parent::__construct($createEm());
        $this->createEm = Closure::fromCallable($createEm);
    }

    /**
     * Recreate the underlying EntityManager if it was closed due to a previous exception.
     */
    public function open(): void
    {
        if (!$this->wrapped->isOpen()) {
            $this->wrapped = ($this->createEm)();
        }
    }

    /**
     * Preventing a situation where duplicate rows are created.
     * @see https://github.com/doctrine/orm/issues/8007
     *
     * @inheritDoc
     */
    public function persist($object): void
    {
        if (is_callable([$object, 'getId'])) {
            $oldId = $object->getId();
            $this->wrapped->persist($object);

            if (null !== $oldId && $oldId !== $object->getId()) {
                throw ORMInvalidArgumentException::detachedEntityCannot($object, 'persisted - ID changed by Doctrine');
            }
        } else {
            $this->wrapped->persist($object);
        }
    }

    /**
     * @inheritDoc
     */
    public function refetch(mixed $entity)
    {
        // phpcs:enable
        $metadata = $this->wrapped->getClassMetadata(get_class($entity));

        $freshValue = $this->wrapped->find($metadata->getName(), $metadata->getIdentifierValues($entity));
        if (!$freshValue) {
            throw ORMInvalidArgumentException::entityHasNoIdentity($entity, 'refetch');
        }

        return $freshValue;
    }

    /**
     * @inheritDoc
     */
    public function refetchAsReference(mixed $entity)
    {
        // phpcs:enable
        $metadata = $this->wrapped->getClassMetadata(get_class($entity));

        $freshValue = $this->wrapped->getReference($metadata->getName(), $metadata->getIdentifierValues($entity));
        if (!$freshValue) {
            throw ORMInvalidArgumentException::entityHasNoIdentity($entity, 'refetch');
        }

        return $freshValue;
    }
}
