<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Container\EntityManagerAwareTrait;
use App\Entity\Repository\StationPlaylistRepository;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistFolder;
use App\Entity\StationPlaylistMedia;
use App\Entity\StationSchedule;
use DeepCopy;
use Doctrine\Common\Collections\Collection;

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
        bool $cloneMedia = false
    ): StationPlaylist {
        $copier = new DeepCopy\DeepCopy();
        $copier->addFilter(
            new DeepCopy\Filter\Doctrine\DoctrineProxyFilter(),
            new DeepCopy\Matcher\Doctrine\DoctrineProxyMatcher()
        );
        $copier->addFilter(
            new DeepCopy\Filter\SetNullFilter(),
            new DeepCopy\Matcher\PropertyNameMatcher('id')
        );
        $copier->addFilter(
            new DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter(),
            new DeepCopy\Matcher\PropertyTypeMatcher(Collection::class)
        );

        $copier->addFilter(
            new DeepCopy\Filter\KeepFilter(),
            new DeepCopy\Matcher\PropertyNameMatcher('station')
        );
        $copier->addFilter(
            new DeepCopy\Filter\KeepFilter(),
            new DeepCopy\Matcher\PropertyMatcher(StationPlaylistMedia::class, 'media')
        );

        /** @var StationPlaylist $newRecord */
        $newRecord = $copier->copy($record);

        $newRecord->name = $newName ?? $record->name . ' - Copy';

        $this->em->persist($newRecord);

        if ($cloneSchedules) {
            foreach ($record->schedule_items as $oldScheduleItem) {
                /** @var StationSchedule $newScheduleItem */
                $newScheduleItem = $copier->copy($oldScheduleItem);
                $newScheduleItem->playlist = $newRecord;

                $this->em->persist($newScheduleItem);
            }
        }

        if ($cloneMedia) {
            foreach ($record->folders as $oldPlaylistFolder) {
                /** @var StationPlaylistFolder $newPlaylistFolder */
                $newPlaylistFolder = $copier->copy($oldPlaylistFolder);
                $newPlaylistFolder->playlist = $newRecord;
                $this->em->persist($newPlaylistFolder);
            }

            foreach ($record->media_items as $oldMediaItem) {
                /** @var StationPlaylistMedia $newMediaItem */
                $newMediaItem = $copier->copy($oldMediaItem);

                $newMediaItem->playlist = $newRecord;
                $this->em->persist($newMediaItem);
            }
        }

        $this->em->flush();

        return $newRecord;
    }
}
