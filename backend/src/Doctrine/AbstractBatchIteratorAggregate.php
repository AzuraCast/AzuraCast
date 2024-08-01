<?php

declare(strict_types=1);

namespace App\Doctrine;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use IteratorAggregate;
use Traversable;

/**
 * @template TKey
 * @template TValue
 * @implements IteratorAggregate<TKey, TValue>
 */
abstract class AbstractBatchIteratorAggregate implements IteratorAggregate
{
    protected iterable $resultSet;

    protected EntityManagerInterface $entityManager;

    protected int $batchSize;

    protected bool $clearMemoryWithFlush = true;

    public static function fromQuery(
        AbstractQuery $query,
        int $batchSize
    ): static {
        return new static($query->toIterable(), $query->getEntityManager(), $batchSize);
    }

    public static function fromArrayResult(
        array $results,
        EntityManagerInterface $entityManager,
        int $batchSize
    ): static {
        return new static($results, $entityManager, $batchSize);
    }

    public static function fromTraversableResult(
        Traversable $results,
        EntityManagerInterface $entityManager,
        int $batchSize
    ): static {
        return new static($results, $entityManager, $batchSize);
    }

    /**
     * BatchIteratorAggregate constructor (private by design: use a named constructor instead).
     *
     * @param iterable<TKey, TValue> $resultSet
     */
    final protected function __construct(
        iterable $resultSet,
        EntityManagerInterface $entityManager,
        int $batchSize
    ) {
        $this->resultSet = $resultSet;
        $this->entityManager = $entityManager;
        $this->batchSize = $batchSize;
    }

    public function setBatchSize(int $batchSize): void
    {
        $this->batchSize = $batchSize;
    }

    public function setClearMemoryWithFlush(bool $clearMemoryWithFlush): void
    {
        $this->clearMemoryWithFlush = $clearMemoryWithFlush;
    }

    /**
     * @return Traversable<TKey, TValue>
     */
    abstract public function getIterator(): Traversable;
}
