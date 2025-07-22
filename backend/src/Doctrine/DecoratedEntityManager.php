<?php

declare(strict_types=1);

namespace App\Doctrine;

use Closure;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\ORMInvalidArgumentException;

final class DecoratedEntityManager extends EntityManagerDecorator implements ReloadableEntityManagerInterface
{
    private Closure $createEm;

    public function __construct(callable $createEm)
    {
        parent::__construct($createEm());

        $this->createEm = $createEm(...);
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
     * @inheritDoc
     *
     * @template TEntity as object
     *
     * @param TEntity $entity
     *
     * @return TEntity
     */
    public function refetch(object $entity): object
    {
        // phpcs:enable
        $metadata = $this->wrapped->getClassMetadata(get_class($entity));

        /** @var TEntity|null $freshValue */
        $freshValue = $this->wrapped->find($metadata->getName(), $metadata->getIdentifierValues($entity));
        if (!$freshValue) {
            throw ORMInvalidArgumentException::entityHasNoIdentity($entity, 'refetch');
        }

        return $freshValue;
    }

    /**
     * @inheritDoc
     *
     * @template TEntity as object
     *
     * @param TEntity $entity
     *
     * @return TEntity
     */
    public function refetchAsReference(object $entity): object
    {
        // phpcs:enable
        $metadata = $this->wrapped->getClassMetadata(get_class($entity));

        /** @var TEntity|null $freshValue */
        $freshValue = $this->wrapped->getReference($metadata->getName(), $metadata->getIdentifierValues($entity));
        if (!$freshValue) {
            throw ORMInvalidArgumentException::entityHasNoIdentity($entity, 'refetch');
        }

        return $freshValue;
    }
}
