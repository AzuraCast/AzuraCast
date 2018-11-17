<?php
namespace App\Controller\Stations\Profile;

use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;

class IndexController
{
    /** @var EntityManager */
    protected $em;

    /** @var Entity\Repository\StationRepository */
    protected $station_repo;

    /**
     * @param EntityManager $em
     * @see \App\Provider\StationsProvider
     */
    public function __construct(
        EntityManager $em
    )
    {
        $this->em = $em;
        $this->station_repo = $em->getRepository(Entity\Station::class);
    }

    public function __invoke(Request $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();
        $view = $request->getView();

        if (!$station->isEnabled()) {
            return $view->renderToResponse($response, 'stations/profile/disabled');
        }

        $backend = $request->getStationBackend();
        $frontend = $request->getStationFrontend();
        $remotes = $request->getStationRemotes();

        $stream_urls = [
            'local' => $frontend->getStreamUrls($station),
            'remote' => [],
        ];

        foreach($remotes as $ra_proxy) {
            $stream_urls['remote'][] = $ra_proxy->getAdapter()->getPublicUrl($ra_proxy->getRemote());
        }

        // Statistics about backend playback.
        $num_songs = $this->em->createQuery('SELECT COUNT(sm.id) FROM '.Entity\StationMedia::class.' sm LEFT JOIN sm.playlist_items spm LEFT JOIN spm.playlist sp WHERE sp.id IS NOT NULL AND sm.station_id = :station_id')
            ->setParameter('station_id', $station->getId())
            ->getSingleScalarResult();

        $num_playlists = $this->em->createQuery('SELECT COUNT(sp.id) FROM '.Entity\StationPlaylist::class.' sp WHERE sp.station_id = :station_id')
            ->setParameter('station_id', $station->getId())
            ->getSingleScalarResult();

        // Populate initial nowplaying data.
        $np = [
            'now_playing' => [
                'song' => [
                    'title' => __('Song Title'),
                    'artist' => __('Song Artist'),
                ],
                'is_request' => false,
                'elapsed' => 0,
                'duration' => 0,
            ],
            'listeners' => [
                'unique' => 0,
                'total' => 0,
            ],
            'live' => [
                'is_live' => false,
                'streamer_name' => '',
            ],
            'playing_next' => [
                'song' => [
                    'title' => __('Song Title'),
                    'artist' => __('Song Artist'),
                ],
            ],
        ];

        $station_np = $station->getNowplaying();
        if ($station_np instanceof Entity\Api\NowPlaying) {
            $np = array_intersect_key($station_np->toArray(), $np) + $np;
        }

        return $view->renderToResponse($response, 'stations/profile/index', [
            'num_songs' => $num_songs,
            'num_playlists' => $num_playlists,
            'stream_urls' => $stream_urls,
            'backend_type' => $station->getBackendType(),
            'backend_config' => (array)$station->getBackendConfig(),
            'backend_is_running' => $backend->isRunning($station),
            'frontend_type' => $station->getFrontendType(),
            'frontend_config' => (array)$station->getFrontendConfig(),
            'frontend_is_running' => $frontend->isRunning($station),
            'nowplaying' => $np,
        ]);
    }
}
