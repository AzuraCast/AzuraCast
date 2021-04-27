<?php

namespace App\Doctrine;

use Doctrine\ORM\EntityManagerInterface;

/**
 * @template T as object The type of the entity being refetched.
 */
interface ReloadableEntityManagerInterface extends EntityManagerInterface
{
    /**
     * Fetch a fresh instance of an entity object, even if the EntityManager has been cleared.
     *
     * phpcs:disable SlevomatCodingStandard.TypeHints.ReturnTypeHint
     *
     * @param T $entity
     *
     * @return T
     */
    public function refetch(mixed $entity);

    /**
     * Fetch a fresh reference to an entity object, even if the EntityManager has been cleared.
     *
     * phpcs:disable SlevomatCodingStandard.TypeHints.ReturnTypeHint
     *
     * @param T $entity
     *
     * @return T
     */
    public function refetchAsReference(mixed $entity);
}
