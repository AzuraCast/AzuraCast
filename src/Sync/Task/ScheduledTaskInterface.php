<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Entity\Settings;
use App\Environment;
use DateTimeInterface;

interface ScheduledTaskInterface
{
    public const SCHEDULE_EVERY_MINUTE = '* * * * *';
    public const SCHEDULE_EVERY_FIVE_MINUTES = '*/5 * * * *';

    public static function isDue(
        DateTimeInterface $now,
        Environment $environment,
        Settings $settings
    ): bool;

    public static function getNextRun(
        DateTimeInterface $now,
        Environment $environment,
        Settings $settings
    ): int;

    /**
     * The CRON-styled pattern for execution of this task.
     */
    public static function getSchedulePattern(): ?string;

    /**
     * @return bool Whether the task is considered a long-running task.
     */
    public static function isLongTask(): bool;

    public function run(bool $force = false): void;
}
