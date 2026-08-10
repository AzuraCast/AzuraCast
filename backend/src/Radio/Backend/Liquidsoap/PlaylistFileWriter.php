<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap;

use App\Container\EntityManagerAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Doctrine\ReadOnlyBatchIteratorAggregate;
use App\Entity\Enums\PlaylistOrders;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Entity\StationQueue;
use App\Event\Radio\AnnotateNextSong;
use App\Event\Radio\WriteLiquidsoapConfiguration;
use App\Exception;
use App\Flysystem\StationFilesystems;
use App\Message;
use App\Radio\Backend\Liquidsoap;
use League\Flysystem\StorageAttributes;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

final class PlaylistFileWriter implements EventSubscriberInterface
{
    use LoggerAwareTrait;
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Filesystem $fsUtils,
        private readonly Liquidsoap $liquidsoap
    ) {
    }

    /**
     * Handle event dispatch.
     *
     * @param Message\AbstractMessage $message
     */
    public function __invoke(Message\AbstractMessage $message): void
    {
        if (!($message instanceof Message\WritePlaylistFileMessage)) {
            return;
        }

        $playlist = $this->em->find(StationPlaylist::class, $message->playlist_id);
        if (!($playlist instanceof StationPlaylist)) {
            return;
        }

        $station = $playlist->station;
        if (!$station->backend_type->isEnabled()) {
            return;
        }

        $this->writePlaylistFile($playlist);

        $playlistVarName = ConfigWriter::getPlaylistVariableName($playlist);

        try {
            $this->liquidsoap->command($station, $playlistVarName . '.reload');
        } catch (Exception $e) {
            $this->logger->error(
                'Could not reload playlist with AutoDJ.',
                [
                    'message' => $e->getMessage(),
                    'playlist' => $playlistVarName,
                    'station' => $station->id,
                ]
            );
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

    public function writeAllPlaylistFiles(Station $station): void
    {
        // Clear out existing playlists directory.
        $fsPlaylists = StationFilesystems::buildPlaylistsFilesystem($station);

        foreach ($fsPlaylists->listContents('', false) as $file) {
            /** @var StorageAttributes $file */
            if ($file->isDir()) {
                $fsPlaylists->deleteDirectory($file->path());
            } else {
                $fsPlaylists->delete($file->path());
            }
        }

        foreach ($station->playlists as $playlist) {
            if (!$playlist->is_enabled) {
                continue;
            }

            $this->writePlaylistFile($playlist);
        }
    }

    private function writePlaylistFile(StationPlaylist $playlist): void
    {
        $station = $playlist->station;

        $this->logger->info(
            'Writing playlist file to disk...',
            [
                'station' => $station->name,
                'playlist' => $playlist->name,
            ]
        );

        $playlistFile = [];

        $queuedMediaIds = [];
        if (PlaylistOrders::Sequential === $playlist->order) {
            $queuedMediaQuery = $this->em->createQuery(
                <<<'DQL'
                    SELECT sq, sm
                    FROM App\Entity\StationQueue sq
                    JOIN sq.media sm
                    WHERE sq.playlist = :playlist
                    AND sq.is_played = 0
                    ORDER BY sq.sent_to_autodj DESC, sq.timestamp_cued ASC
                DQL
            )->setParameter('playlist', $playlist);

            /** @var StationQueue $queueRow */
            foreach (ReadOnlyBatchIteratorAggregate::fromQuery($queuedMediaQuery, 1000) as $queueRow) {
                $mediaFile = $queueRow->media;
                if (!$mediaFile instanceof StationMedia) {
                    continue;
                }

                $queuedMediaIds[] = $mediaFile->id;
                $this->appendPlaylistFileEntry($playlistFile, $station, $playlist, $mediaFile);
            }
        }

        $playlistMediaOrder = (PlaylistOrders::Sequential === $playlist->order)
            ? 'spm.is_queued DESC, spm.weight ASC'
            : 'spm.weight ASC';

        $mediaQuery = $this->em->createQuery(
            <<<DQL
                SELECT DISTINCT sm
                FROM App\Entity\StationMedia sm
                JOIN sm.playlists spm
                WHERE spm.playlist = :playlist
                ORDER BY {$playlistMediaOrder}
            DQL
        )->setParameter('playlist', $playlist);

        /** @var StationMedia $mediaFile */
        foreach (ReadOnlyBatchIteratorAggregate::fromQuery($mediaQuery, 1000) as $mediaFile) {
            if (in_array($mediaFile->id, $queuedMediaIds, true)) {
                continue;
            }

            $this->appendPlaylistFileEntry($playlistFile, $station, $playlist, $mediaFile);
        }

        $playlistFilePath = self::getPlaylistFilePath($playlist);
        $this->fsUtils->dumpFile(
            $playlistFilePath,
            implode("\n", $playlistFile)
        );
    }

    /**
     * @param string[] $playlistFile
     */
    private function appendPlaylistFileEntry(
        array &$playlistFile,
        Station $station,
        StationPlaylist $playlist,
        StationMedia $mediaFile
    ): void {
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

    public static function getPlaylistFilePath(StationPlaylist $playlist): string
    {
        return $playlist->station->getRadioPlaylistsDir() . '/'
            . ConfigWriter::getPlaylistVariableName($playlist) . '.m3u';
    }
}
