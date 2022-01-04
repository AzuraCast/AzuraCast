<?php

declare(strict_types=1);

namespace App\Sync\Task;

interface ScheduledTaskInterface
{
    public const SCHEDULE_EVERY_MINUTE = '* * * * *';
    public const SCHEDULE_EVERY_FIVE_MINUTES = '*/5 * * * *';

    /**
     * The CRON-styled pattern for execution of this task.
     *
     * @return string
     */
    public static function getSchedulePattern(): string;

    public function run(bool $force = false): void;
}
