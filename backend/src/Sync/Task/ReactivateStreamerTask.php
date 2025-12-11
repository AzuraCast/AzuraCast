<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Entity\StationStreamer;

final class ReactivateStreamerTask extends AbstractTask
{
    public static function getSchedulePattern(): string
    {
        return self::SCHEDULE_EVERY_MINUTE;
    }

    public function run(bool $force = false): void
    {
        $streamers = $this->em->createQuery(
            <<<DQL
            SELECT sst
            FROM App\Entity\StationStreamer sst
            WHERE sst.is_active = 0
            AND sst.reactivate_at <= :reactivate_at
            DQL
        )->setParameter('reactivate_at', time())
            ->execute();

        /** @var StationStreamer $streamer */
        foreach ($streamers as $streamer) {
            $streamer->is_active = true;
            $this->em->persist($streamer);
        }

        $this->em->flush();
    }
}
