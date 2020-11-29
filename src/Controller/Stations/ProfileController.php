<?php

namespace App\Controller\Stations;

use App\Entity;
use App\Form\StationForm;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class ProfileController
{
    protected EntityManagerInterface $em;

    protected Entity\Repository\StationRepository $station_repo;

    protected StationForm $station_form;

    protected string $csrf_namespace = 'stations_profile';

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\StationRepository $station_repo,
        StationForm $station_form
    ) {
        $this->em = $em;
        $this->station_repo = $station_repo;
        $this->station_form = $station_form;
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();
        $view = $request->getView();

        if (!$station->isEnabled()) {
            return $view->renderToResponse($response, 'stations/profile/disabled');
        }

        // Statistics about backend playback.
        $num_songs = $this->em->createQuery(/** @lang DQL */ 'SELECT COUNT(sm.id)
            FROM App\Entity\StationMedia sm
            LEFT JOIN sm.playlists spm
            LEFT JOIN spm.playlist sp
            WHERE sp.id IS NOT NULL
            AND sp.station_id = :station_id')
            ->setParameter('station_id', $station->getId())
            ->getSingleScalarResult();

        $num_playlists = $this->em->createQuery(/** @lang DQL */ 'SELECT COUNT(sp.id)
            FROM App\Entity\StationPlaylist sp
            WHERE sp.station_id = :station_id')
            ->setParameter('station_id', $station->getId())
            ->getSingleScalarResult();

        $view->addData([
            'num_songs' => (int)$num_songs,
            'num_playlists' => (int)$num_playlists,
            'backend_type' => $station->getBackendType(),
            'backend_config' => $station->getBackendConfig(),
            'frontend_type' => $station->getFrontendType(),
            'frontend_config' => $station->getFrontendConfig(),
            'user' => $request->getUser(),
            'csrf' => $request->getCsrf()->generate($this->csrf_namespace),
        ]);

        return $view->renderToResponse($response, 'stations/profile/index');
    }

    public function editAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        if (false !== $this->station_form->process($request, $station)) {
            return $response->withRedirect($request->getRouter()->fromHere('stations:profile:index'));
        }

        return $request->getView()->renderToResponse($response, 'stations/profile/edit', [
            'form' => $this->station_form,
        ]);
    }

    public function toggleAction(
        ServerRequest $request,
        Response $response,
        $feature,
        $csrf
    ): ResponseInterface {
        $request->getCsrf()->verify($csrf, $this->csrf_namespace);

        $station = $request->getStation();

        switch ($feature) {
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
        $this->em->flush();

        return $response->withRedirect($request->getRouter()->fromHere('stations:profile:index'));
    }
}
