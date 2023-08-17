<?php

declare(strict_types=1);

namespace App\Entity\ApiGenerator;

use App\Entity\Api\StationSchedule as StationScheduleApi;
use App\Entity\StationPlaylist;
use App\Entity\StationSchedule;
use App\Entity\StationStreamer;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final class ScheduleApiGenerator
{
    public function __invoke(
        StationSchedule $scheduleItem,
        ?CarbonInterface $start,
        ?CarbonInterface $end,
        ?CarbonInterface $now
    ): StationScheduleApi {
        $playlist = $scheduleItem->getPlaylist();
        $streamer = $scheduleItem->getStreamer();

        if (null === $now) {
            if (null !== $playlist) {
                $station = $playlist->getStation();
            } elseif (null !== $streamer) {
                $station = $streamer->getStation();
            } else {
                $station = null;
            }

            $now = CarbonImmutable::now($station?->getTimezoneObject());
        }

        if (null === $start || null === $end) {
            $start = StationSchedule::getDateTime($scheduleItem->getStartTime(), $now);
            $end = StationSchedule::getDateTime($scheduleItem->getEndTime(), $now);

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
