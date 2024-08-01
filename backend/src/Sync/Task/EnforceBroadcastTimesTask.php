<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Radio\Adapters;
use App\Radio\AutoDJ\Scheduler;
use App\Radio\Backend\Liquidsoap;
use App\Radio\Enums\BackendAdapters;

final class EnforceBroadcastTimesTask extends AbstractTask
{
    public function __construct(
        private readonly Scheduler $scheduler,
        private readonly Adapters $adapters,
    ) {
    }

    public static function getSchedulePattern(): string
    {
        return self::SCHEDULE_EVERY_MINUTE;
    }

    public function run(bool $force = false): void
    {
        foreach ($this->iterateStations() as $station) {
            if (BackendAdapters::Liquidsoap !== $station->getBackendType()) {
                continue;
            }

            $currentStreamer = $station->getCurrentStreamer();
            if (null === $currentStreamer) {
                continue;
            }

            if (!$this->scheduler->canStreamerStreamNow($currentStreamer)) {
                /** @var Liquidsoap $adapter */
                $adapter = $this->adapters->getBackendAdapter($station);

                $adapter->disconnectStreamer($station);
            }
        }
    }
}
