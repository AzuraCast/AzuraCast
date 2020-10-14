<?php

namespace App\Doctrine;

use Closure;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use InvalidArgumentException;

class DecoratedEntityManager extends EntityManagerDecorator
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
     * Fetch a new, managed instance of an entity object, even if the EntityManager has been cleared.
     *
     * @template T as object The type of the entity being refetched.
     *
     * phpcs:disable SlevomatCodingStandard.TypeHints.ReturnTypeHint
     * @param T $entity
     *
     * @return T
     */
    public function refetch($entity)
    {
        // phpcs:enable
        $metadata = $this->wrapped->getClassMetadata(get_class($entity));

        /** @var T $freshValue */
        $freshValue = $this->wrapped->find($metadata->getName(), $metadata->getIdentifierValues($entity));

        if (!$freshValue) {
            throw new InvalidArgumentException(
                sprintf('Object of class %s cannot be refetched.', get_class($entity))
            );
        }

        return $freshValue;
    }
}
