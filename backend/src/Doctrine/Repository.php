<?php

declare(strict_types=1);

namespace App\Doctrine;

use App\Container\EntityManagerAwareTrait;
use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Exception\NotFoundException;
use Closure;
use DI\Attribute\Inject;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ObjectRepository;

/**
 * @template TEntity as object
 */
class Repository
{
    use EntityManagerAwareTrait;

    /** @var class-string<TEntity> */
    protected string $entityClass;

    /** @var ObjectRepository<TEntity> */
    protected ObjectRepository $repository;

    #[Inject]
    public function setEntityManager(ReloadableEntityManagerInterface $em): void
    {
        $this->em = $em;
        $this->repository = $em->getRepository($this->entityClass);
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
            throw NotFoundException::generic();
        }
        return $record;
    }

    /**
     * Re-hydrates managed entities in the Doctrine identity map after bulk DQL
     *
     * @template T of IdentifiableEntityInterface
     *
     * @param class-string<T> $entityClass
     * @param callable(T): bool $matcher
     */
    protected function resyncManagedEntities(string $entityClass, callable $matcher): void
    {
        $unitOfWork = $this->em->getUnitOfWork();

        $staleIds = [];

        /** @var T $entity */
        foreach ($unitOfWork->getIdentityMap()[$entityClass] ?? [] as $entity) {
            if (!$unitOfWork->isUninitializedObject($entity) && $matcher($entity)) {
                $staleIds[] = $entity->id;
            }
        }

        if ($staleIds === []) {
            return;
        }

        $this->em->createQueryBuilder()
            ->select('e')
            ->from($entityClass, 'e')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', $staleIds)
            ->getQuery()
            ->setHint(Query::HINT_REFRESH, true)
            ->execute();
    }

    /**
     * Generate an array result of all records.
     *
     * @param bool $cached
     * @param string|null $orderBy
     * @param string $orderDir
     *
     * @return mixed[]
     */
    public function fetchArray(bool $cached = true, ?string $orderBy = null, string $orderDir = 'ASC'): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('e')
            ->from($this->entityClass, 'e');

        if ($orderBy) {
            $qb->orderBy('e.' . str_replace('e.', '', $orderBy), $orderDir);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Generic dropdown builder function (can be overridden for specialized use cases).
     *
     * @param bool|string $addBlank
     * @param Closure|null $display
     * @param string $pk
     * @param string $orderBy
     *
     * @return mixed[]
     */
    public function fetchSelect(
        bool|string $addBlank = false,
        ?Closure $display = null,
        string $pk = 'id',
        string $orderBy = 'name'
    ): array {
        $select = [];

        // Specify custom text in the $add_blank parameter to override.
        if ($addBlank !== false) {
            $select[''] = ($addBlank === true) ? __('Select...') : $addBlank;
        }

        // Build query for records.
        $qb = $this->em->createQueryBuilder()->from($this->entityClass, 'e');

        if ($display === null) {
            $qb->select('e.' . $pk)->addSelect('e.name')->orderBy('e.' . $orderBy, 'ASC');
        } else {
            $qb->select('e')->orderBy('e.' . $orderBy, 'ASC');
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
