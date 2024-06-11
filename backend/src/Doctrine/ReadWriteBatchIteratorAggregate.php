<?php

declare(strict_types=1);

namespace App\Doctrine;

use Closure;
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
 * @extends AbstractBatchIteratorAggregate<TKey, TValue>
 */
final class ReadWriteBatchIteratorAggregate extends AbstractBatchIteratorAggregate
{
    private ?Closure $customFetchFunction = null;

    public function setCustomFetchFunction(?callable $customFetchFunction = null): void
    {
        $this->customFetchFunction = (null === $customFetchFunction)
            ? null
            : $customFetchFunction(...);
    }

    /** @inheritDoc */
    public function getIterator(): Traversable
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
