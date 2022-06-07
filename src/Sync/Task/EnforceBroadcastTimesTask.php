<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Radio\Adapters;
use App\Radio\AutoDJ\Scheduler;
use App\Radio\Backend\Liquidsoap;
use App\Radio\Enums\BackendAdapters;
use Psr\Log\LoggerInterface;

final class EnforceBroadcastTimesTask extends AbstractTask
{
    public function __construct(
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger,
        private readonly Scheduler $scheduler,
        private readonly Adapters $adapters,
    ) {
        parent::__construct($em, $logger);
    }

    public static function getSchedulePattern(): string
    {
        return self::SCHEDULE_EVERY_MINUTE;
    }

    public function run(bool $force = false): void
    {
        foreach ($this->iterateStations() as $station) {
            if (BackendAdapters::Liquidsoap !== $station->getBackendTypeEnum()) {
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
