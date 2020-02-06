<?php
namespace App\Entity\Repository;

use App\Entity;
use App\Doctrine\Repository;

class StationPlaylistScheduleRepository extends Repository
{
    public function setScheduleItems(Entity\StationPlaylist $playlist, ?array $items): void
    {
        $rawScheduleItems = $this->repository->findBy([
            'playlist' => $playlist,
        ]);

        $scheduleItems = [];
        foreach ($rawScheduleItems as $row) {
            /** @var Entity\StationPlaylistSchedule $row */
            $scheduleItems[$row->getId()] = $row;
        }

        foreach ($items as $item) {
            if (isset($item['id'], $scheduleItems[$item['id']])) {
                $record = $scheduleItems[$item['id']];
                unset($scheduleItems[$item['id']]);
            } else {
                $record = new Entity\StationPlaylistSchedule($playlist);
            }

            $record->setStartTime($item['start_time']);
            $record->setEndTime($item['end_time']);
            $record->setStartDate($item['start_date']);
            $record->setEndDate($item['end_date']);
            $record->setDays($item['days']);

            $this->em->persist($record);
        }

        foreach ($scheduleItems as $row) {
            $this->em->remove($row);
        }

        $this->em->flush();
    }
}