<?php
namespace App\Entity\Repository;

use App\Entity;
use App\Doctrine\Repository;

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
            [$path_prefix, $path] = explode('://', $path, 2);
        }

        $clearExisting = $this->em->createQuery(/** @lang DQL */ 'DELETE
            FROM App\Entity\StationPlaylistFolder spf 
            WHERE spf.station = :station AND spf.path = :path')
            ->setParameter('station', $station)
            ->setParameter('path', $path)
            ->execute();

        foreach ($playlists as $playlist) {
            $newRecord = new Entity\StationPlaylistFolder($station, $playlist, $path);
            $this->em->persist($newRecord);
        }

        $this->em->flush();
    }
}