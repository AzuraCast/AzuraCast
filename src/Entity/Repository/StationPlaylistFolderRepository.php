<?php

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;

class StationPlaylistFolderRepository extends Repository
{
    /**
     * @param Entity\Station $station
     * @param Entity\StationPlaylist[] $playlists
     * @param string $path
     */
    public function setPlaylistsForFolder(
        Entity\Station $station,
        array $playlists,
        string $path
    ): void {
        if (strpos($path, '://') !== false) {
            [, $path] = explode('://', $path, 2);
        }

        $this->em->createQuery(/** @lang DQL */ 'DELETE
            FROM App\Entity\StationPlaylistFolder spf
            WHERE spf.station = :station AND spf.path = :path')
            ->setParameter('station', $station)
            ->setParameter('path', $path)
            ->execute();

        foreach ($playlists as $playlistId => $playlistRecord) {
            /** @var Entity\StationPlaylist $playlistRecord */
            if (
                Entity\StationPlaylist::ORDER_SEQUENTIAL !== $playlistRecord->getOrder()
                && Entity\StationPlaylist::SOURCE_SONGS === $playlistRecord->getSource()
            ) {
                /** @var Entity\StationPlaylist $playlist */
                $playlist = $this->em->getReference(Entity\StationPlaylist::class, $playlistId);
                
                $newRecord = new Entity\StationPlaylistFolder($station, $playlist, $path);
                $this->em->persist($newRecord);
            }
        }

        $this->em->flush();
    }
}
