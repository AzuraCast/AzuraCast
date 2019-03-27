<?php
namespace App\Radio;

use App\Entity;
use App\Event\Radio\AnnotateNextSong;
use App\Event\Radio\GetNextSong;
use App\Radio\Backend\Liquidsoap;
use Azura\EventDispatcher;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AutoDJ implements EventSubscriberInterface
{
    /** @var EntityManager */
    protected $em;

    /** @var EventDispatcher */
    protected $dispatcher;

    /** @var Filesystem */
    protected $filesystem;

    /** @var Logger */
    protected $logger;

    /**
     * @param EntityManager $em
     * @param EventDispatcher $dispatcher
     * @param Filesystem $filesystem
     * @param Logger $logger
     *
     * @see \App\Provider\RadioProvider
     */
    public function __construct(
        EntityManager $em,
        EventDispatcher $dispatcher,
        Filesystem $filesystem,
        Logger $logger)
    {
        $this->em = $em;
        $this->dispatcher = $dispatcher;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            AnnotateNextSong::NAME => [
                ['defaultAnnotationHandler', 0],
            ],
            GetNextSong::NAME => [
                ['checkDatabaseForNextSong', 10],
                ['calculateNextSong', 0],
            ],
        ];
    }

    /**
     * Pulls the next song from the AutoDJ, dispatches the AnnotateNextSong event and returns the built result.
     *
     * @param Entity\Station $station
     * @param bool $as_autodj
     * @return string
     */
    public function annotateNextSong(Entity\Station $station, $as_autodj = false): string
    {
        /** @var Entity\SongHistory|string|null $sh */
        $sh = $this->getNextSong($station, $as_autodj);

        $event = new AnnotateNextSong($station, $sh);
        $this->dispatcher->dispatch(AnnotateNextSong::NAME, $event);

        return $event->buildAnnotations();
    }

    /**
     * Event Handler function for the AnnotateNextSong event.
     *
     * @param AnnotateNextSong $event
     */
    public function defaultAnnotationHandler(AnnotateNextSong $event): void
    {
        $sh = $event->getNextSong();

        if ($sh instanceof Entity\SongHistory) {
            $media = $sh->getMedia();
            if ($media instanceof Entity\StationMedia) {
                $fs = $this->filesystem->getForStation($event->getStation());
                $media_path = $fs->getFullPath($media->getPathUri());

                $event->setSongPath($media_path);
                $event->addAnnotations($media->getAnnotations());

                $playlist = $sh->getPlaylist();

                if ($playlist instanceof Entity\StationPlaylist) {
                    $event->addAnnotations([
                        'playlist_id' => $playlist->getId(),
                    ]);

                    // Handle "Jingle mode" by sending the same metadata as the previous song.
                    if ($playlist->isJingle()) {
                        $np = $event->getStation()->getNowplaying();
                        if ($np instanceof Entity\Api\NowPlaying) {
                            $event->addAnnotations([
                                'title' => $np->now_playing->song->title,
                                'artist' => $np->now_playing->song->artist,
                                'playlist_id' => null,
                                'media_id' => null,
                                'jingle_mode' => 'true',
                            ]);
                        }
                    }
                }
            } else if (!empty($sh->getAutodjCustomUri())) {
                $custom_uri = $sh->getAutodjCustomUri();

                $event->setSongPath($custom_uri);
                if ($sh->getDuration()) {
                    $event->addAnnotations([
                        'length' => $sh->getDuration(),
                    ]);
                }
            }
        } else if (null !== $sh) {
            $event->setSongPath((string)$sh);
        }
    }

    /**
     * If the next song for a station has already been calculated, return the calculated result; otherwise,
     * calculate the next playing song.
     * 
     * @param Entity\Station $station
     * @param bool $is_autodj
     * @return Entity\SongHistory|null
     */
    public function getNextSong(Entity\Station $station, $is_autodj = false): ?Entity\SongHistory
    {
        $this->logger->pushProcessor(function($record) use ($station) {
            $record['extra']['station'] = [
                'id' => $station->getId(),
                'name' => $station->getName(),
            ];
            return $record;
        });

        $event = new GetNextSong($station);
        $this->dispatcher->dispatch(GetNextSong::NAME, $event);

        $this->logger->popProcessor();

        $next_song = $event->getNextSong();

        if ($next_song instanceof Entity\SongHistory && $is_autodj) {
            $next_song->sentToAutodj();
            $this->em->persist($next_song);

            // The "get next song" function is only called when a streamer is not live
            $station->setIsStreamerLive(false);
            $this->em->persist($station);

            $this->em->flush();
        }

        return $next_song;
    }

    public function checkDatabaseForNextSong(GetNextSong $event): void
    {
        $next_song = $this->em->createQuery(/** @lang DQL */ 'SELECT sh, s, sp, sm
            FROM App\Entity\SongHistory sh
            LEFT JOIN sh.song s 
            LEFT JOIN sh.media sm
            LEFT JOIN sh.playlist sp
            WHERE sh.station_id = :station_id
            AND sh.timestamp_cued != 0
            AND sh.sent_to_autodj = 0
            AND sh.timestamp_start = 0
            AND sh.timestamp_end = 0
            ORDER BY sh.id DESC')
            ->setParameter('station_id', $event->getStation()->getId())
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if ($next_song instanceof Entity\SongHistory) {
            $this->logger->debug('Database has a next song already registered.');
            $event->setNextSong($next_song);
        }
    }

    /**
     * Determine the next-playing song for this station based on its playlist rotation rules.
     *
     * @param GetNextSong $event
     */
    public function calculateNextSong(GetNextSong $event): void
    {
        $station = $event->getStation();

        $eligible_playlists = [];
        $general_playlists = [];
        foreach($station->getPlaylists() as $playlist) {
            /** @var Entity\StationPlaylist $playlist */
            if (Entity\StationPlaylist::TYPE_DEFAULT === $playlist->getType() && $playlist->isPlayable()) {
                $general_playlists[$playlist->getId()] = $playlist;
                $eligible_playlists[$playlist->getId()] = $playlist->getWeight();
            }
        }

        if (empty($general_playlists)) {
            return;
        }

        // Shuffle playlists by weight.
        $rand = random_int(1, (int)array_sum($eligible_playlists));
        foreach ($eligible_playlists as $playlist_id => $weight) {
            $rand -= $weight;
            if ($rand <= 0) {
                $playlist = $general_playlists[$playlist_id];

                if ($event->setNextSong($this->_playSongFromPlaylist($playlist))) {
                    return;
                }
            }
        }
    }

    /**
     * Given a specified (sequential or shuffled) playlist, choose a song from the playlist to play and return it.
     *
     * @param Entity\StationPlaylist $playlist
     * @return Entity\SongHistory|string|null
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function _playSongFromPlaylist(Entity\StationPlaylist $playlist)
    {
        /** @var Entity\Repository\StationPlaylistMediaRepository $spm_repo */
        $spm_repo = $this->em->getRepository(Entity\StationPlaylistMedia::class);

        /** @var Entity\Repository\SongRepository $song_repo */
        $song_repo = $this->em->getRepository(Entity\Song::class);

        $media_to_play = $spm_repo->getQueuedSong($playlist);

        if ($media_to_play instanceof Entity\StationMedia) {
            $spm = $media_to_play->getItemForPlaylist($playlist);
            $spm->played();

            $this->em->persist($spm);

            // Log in history
            $sh = new Entity\SongHistory($media_to_play->getSong(), $playlist->getStation());
            $sh->setPlaylist($playlist);
            $sh->setMedia($media_to_play);

            $sh->setDuration($media_to_play->getCalculatedLength());
            $sh->setTimestampCued(time());

            $this->em->persist($sh);
            $this->em->flush();

            return $sh;
        }

        if (is_array($media_to_play)) {
            [$media_uri, $media_duration] = $media_to_play;

            $sh = new Entity\SongHistory($song_repo->getOrCreate([
                'text' => 'Remote Playlist URL',
            ]), $playlist->getStation());

            $sh->setPlaylist($playlist);
            $sh->setAutodjCustomUri($media_uri);
            $sh->setDuration($media_duration);
            $sh->setTimestampCued(time());

            $this->em->persist($sh);
            $this->em->flush();

            return $sh;
        }

        return null;
    }
}
