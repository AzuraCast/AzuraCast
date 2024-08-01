<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Container\EntityManagerAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Doctrine\ReadWriteBatchIteratorAggregate;
use App\Entity\Enums\StorageLocationTypes;
use App\Entity\Settings;
use App\Entity\Station;
use App\Entity\StorageLocation;
use App\Environment;
use Cron\CronExpression;
use DateTimeInterface;
use RuntimeException;

abstract class AbstractTask implements ScheduledTaskInterface
{
    use LoggerAwareTrait;
    use EntityManagerAwareTrait;

    public static function isLongTask(): bool
    {
        return false;
    }

    /**
     * The CRON-styled pattern for execution of this task.
     */
    public static function getSchedulePattern(): ?string
    {
        return null;
    }

    public static function isDue(
        DateTimeInterface $now,
        Environment $environment,
        Settings $settings
    ): bool {
        $schedulePattern = static::getSchedulePattern();
        if (null === $schedulePattern) {
            throw new RuntimeException('Schedule not defined.');
        }

        $cronExpression = new CronExpression($schedulePattern);
        return $cronExpression->isDue($now);
    }

    public static function getNextRun(
        DateTimeInterface $now,
        Environment $environment,
        Settings $settings
    ): int {
        $schedulePattern = static::getSchedulePattern();
        if (null === $schedulePattern) {
            throw new RuntimeException('Schedule not defined.');
        }

        $cronExpression = new CronExpression($schedulePattern);
        return $cronExpression->getNextRunDate($now)->getTimestamp();
    }

    abstract public function run(bool $force = false): void;

    /**
     * @return ReadWriteBatchIteratorAggregate<int, Station>
     */
    protected function iterateStations(): ReadWriteBatchIteratorAggregate
    {
        return ReadWriteBatchIteratorAggregate::fromQuery(
            $this->em->createQuery(
                <<<'DQL'
                    SELECT s FROM App\Entity\Station s
                DQL
            ),
            1
        );
    }

    /**
     * @param StorageLocationTypes $type
     *
     * @return ReadWriteBatchIteratorAggregate<int, StorageLocation>
     */
    protected function iterateStorageLocations(StorageLocationTypes $type): ReadWriteBatchIteratorAggregate
    {
        return ReadWriteBatchIteratorAggregate::fromQuery(
            $this->em->createQuery(
                <<<'DQL'
                    SELECT sl
                    FROM App\Entity\StorageLocation sl
                    WHERE sl.type = :type
                DQL
            )->setParameter('type', $type->value),
            1
        );
    }
}
