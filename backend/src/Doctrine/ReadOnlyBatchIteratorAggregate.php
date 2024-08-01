<?php

declare(strict_types=1);

namespace App\Doctrine;

use Traversable;

/**
 * @template TKey
 * @template TValue
 * @extends AbstractBatchIteratorAggregate<TKey, TValue>
 */
final class ReadOnlyBatchIteratorAggregate extends AbstractBatchIteratorAggregate
{
    /** @inheritDoc */
    public function getIterator(): Traversable
    {
        $iteration = 0;
        foreach ($this->resultSet as $key => $value) {
            ++$iteration;
            yield $key => $value;

            $this->entityManager->detach($value);
            $this->clearBatch($iteration);
        }

        $this->clearMemory();
    }

    private function clearBatch(int $iteration): void
    {
        if ($iteration % $this->batchSize) {
            return;
        }

        $this->clearMemory();
    }

    private function clearMemory(): void
    {
        if ($this->clearMemoryWithFlush) {
            gc_collect_cycles();
        }
    }
}
