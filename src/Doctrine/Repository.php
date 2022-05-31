<?php

declare(strict_types=1);

namespace App\Doctrine;

use App\Exception\NotFoundException;
use Closure;
use Doctrine\Persistence\ObjectRepository;

/**
 * @template TEntity as object
 */
class Repository
{
    /** @var class-string<TEntity> */
    protected string $entityClass;

    /** @var ObjectRepository<TEntity> */
    protected ObjectRepository $repository;

    public function __construct(
        protected ReloadableEntityManagerInterface $em
    ) {
        if (!isset($this->entityClass)) {
            /** @var class-string<TEntity> $defaultClass */
            $defaultClass = str_replace(['Repository', '\\\\'], ['', '\\'], static::class);
            $this->entityClass = $defaultClass;
        }

        if (!isset($this->repository)) {
            $this->repository = $em->getRepository($this->entityClass);
        }
    }

    /**
     * @return ObjectRepository<TEntity>
     */
    public function getRepository(): ObjectRepository
    {
        return $this->repository;
    }

    public function getEntityManager(): ReloadableEntityManagerInterface
    {
        return $this->em;
    }

    /**
     * @return TEntity|null
     */
    public function find(int|string $id): ?object
    {
        return $this->em->find($this->entityClass, $id);
    }

    /**
     * @return TEntity
     */
    public function requireRecord(int|string $id): object
    {
        $record = $this->find($id);
        if (null === $record) {
            throw new NotFoundException();
        }
        return $record;
    }

    /**
     * Generate an array result of all records.
     *
     * @param bool $cached
     * @param string|null $order_by
     * @param string $order_dir
     *
     * @return mixed[]
     */
    public function fetchArray(bool $cached = true, ?string $order_by = null, string $order_dir = 'ASC'): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('e')
            ->from($this->entityClass, 'e');

        if ($order_by) {
            $qb->orderBy('e.' . str_replace('e.', '', $order_by), $order_dir);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Generic dropdown builder function (can be overridden for specialized use cases).
     *
     * @param bool|string $add_blank
     * @param Closure|NULL $display
     * @param string $pk
     * @param string $order_by
     *
     * @return mixed[]
     */
    public function fetchSelect(
        bool|string $add_blank = false,
        Closure $display = null,
        string $pk = 'id',
        string $order_by = 'name'
    ): array {
        $select = [];

        // Specify custom text in the $add_blank parameter to override.
        if ($add_blank !== false) {
            $select[''] = ($add_blank === true) ? __('Select...') : $add_blank;
        }

        // Build query for records.
        $qb = $this->em->createQueryBuilder()->from($this->entityClass, 'e');

        if ($display === null) {
            $qb->select('e.' . $pk)->addSelect('e.name')->orderBy('e.' . $order_by, 'ASC');
        } else {
            $qb->select('e')->orderBy('e.' . $order_by, 'ASC');
        }

        $results = $qb->getQuery()->getArrayResult();

        // Assemble select values and, if necessary, call $display callback.
        foreach ((array)$results as $result) {
            $key = $result[$pk];
            $select[$key] = ($display === null) ? $result['name'] : $display($result);
        }

        return $select;
    }
}
