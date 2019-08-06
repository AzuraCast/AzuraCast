<?php
namespace App\Controller\Stations;

use App\Entity;
use App\Form\StationForm;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProfileController
{
    /** @var EntityManager */
    protected $em;

    /** @var Entity\Repository\StationRepository */
    protected $station_repo;

    /** @var StationForm */
    protected $station_form;

    /** @var string */
    protected $csrf_namespace = 'stations_profile';

    /**
     * @param EntityManager $em
     * @param StationForm $station_form
     *
     * @see \App\Provider\StationsProvider
     */
    public function __construct(
        EntityManager $em,
        StationForm $station_form
    )
    {
        $this->em = $em;
        $this->station_repo = $em->getRepository(Entity\Station::class);

        $this->station_form = $station_form;
    }

    public function __invoke(Request $request, Response $response): ResponseInterface
    {
        $station = \App\Http\RequestHelper::getStation($request);
        $view = \App\Http\RequestHelper::getView($request);

        if (!$station->isEnabled()) {
            return $view->renderToResponse($response, 'stations/profile/disabled');
        }

        $frontend = \App\Http\RequestHelper::getStationFrontend($request);
        $remotes = \App\Http\RequestHelper::getStationRemotes($request);

        $stream_urls = [
            'local' => [],
            'remote' => [],
        ];

        foreach ($station->getMounts() as $mount) {
            $stream_urls['local'][] = [
                $mount->getDisplayName(),
                (string)$frontend->getUrlForMount($station, $mount),
            ];
        }

        foreach($remotes as $ra_proxy) {
            $remote = $ra_proxy->getRemote();

            $stream_urls['remote'][] = [
                $remote->getDisplayName(),
                (string)$ra_proxy->getAdapter()->getPublicUrl($remote)
            ];
        }

        // Statistics about backend playback.
        $num_songs = $this->em->createQuery(/** @lang DQL */ 'SELECT COUNT(sm.id) 
            FROM App\Entity\StationMedia sm 
            LEFT JOIN sm.playlists spm 
            LEFT JOIN spm.playlist sp 
            WHERE sp.id IS NOT NULL 
            AND sm.station_id = :station_id')
            ->setParameter('station_id', $station->getId())
            ->getSingleScalarResult();

        $num_playlists = $this->em->createQuery(/** @lang DQL */ 'SELECT COUNT(sp.id) 
            FROM App\Entity\StationPlaylist sp 
            WHERE sp.station_id = :station_id')
            ->setParameter('station_id', $station->getId())
            ->getSingleScalarResult();

        // Populate initial nowplaying data.
        $np = [
            'now_playing' => [
                'song' => [
                    'title' => __('Song Title'),
                    'artist' => __('Song Artist'),
                    'art' => '',
                ],
                'playlist' => '',
                'is_request' => false,
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
                    'art' => '',
                ],
                'playlist' => '',
            ],
        ];

        $station_np = $station->getNowplaying();
        if ($station_np instanceof Entity\Api\NowPlaying) {
            $station_np->resolveUrls($request->getRouter()->getBaseUrl());
            $np = array_intersect_key($station_np->toArray(), $np) + $np;
        }

        $view->addData([
            'num_songs'     => $num_songs,
            'num_playlists' => $num_playlists,
            'stream_urls'   => $stream_urls,
            'backend_type'  => $station->getBackendType(),
            'backend_config' => (array)$station->getBackendConfig(),
            'frontend_type' => $station->getFrontendType(),
            'frontend_config' => (array)$station->getFrontendConfig(),
            'nowplaying'    => $np,
            'user'          => \App\Http\RequestHelper::getUser($request),
            'csrf'          => \App\Http\RequestHelper::getSession($request)->getCsrf()->generate($this->csrf_namespace),
        ]);

        return $view->renderToResponse($response, 'stations/profile/index');
    }

    public function editAction(Request $request, Response $response, $station_id): ResponseInterface
    {
        $station = \App\Http\RequestHelper::getStation($request);

        if (false !== $this->station_form->process($request, $station)) {
            return $response->withRedirect($request->getRouter()->fromHere('stations:profile:index'));
        }

        return \App\Http\RequestHelper::getView($request)->renderToResponse($response, 'stations/profile/edit', [
            'form' => $this->station_form,
        ]);
    }

    public function toggleAction(Request $request, Response $response, $station_id, $feature, $csrf_token): ResponseInterface
    {
        \App\Http\RequestHelper::getSession($request)->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        $station = \App\Http\RequestHelper::getStation($request);

        switch($feature) {
            case 'requests':
                $station->setEnableRequests(!$station->getEnableRequests());
            break;

            case 'streamers':
                $station->setEnableStreamers(!$station->getEnableStreamers());
            break;

            case 'public':
                $station->setEnablePublicPage(!$station->getEnablePublicPage());
            break;
        }

        $this->em->persist($station);
        $this->em->flush($station);

        $this->em->refresh($station);

        return $response->withRedirect($request->getRouter()->fromHere('stations:profile:index'));
    }
}
