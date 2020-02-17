<?php
namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;

class StationScheduleRepository extends Repository
{


    /**
     * @param Entity\StationPlaylist|Entity\StationStreamer $relation
     * @param array|null $items
     */
    public function setScheduleItems($relation, ?array $items): void
    {
        $rawScheduleItems = $this->findByRelation($relation);

        $scheduleItems = [];
        foreach ($rawScheduleItems as $row) {
            $scheduleItems[$row->getId()] = $row;
        }

        foreach ($items as $item) {
            if (isset($item['id'], $scheduleItems[$item['id']])) {
                $record = $scheduleItems[$item['id']];
                unset($scheduleItems[$item['id']]);
            } else {
                $record = new Entity\StationSchedule($relation);
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

    /**
     * @param Entity\StationPlaylist|Entity\StationStreamer $relation
     *
     * @return Entity\StationSchedule[]
     */
    public function findByRelation($relation): array
    {
        if ($relation instanceof Entity\StationPlaylist) {
            return $this->repository->findBy(['playlist' => $relation]);
        }
        if ($relation instanceof Entity\StationStreamer) {
            return $this->repository->findBy(['streamer' => $relation]);
        }

        throw new \InvalidArgumentException('Related entity must be a Playlist or Streamer.');
    }
}