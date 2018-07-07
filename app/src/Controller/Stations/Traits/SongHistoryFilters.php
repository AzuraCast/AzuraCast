<?php
namespace Controller\Stations\Traits;

use App\Cache;
use Doctrine\ORM\EntityManager;

trait SongHistoryFilters
{
    /** @var Cache */
    protected $cache;

    /** @var EntityManager */
    protected $em;

    protected function _getEligibleHistory($station_id)
    {
        $cache_name = 'stations/'.$station_id.'/history';

        $songs_played_raw = $this->cache->get($cache_name);

        if (!$songs_played_raw) {
            try {
                $first_song = $this->em->createQuery('SELECT sh.timestamp_start FROM Entity\SongHistory sh
                    WHERE sh.station_id = :station_id AND sh.listeners_start IS NOT NULL
                    ORDER BY sh.timestamp_start ASC')
                    ->setParameter('station_id', $station_id)
                    ->setMaxResults(1)
                    ->getSingleScalarResult();
            } catch (\Exception $e) {
                $first_song = strtotime('Yesterday 00:00:00');
            }

            $min_threshold = strtotime('-2 weeks');
            $threshold = max($first_song, $min_threshold);

            // Get all songs played in timeline.
            $songs_played_raw = $this->em->createQuery('SELECT sh, sr, sp, s
                FROM Entity\SongHistory sh
                LEFT JOIN sh.request sr
                LEFT JOIN sh.playlist sp 
                LEFT JOIN sh.song s
                WHERE sh.station_id = :station_id AND sh.timestamp_start >= :timestamp AND sh.listeners_start IS NOT NULL
                ORDER BY sh.timestamp_start ASC')
                ->setParameter('station_id', $station_id)
                ->setParameter('timestamp', $threshold)
                ->getArrayResult();

            $ignored_songs = $this->_getIgnoredSongs();
            $songs_played_raw = array_filter($songs_played_raw, function ($value) use ($ignored_songs) {
                return !(isset($ignored_songs[$value['song_id']]));
            });

            $songs_played_raw = array_values($songs_played_raw);

            $this->cache->save($songs_played_raw, $cache_name, 60 * 5);
        }

        return $songs_played_raw;
    }

    protected function _getIgnoredSongs()
    {
        $song_hashes = $this->cache->get('stations/all/ignored_songs');

        if (!$song_hashes) {
            $ignored_phrases = ['Offline', 'Sweeper', 'Bumper', 'Unknown'];

            $qb = $this->em->createQueryBuilder();
            $qb->select('s.id')->from('Entity\Song', 's');

            foreach ($ignored_phrases as $i => $phrase) {
                $qb->orWhere('s.text LIKE ?' . ($i + 1));
                $qb->setParameter($i + 1, '%' . $phrase . '%');
            }

            $song_hashes_raw = $qb->getQuery()->getArrayResult();
            $song_hashes = [];

            foreach ($song_hashes_raw as $row) {
                $song_hashes[$row['id']] = $row['id'];
            }

            $this->cache->save($song_hashes, 'stations/all/ignored_songs', 86400);
        }

        return $song_hashes;
    }
}
