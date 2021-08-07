<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use DeepCopy;
use Doctrine\Common\Collections\Collection;
use Psr\Http\Message\ResponseInterface;

class CloneAction extends AbstractPlaylistsAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        int $id
    ): ResponseInterface {
        $record = $this->requireRecord($request->getStation(), $id);

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
            new DeepCopy\Matcher\PropertyMatcher(Entity\StationPlaylistMedia::class, 'media')
        );

        /** @var Entity\StationPlaylist $newRecord */
        $newRecord = $copier->copy($record);

        $newRecord->setName($data['name'] ?? ($record->getName() . ' - Copy'));

        $this->em->persist($newRecord);

        $toClone = $data['clone'] ?? [];

        if (in_array('schedule', $toClone, true)) {
            foreach ($record->getScheduleItems() as $oldScheduleItem) {
                /** @var Entity\StationSchedule $newScheduleItem */
                $newScheduleItem = $copier->copy($oldScheduleItem);
                $newScheduleItem->setPlaylist($newRecord);

                $this->em->persist($newScheduleItem);
            }
        }

        if (in_array('media', $toClone, true)) {
            foreach ($record->getFolders() as $oldPlaylistFolder) {
                /** @var Entity\StationPlaylistFolder $newPlaylistFolder */
                $newPlaylistFolder = $copier->copy($oldPlaylistFolder);
                $newPlaylistFolder->setPlaylist($newRecord);
                $this->em->persist($newPlaylistFolder);
            }

            foreach ($record->getMediaItems() as $oldMediaItem) {
                /** @var Entity\StationPlaylistMedia $newMediaItem */
                $newMediaItem = $copier->copy($oldMediaItem);

                $newMediaItem->setPlaylist($newRecord);
                $this->em->persist($newMediaItem);
            }
        }

        $this->em->flush();

        return $response->withJson(new Entity\Api\Status());
    }
}
