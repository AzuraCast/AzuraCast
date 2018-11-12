<?php
namespace App\Radio;

use App\Entity;
use App\Event\Radio\GetNextSong;
use Azura\EventDispatcher;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AutoDJ implements EventSubscriberInterface
{
    /** @var EntityManager */
    protected $em;

    /** @var EventDispatcher */
    protected $dispatcher;

    /**
     * @param EntityManager $em
     * @param EventDispatcher $dispatcher
     *
     * @see \App\Provider\RadioProvider
     */
    public function __construct(EntityManager $em, EventDispatcher $dispatcher)
    {
        $this->em = $em;
        $this->dispatcher = $dispatcher;
    }

    public static function getSubscribedEvents()
    {
        return [
            GetNextSong::NAME => [
                ['checkDatabaseForNextSong', 10],
                ['getNextSongFromRequests', 5],
                ['calculateNextSong', 0],
            ],
        ];
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
        if ($station->useManualAutoDJ()) {
            return null;
        }

        $event = new GetNextSong($station);
        $this->dispatcher->dispatch(GetNextSong::NAME, $event);

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

    public function checkDatabaseForNextSong(GetNextSong $event)
    {
        $next_song = $this->em->createQuery('SELECT sh, s, sm
            FROM ' . Entity\SongHistory::class . ' sh JOIN sh.song s JOIN sh.media sm
            WHERE sh.station_id = :station_id
            AND sh.timestamp_cued >= :threshold
            AND sh.sent_to_autodj = 0
            AND sh.timestamp_start = 0
            AND sh.timestamp_end = 0
            ORDER BY sh.id DESC')
            ->setParameter('station_id', $event->getStation()->getId())
            ->setParameter('threshold', time() - 60 * 15)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if ($next_song instanceof Entity\SongHistory) {
            $event->setNextSong($next_song);
        }
    }

    public function getNextSongFromRequests(GetNextSong $event)
    {
        $station = $event->getStation();

        // Process requests first (if applicable)
        if ($station->getEnableRequests()) {

            $min_minutes = (int)$station->getRequestDelay();
            $threshold_minutes = $min_minutes + mt_rand(0, $min_minutes);

            $threshold = time() - ($threshold_minutes * 60);

            // Look up all requests that have at least waited as long as the threshold.
            $request = $this->em->createQuery('SELECT sr, sm 
                FROM '.Entity\StationRequest::class.' sr JOIN sr.track sm
                WHERE sr.played_at = 0 AND sr.station_id = :station_id AND sr.timestamp <= :threshold
                ORDER BY sr.id ASC')
                ->setParameter('station_id', $station->getId())
                ->setParameter('threshold', $threshold)
                ->setMaxResults(1)
                ->getOneOrNullResult();

            if ($request instanceof Entity\StationRequest) {
                $event->setNextSong($this->_playSongFromRequest($request));
            }
        }
    }

    /**
     * Given a StationRequest object, create a new SongHistory entry that cues the requested song to play next.
     *
     * @param Entity\StationRequest $request
     * @return Entity\SongHistory
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function _playSongFromRequest(Entity\StationRequest $request)
    {
        // Log in history
        $sh = new Entity\SongHistory($request->getTrack()->getSong(), $request->getStation());
        $sh->setRequest($request);
        $sh->setMedia($request->getTrack());

        $sh->setDuration($request->getTrack()->getCalculatedLength());
        $sh->setTimestampCued(time());
        $this->em->persist($sh);

        $request->setPlayedAt(time());
        $this->em->persist($request);

        $this->em->flush();

        return $sh;
    }

    /**
     * Determine the next-playing song for this station based on its playlist rotation rules.
     *
     * @param GetNextSong $event
     */
    public function calculateNextSong(GetNextSong $event): void
    {
        $station = $event->getStation();

        // Pull all active, non-empty playlists and sort by type.
        $playlists_by_type = [];
        foreach($station->getPlaylists() as $playlist) {
            /** @var Entity\StationPlaylist $playlist */

            // Don't include empty playlists or nonstandard ones
            if ($playlist->getIsEnabled()
                && $playlist->getSource() === Entity\StationPlaylist::SOURCE_SONGS
                && $playlist->getMediaItems()->count() > 0) {
                $playlists_by_type[$playlist->getType()][$playlist->getId()] = $playlist;
            }
        }

        // Pull all recent cued songs for easy referencing below.
        $cued_song_history = $this->em->createQuery('SELECT sh FROM '.Entity\SongHistory::class.' sh
            WHERE sh.station_id = :station_id
            AND (sh.timestamp_cued != 0 AND sh.timestamp_cued IS NOT NULL)
            AND sh.timestamp_cued >= :threshold
            ORDER BY sh.timestamp_cued DESC')
            ->setParameter('station_id', $station->getId())
            ->setParameter('threshold', time()-86399)
            ->getArrayResult();

        // Once per day playlists
        if (!empty($playlists_by_type['once_per_day'])) {
            foreach ($playlists_by_type['once_per_day'] as $playlist) {
                /** @var Entity\StationPlaylist $playlist */
                if ($playlist->canPlayOnce()) {
                    // Check if already played
                    $relevant_song_history = array_slice($cued_song_history, 0, 15);

                    $was_played = false;
                    foreach($relevant_song_history as $sh_row) {
                        if ($sh_row['playlist_id'] == $playlist->getId()) {
                            $was_played = true;
                            break;
                        }
                    }

                    if (!$was_played) {
                        if ($event->setNextSong($this->_playSongFromPlaylist($playlist))) {
                            return;
                        };
                    }

                    reset($cued_song_history);
                }
            }
        }

        // Once per X songs playlists
        if (!empty($playlists_by_type['once_per_x_songs'])) {
            foreach($playlists_by_type['once_per_x_songs'] as $playlist) {
                /** @var Entity\StationPlaylist $playlist */

                $relevant_song_history = array_slice($cued_song_history, 0, $playlist->getPlayPerSongs());

                $was_played = false;
                foreach($relevant_song_history as $sh_row) {
                    if ($sh_row['playlist_id'] == $playlist->getId()) {
                        $was_played = true;
                        break;
                    }
                }

                if (!$was_played) {
                    if ($event->setNextSong($this->_playSongFromPlaylist($playlist))) {
                        return;
                    };
                }

                reset($cued_song_history);
            }
        }

        // Once per X minutes playlists
        if (!empty($playlists_by_type['once_per_x_minutes'])) {
            foreach($playlists_by_type['once_per_x_minutes'] as $playlist) {
                /** @var Entity\StationPlaylist $playlist */

                $threshold = time() - ($playlist->getPlayPerMinutes() * 60);

                $was_played = false;
                foreach($cued_song_history as $sh_row) {
                    if ($sh_row['timestamp_cued'] < $threshold) {
                        break;
                    } else if ($sh_row['playlist_id'] == $playlist->getId()) {
                        $was_played = true;
                        break;
                    }
                }

                if (!$was_played) {
                    if ($event->setNextSong($this->_playSongFromPlaylist($playlist))) {
                        return;
                    };
                }

                reset($cued_song_history);
            }
        }

        // Time-block scheduled playlists
        if (!empty($playlists_by_type['scheduled'])) {
            foreach ($playlists_by_type['scheduled'] as $playlist) {
                /** @var Entity\StationPlaylist $playlist */
                if ($playlist->canPlayScheduled()) {
                    if ($event->setNextSong($this->_playSongFromPlaylist($playlist))) {
                        return;
                    };
                }
            }
        }

        // Default rotation playlists
        if (!empty($playlists_by_type['default'])) {
            $playlist_weights = [];
            foreach ($playlists_by_type['default'] as $playlist_id => $playlist) {
                /** @var Entity\StationPlaylist $playlist */
                $playlist_weights[$playlist_id] = $playlist->getWeight();
            }

            $rand = random_int(1, (int)array_sum($playlist_weights));
            foreach ($playlist_weights as $playlist_id => $weight) {
                $rand -= $weight;
                if ($rand <= 0) {
                    $playlist = $playlists_by_type['default'][$playlist_id];

                    if ($event->setNextSong($this->_playSongFromPlaylist($playlist))) {
                        return;
                    };
                }
            }
        }
    }

    /**
     * Given a specified (sequential or shuffled) playlist, choose a song from the playlist to play and return it.
     *
     * @param Entity\StationPlaylist $playlist
     * @return Entity\SongHistory|null
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function _playSongFromPlaylist(Entity\StationPlaylist $playlist)
    {
        /** @var Entity\Repository\StationPlaylistMediaRepository $spm_repo */
        $spm_repo = $this->em->getRepository(Entity\StationPlaylistMedia::class);
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

        return null;
    }


}
