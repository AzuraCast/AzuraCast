<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Entity;
use App\Form\StationForm;
use App\Http\Response;
use App\Http\ServerRequest;
use DI\FactoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class ProfileController
{
    protected string $csrf_namespace = 'stations_profile';

    public function __construct(
        protected EntityManagerInterface $em,
        protected Entity\Repository\StationRepository $stationRepo,
        protected FactoryInterface $factory
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();
        $view = $request->getView();

        if (!$station->isEnabled()) {
            return $view->renderToResponse($response, 'stations/profile/disabled');
        }

        // Statistics about backend playback.
        $num_songs = $this->em->createQuery(
            <<<'DQL'
                SELECT COUNT(sm.id)
                FROM App\Entity\StationMedia sm
                LEFT JOIN sm.playlists spm
                LEFT JOIN spm.playlist sp
                WHERE sp.id IS NOT NULL
                AND sp.station_id = :station_id
            DQL
        )->setParameter('station_id', $station->getId())
            ->getSingleScalarResult();

        $num_playlists = $this->em->createQuery(
            <<<'DQL'
                SELECT COUNT(sp.id)
                FROM App\Entity\StationPlaylist sp
                WHERE sp.station_id = :station_id
            DQL
        )->setParameter('station_id', $station->getId())
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
        $stationForm = $this->factory->make(StationForm::class);

        if (false !== $stationForm->process($request, $station)) {
            return $response->withRedirect((string)$request->getRouter()->fromHere('stations:profile:index'));
        }

        return $request->getView()->renderToResponse(
            $response,
            'stations/profile/edit',
            [
                'form' => $stationForm,
            ]
        );
    }

    public function toggleAction(
        ServerRequest $request,
        Response $response,
        string $feature,
        string $csrf
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

        return $response->withRedirect((string)$request->getRouter()->fromHere('stations:profile:index'));
    }
}
