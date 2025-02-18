<?php

declare(strict_types=1);

namespace App\Entity\ApiGenerator;

use App\Entity\Api\StationSchedule as StationScheduleApi;
use App\Entity\Station;
use App\Entity\StationPlaylist;
use App\Entity\StationSchedule;
use App\Entity\StationStreamer;
use App\Utilities\Time;
use Carbon\CarbonImmutable;

final class ScheduleApiGenerator
{
    public function __invoke(
        Station $station,
        StationSchedule $scheduleItem,
        ?CarbonImmutable $start = null,
        ?CarbonImmutable $end = null,
        ?CarbonImmutable $now = null
    ): StationScheduleApi {
        $playlist = $scheduleItem->getPlaylist();
        $streamer = $scheduleItem->getStreamer();

        $stationTz = $station->getTimezoneObject();
        $now = Time::nowInTimezone($stationTz, $now);

        if (null === $start || null === $end) {
            $start = StationSchedule::getDateTime($scheduleItem->getStartTime(), $stationTz, $now);
            $end = StationSchedule::getDateTime($scheduleItem->getEndTime(), $stationTz, $now);

            // Handle overnight schedule items
            if ($end < $start) {
                $end = $end->addDay();
            }
        }

        $row = new StationScheduleApi();
        $row->id = $scheduleItem->getIdRequired();
        $row->start_timestamp = $start->getTimestamp();
        $row->start = $start->toIso8601String();
        $row->end_timestamp = $end->getTimestamp();
        $row->end = $end->toIso8601String();
        $row->is_now = ($start <= $now && $end >= $now);

        if ($playlist instanceof StationPlaylist) {
            $row->type = StationScheduleApi::TYPE_PLAYLIST;
            $row->name = $playlist->getName();
            $row->title = $row->name;
            $row->description = sprintf(__('Playlist: %s'), $row->name);
        } elseif ($streamer instanceof StationStreamer) {
            $row->type = StationScheduleApi::TYPE_STREAMER;
            $row->name = $streamer->getDisplayName();
            $row->title = $row->name;
            $row->description = sprintf(__('Streamer: %s'), $row->name);
        }

        return $row;
    }
}
