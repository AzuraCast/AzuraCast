<?php

declare(strict_types=1);

namespace App\Entity\ApiGenerator;

use App\Entity\Api\StationSchedule as StationScheduleApi;
use App\Entity\Station;
use App\Entity\StationPlaylist;
use App\Entity\StationSchedule;
use App\Entity\StationStreamer;
use App\Utilities\DateRange;
use App\Utilities\Time;
use DateTimeImmutable;

final class ScheduleApiGenerator
{
    public function __invoke(
        Station $station,
        StationSchedule $scheduleItem,
        DateRange $dateRange,
        ?DateTimeImmutable $now = null
    ): StationScheduleApi {
        $playlist = $scheduleItem->playlist;
        $streamer = $scheduleItem->streamer;

        $stationTz = $station->getTimezoneObject();
        $now = Time::nowInTimezone($stationTz, $now);

        $start = $dateRange->start;
        $end = $dateRange->end;

        $row = new StationScheduleApi();
        $row->id = $scheduleItem->id;
        $row->start_timestamp = $start->getTimestamp();
        $row->start = $start->toIso8601String();
        $row->end_timestamp = $end->getTimestamp();
        $row->end = $end->toIso8601String();
        $row->is_now = $dateRange->contains($now);

        if ($playlist instanceof StationPlaylist) {
            $row->type = StationScheduleApi::TYPE_PLAYLIST;
            $row->name = $playlist->name;
            $row->title = $row->name;
            $row->description = sprintf(__('Playlist: %s'), $row->name);
        } elseif ($streamer instanceof StationStreamer) {
            $row->type = StationScheduleApi::TYPE_STREAMER;
            $row->name = $streamer->display_name;
            $row->title = $row->name;
            $row->description = sprintf(__('Streamer: %s'), $row->name);
        }

        return $row;
    }
}
