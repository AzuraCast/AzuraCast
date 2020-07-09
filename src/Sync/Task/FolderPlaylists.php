<?php
namespace App\Sync\Task;

use App\Entity;
use App\Flysystem\Filesystem;
use Doctrine\ORM\EntityManagerInterface;
use DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate;
use Psr\Log\LoggerInterface;

class FolderPlaylists extends AbstractTask
{
    protected Entity\Repository\StationPlaylistFolderRepository $folderRepo;

    protected Entity\Repository\StationPlaylistMediaRepository $spmRepo;

    protected Filesystem $filesystem;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        LoggerInterface $logger,
        Entity\Repository\StationPlaylistMediaRepository $spmRepo,
        Entity\Repository\StationPlaylistFolderRepository $folderRepo,
        Filesystem $filesystem
    ) {
        parent::__construct($em, $settingsRepo, $logger);

        $this->spmRepo = $spmRepo;
        $this->folderRepo = $folderRepo;
        $this->filesystem = $filesystem;
    }

    public function run(bool $force = false): void
    {
        $stations = SimpleBatchIteratorAggregate::fromQuery(
            $this->em->createQuery(/** @lang DQL */ 'SELECT s FROM App\Entity\Station s'),
            1
        );

        foreach ($stations as $station) {
            /** @var Entity\Station $station */

            $this->logger->info('Processing auto-assigning folders for station...', [
                'station' => $station->getName(),
            ]);

            $this->syncPlaylistFolders($station);
            gc_collect_cycles();
        }
    }

    public function syncPlaylistFolders(Entity\Station $station): void
    {
        $folderPlaylists = $this->em->createQuery(/** @lang DQL */ 'SELECT 
            spf, sp
            FROM App\Entity\StationPlaylistFolder spf
            JOIN spf.playlist sp
            WHERE spf.station = :station')
            ->setParameter('station', $station)
            ->execute();

        $folders = [];

        $fs = $this->filesystem->getForStation($station);

        foreach ($folderPlaylists as $row) {
            /** @var Entity\StationPlaylistFolder $row */
            $path = $row->getPath();

            if ($fs->has(Filesystem::PREFIX_MEDIA . '://' . $path)) {
                $folders[$path][] = $row->getPlaylist();
            } else {
                $this->em->remove($row);
            }
        }

        $this->em->flush();

        $mediaInFolderQuery = $this->em->createQuery(/** @lang DQL */ 'SELECT 
            sm
            FROM App\Entity\StationMedia sm
            WHERE sm.station = :station
            AND sm.path LIKE :path')
            ->setParameter('station', $station);

        foreach ($folders as $path => $playlists) {
            $mediaInFolder = $mediaInFolderQuery->setParameter('path', $path . '/%')
                ->execute();

            foreach ($mediaInFolder as $media) {
                foreach ($playlists as $playlist) {
                    /** @var Entity\StationMedia $media */
                    /** @var Entity\StationPlaylist $playlist */

                    if (Entity\StationPlaylist::ORDER_SEQUENTIAL !== $playlist->getOrder()
                        && Entity\StationPlaylist::SOURCE_SONGS === $playlist->getSource()) {
                        $this->spmRepo->addMediaToPlaylist($media, $playlist);
                    }
                }
            }
        }

        $this->em->flush();
        $this->em->clear();
    }
}