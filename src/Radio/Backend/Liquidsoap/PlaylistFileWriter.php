<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Event\Radio\AnnotateNextSong;
use App\Event\Radio\WriteLiquidsoapConfiguration;
use App\Exception;
use App\Flysystem\StationFilesystems;
use App\Message;
use App\Radio\Backend\Liquidsoap;
use League\Flysystem\StorageAttributes;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

final class PlaylistFileWriter implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ReloadableEntityManagerInterface $em,
        private readonly Filesystem $fsUtils,
        private readonly Liquidsoap $liquidsoap,
    ) {
    }

    /**
     * Handle event dispatch.
     *
     * @param Message\AbstractMessage $message
     */
    public function __invoke(Message\AbstractMessage $message): void
    {
        if ($message instanceof Message\WritePlaylistFileMessage) {
            $playlist = $this->em->find(Entity\StationPlaylist::class, $message->playlist_id);

            if ($playlist instanceof Entity\StationPlaylist) {
                $this->writePlaylistFile($playlist);

                $playlistVarName = ConfigWriter::getPlaylistVariableName($playlist);
                $station = $playlist->getStation();

                try {
                    $this->liquidsoap->command($station, $playlistVarName . '.reload');
                } catch (Exception $e) {
                    $this->logger->error(
                        'Could not reload playlist with AutoDJ.',
                        [
                            'message' => $e->getMessage(),
                            'playlist' => $playlistVarName,
                            'station' => $station->getId(),
                        ]
                    );
                }
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WriteLiquidsoapConfiguration::class => [
                ['writePlaylistsForLiquidsoap', 32],
            ],
        ];
    }

    public function writePlaylistsForLiquidsoap(WriteLiquidsoapConfiguration $event): void
    {
        if ($event->shouldWriteToDisk()) {
            $this->writeAllPlaylistFiles($event->getStation());
        }
    }

    public function writeAllPlaylistFiles(Entity\Station $station): void
    {
        // Clear out existing playlists directory.
        $fsPlaylists = (new StationFilesystems($station))->getPlaylistsFilesystem();

        foreach ($fsPlaylists->listContents('', false) as $file) {
            /** @var StorageAttributes $file */
            if ($file->isDir()) {
                $fsPlaylists->deleteDirectory($file->path());
            } else {
                $fsPlaylists->delete($file->path());
            }
        }

        foreach ($station->getPlaylists() as $playlist) {
            if (!$playlist->getIsEnabled()) {
                continue;
            }

            $this->writePlaylistFile($playlist);
        }
    }

    private function writePlaylistFile(Entity\StationPlaylist $playlist): void
    {
        $station = $playlist->getStation();

        $this->logger->info(
            'Writing playlist file to disk...',
            [
                'station' => $station->getName(),
                'playlist' => $playlist->getName(),
            ]
        );

        $playlistFile = [];

        $mediaQuery = $this->em->createQuery(
            <<<'DQL'
                SELECT DISTINCT sm
                FROM App\Entity\StationMedia sm
                JOIN sm.playlists spm
                WHERE spm.playlist = :playlist
                ORDER BY spm.weight ASC
            DQL
        )->setParameter('playlist', $playlist);

        /** @var Entity\StationMedia $mediaFile */
        foreach ($mediaQuery->toIterable() as $mediaFile) {
            $event = new AnnotateNextSong(
                station: $station,
                media: $mediaFile,
                playlist: $playlist,
                asAutoDj: false
            );

            try {
                $this->eventDispatcher->dispatch($event);
                $playlistFile[] = $event->buildAnnotations();
            } catch (Throwable) {
            }
        }

        $playlistFilePath = self::getPlaylistFilePath($playlist);
        $this->fsUtils->dumpFile(
            $playlistFilePath,
            implode("\n", $playlistFile)
        );
    }

    public static function getPlaylistFilePath(Entity\StationPlaylist $playlist): string
    {
        return $playlist->getStation()->getRadioPlaylistsDir() . '/'
            . ConfigWriter::getPlaylistVariableName($playlist) . '.m3u';
    }
}
