<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Container\EntityManagerAwareTrait;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Repository\StationPlaylistRepository;
use App\Entity\StationPlaylist;

abstract class AbstractClonableAction
{
    use EntityManagerAwareTrait;

    public function __construct(
        protected readonly StationPlaylistRepository $playlistRepo
    ) {
    }

    protected function clone(
        StationPlaylist $record,
        ?string $newName = null,
        bool $cloneSchedules = true,
        bool $cloneMedia = false,
        bool $cloneMembers = false
    ): StationPlaylist {
        $this->em->detach($record);

        $newRecord = clone $record;
        $newRecord->name = $newName ?? $record->name . ' - Copy';

        $this->em->persist($newRecord);

        if ($cloneSchedules) {
            foreach ($record->schedule_items as $oldScheduleItem) {
                $this->em->detach($oldScheduleItem);

                $newScheduleItem = clone $oldScheduleItem;
                $newScheduleItem->playlist = $newRecord;

                $this->em->persist($newScheduleItem);
            }
        }

        if ($cloneMedia) {
            foreach ($record->folders as $oldPlaylistFolder) {
                $this->em->detach($oldPlaylistFolder);

                $newPlaylistFolder = clone $oldPlaylistFolder;
                $newPlaylistFolder->playlist = $newRecord;
                $this->em->persist($newPlaylistFolder);
            }

            foreach ($record->media_items as $oldMediaItem) {
                $this->em->detach($oldMediaItem);

                $newMediaItem = clone $oldMediaItem;
                $newMediaItem->playlist = $newRecord;
                $this->em->persist($newMediaItem);
            }
        }

        if ($cloneMembers && $record->source === PlaylistSources::Playlists) {
            foreach ($record->playlists as $oldMember) {
                $this->em->detach($oldMember);

                $newMember = clone $oldMember;
                $newMember->playlist_group = $newRecord;

                $this->em->persist($newMember);
            }
        }

        $this->em->flush();

        return $newRecord;
    }
}
