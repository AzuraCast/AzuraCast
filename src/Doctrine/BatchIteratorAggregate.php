<?php

declare(strict_types=1);

namespace App\Doctrine;

use Closure;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use IteratorAggregate;
use RuntimeException;
use Throwable;
use Traversable;

use function get_class;
use function is_array;
use function is_object;
use function key;

/**
 * @template TKey
 * @template TValue
 * @implements \IteratorAggregate<TKey, TValue>
 */
final class BatchIteratorAggregate implements IteratorAggregate
{
    /** @var iterable<TKey, TValue> */
    private iterable $resultSet;

    private EntityManagerInterface $entityManager;

    private ?Closure $customFetchFunction = null;

    /** @psalm-var positive-int */
    private int $batchSize;

    private bool $clearMemoryWithFlush = true;

    /**
     * @psalm-param positive-int $batchSize
     */
    public static function fromQuery(
        AbstractQuery $query,
        int $batchSize
    ): self {
        return new self($query->toIterable(), $query->getEntityManager(), $batchSize);
    }

    /**
     * @param array<C, D> $results
     *
     * @return self<C, D>
     *
     * @template C
     * @template D
     * @psalm-param positive-int $batchSize
     */
    public static function fromArrayResult(
        array $results,
        EntityManagerInterface $entityManager,
        int $batchSize
    ): self {
        return new self($results, $entityManager, $batchSize);
    }

    /**
     * @param Traversable<E, F> $results
     *
     * @return self<E, F>
     *
     * @template E
     * @template F
     * @psalm-param positive-int $batchSize
     */
    public static function fromTraversableResult(
        Traversable $results,
        EntityManagerInterface $entityManager,
        int $batchSize
    ): self {
        return new self($results, $entityManager, $batchSize);
    }

    /**
     * BatchIteratorAggregate constructor (private by design: use a named constructor instead).
     *
     * @param iterable<TKey, TValue> $resultSet
     *
     * @psalm-param positive-int $batchSize
     */
    private function __construct(
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

    public function setCustomFetchFunction(?callable $customFetchFunction = null): void
    {
        $this->customFetchFunction = (null === $customFetchFunction)
            ? null
            : Closure::fromCallable($customFetchFunction);
    }

    public function setClearMemoryWithFlush(bool $clearMemoryWithFlush): void
    {
        $this->clearMemoryWithFlush = $clearMemoryWithFlush;
    }

    /**
     * @return Traversable<TKey, TValue>
     */
    public function getIterator(): iterable
    {
        $iteration = 0;
        $resultSet = $this->resultSet;

        $this->entityManager->beginTransaction();

        try {
            foreach ($resultSet as $key => $value) {
                ++$iteration;

                yield $key => $this->getObjectFromValue($value);

                $this->flushAndClearBatch($iteration);
            }
        } catch (Throwable $exception) {
            $this->entityManager->rollback();

            throw $exception;
        }

        $this->flushAndClearEntityManager();
        $this->entityManager->commit();
    }

    /**
     * @param mixed $value
     *
     */
    private function getObjectFromValue(mixed $value): mixed
    {
        if ($this->customFetchFunction instanceof Closure) {
            return ($this->customFetchFunction)($value, $this->entityManager);
        }

        if (is_array($value)) {
            $firstKey = key($value);
            if (
                $firstKey !== null && is_object(
                    $value[$firstKey]
                ) && $value === [$firstKey => $value[$firstKey]]
            ) {
                return $this->reFetchObject($value[$firstKey]);
            }
        }

        if (!is_object($value)) {
            return $value;
        }

        return $this->reFetchObject($value);
    }

    /**
     * @return object of TValue
     *
     * @psalm-assert TValue $object
     */
    private function reFetchObject(object $object): object
    {
        $metadata = $this->entityManager->getClassMetadata(get_class($object));

        /** @psalm-var class-string $classname */
        $classname = $metadata->getName();
        $freshValue = $this->entityManager->find($classname, $metadata->getIdentifierValues($object));

        if (!$freshValue) {
            throw new RuntimeException(
                sprintf(
                    'Requested batch item %s#%s (of type %s) with identifier "%s" could not be found',
                    get_class($object),
                    spl_object_hash($object),
                    $metadata->getName(),
                    json_encode($metadata->getIdentifierValues($object), JSON_THROW_ON_ERROR)
                )
            );
        }

        return $freshValue;
    }

    private function flushAndClearBatch(int $iteration): void
    {
        if ($iteration % $this->batchSize) {
            return;
        }

        $this->flushAndClearEntityManager();
    }

    private function flushAndClearEntityManager(): void
    {
        $this->entityManager->flush();
        $this->entityManager->clear();

        if ($this->clearMemoryWithFlush) {
            gc_collect_cycles();
        }
    }
}
