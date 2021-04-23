<?php

namespace App\Controller\Api\Stations\Playlists;

use App\Entity;
use App\Exception\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractPlaylistsAction
{
    public function __construct(
        protected EntityManagerInterface $em
    ) {
    }

    protected function requireRecord(Entity\Station $station, int $id): Entity\StationPlaylist
    {
        $repo = $this->em->getRepository(Entity\StationPlaylist::class);

        $record = $repo->findOneBy(
            [
                'station' => $station,
                'id' => $id,
            ]
        );

        if (!$record instanceof Entity\StationPlaylist) {
            throw new NotFoundException(__('Playlist not found.'));
        }

        return $record;
    }
}
