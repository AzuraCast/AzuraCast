<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity\Api\Status;
use App\Entity\Repository\StationPlaylistRepository;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistFolder;
use App\Entity\StationPlaylistMedia;
use App\Entity\StationSchedule;
use App\Http\Response;
use App\Http\ServerRequest;
use DeepCopy;
use Doctrine\Common\Collections\Collection;
use Psr\Http\Message\ResponseInterface;

final class CloneAction
{
    public function __construct(
        private readonly StationPlaylistRepository $playlistRepo,
        private readonly ReloadableEntityManagerInterface $em,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $id
    ): ResponseInterface {
        $record = $this->playlistRepo->requireForStation($id, $request->getStation());

        $data = (array)$request->getParsedBody();

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

        $newRecord->setName($data['name'] ?? ($record->getName() . ' - Copy'));

        $this->em->persist($newRecord);

        $toClone = $data['clone'] ?? [];

        if (in_array('schedule', $toClone, true)) {
            foreach ($record->getScheduleItems() as $oldScheduleItem) {
                /** @var StationSchedule $newScheduleItem */
                $newScheduleItem = $copier->copy($oldScheduleItem);
                $newScheduleItem->setPlaylist($newRecord);

                $this->em->persist($newScheduleItem);
            }
        }

        if (in_array('media', $toClone, true)) {
            foreach ($record->getFolders() as $oldPlaylistFolder) {
                /** @var StationPlaylistFolder $newPlaylistFolder */
                $newPlaylistFolder = $copier->copy($oldPlaylistFolder);
                $newPlaylistFolder->setPlaylist($newRecord);
                $this->em->persist($newPlaylistFolder);
            }

            foreach ($record->getMediaItems() as $oldMediaItem) {
                /** @var StationPlaylistMedia $newMediaItem */
                $newMediaItem = $copier->copy($oldMediaItem);

                $newMediaItem->setPlaylist($newRecord);
                $this->em->persist($newMediaItem);
            }
        }

        $this->em->flush();

        return $response->withJson(Status::created());
    }
}
