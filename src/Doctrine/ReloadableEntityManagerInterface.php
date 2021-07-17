<?php

declare(strict_types=1);

namespace App\Doctrine;

use Doctrine\ORM\EntityManagerInterface;

interface ReloadableEntityManagerInterface extends EntityManagerInterface
{
    /**
     * Fetch a fresh instance of an entity object, even if the EntityManager has been cleared.
     *
     * phpcs:disable SlevomatCodingStandard.TypeHints.ReturnTypeHint
     *
     * @template T as object
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
     * @template U as object
     *
     * @param U $entity
     *
     * @return U
     */
    public function refetchAsReference(mixed $entity);
}
