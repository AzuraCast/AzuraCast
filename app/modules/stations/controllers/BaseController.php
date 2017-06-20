<?php
namespace Controller\Stations;

use AzuraCast\Radio\Backend\BackendAbstract;
use AzuraCast\Radio\Frontend\FrontendAbstract;
use Entity\Station;

class BaseController extends \AzuraCast\Mvc\Controller
{
    /**
     * @var Station The current active station.
     */
    protected $station;

    /**
     * @var FrontendAbstract
     */
    protected $frontend;

    /**
     * @var BackendAbstract
     */
    protected $backend;

    public function init()
    {
        $station_id = (int)$this->getParam('station');
        $this->station = $this->view->station = $this->em->getRepository(Station::class)->find($station_id);

        if (!($this->station instanceof Station)) {
            throw new \App\Exception\PermissionDenied;
        }

        $this->frontend = $this->view->frontend = $this->station->getFrontendAdapter($this->di);
        $this->backend = $this->view->backend = $this->station->getBackendAdapter($this->di);

        $this->view->sidebar = $this->view->fetch('common::sidebar');

        parent::init();
    }

    protected function permissions()
    {
        return $this->acl->isAllowed('view station management', $this->station->id);
    }

    protected function _getEligibleHistory()
    {
        $cache = $this->di->get('cache');
        $cache_name = 'station_center_history_' . $this->station->id;

        $songs_played_raw = $cache->get($cache_name);

        if (!$songs_played_raw) {
            try {
                $first_song = $this->em->createQuery('SELECT sh.timestamp_start FROM Entity\SongHistory sh
                    WHERE sh.station_id = :station_id AND sh.listeners_start IS NOT NULL
                    ORDER BY sh.timestamp_start ASC')
                    ->setParameter('station_id', $this->station->id)
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
                ->setParameter('station_id', $this->station->id)
                ->setParameter('timestamp', $threshold)
                ->getArrayResult();

            $ignored_songs = $this->_getIgnoredSongs();
            $songs_played_raw = array_filter($songs_played_raw, function ($value) use ($ignored_songs) {
                return !(isset($ignored_songs[$value['song_id']]));
            });

            $songs_played_raw = array_values($songs_played_raw);

            $cache->save($songs_played_raw, $cache_name, 60 * 5);
        }

        return $songs_played_raw;
    }

    protected function _getIgnoredSongs()
    {
        $cache = $this->di->get('cache');
        $song_hashes = $cache->get('station_center_ignored_songs');

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

            $cache->save($song_hashes, 'station_center_ignored_songs', 86400);
        }

        return $song_hashes;
    }
}