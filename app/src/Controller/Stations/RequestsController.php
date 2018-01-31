<?php
namespace Controller\Stations;

use App\Flash;
use App\Mvc\View;
use Doctrine\ORM\EntityManager;
use Entity;
use App\Http\Request;
use App\Http\Response;

class RequestsController
{
    /** @var EntityManager */
    protected $em;

    /** @var Flash */
    protected $flash;

    /**
     * RequestsController constructor.
     * @param EntityManager $em
     * @param Flash $flash
     */
    public function __construct(EntityManager $em, Flash $flash)
    {
        $this->em = $em;
        $this->flash = $flash;
    }

    public function indexAction(Request $request, Response $response, $station_id): Response
    {
        $requests = $this->em->createQuery('SELECT sr, sm, s FROM Entity\StationRequest sr
            JOIN sr.track sm
            JOIN sm.song s
            WHERE sr.station_id = :station_id
            ORDER BY sr.timestamp DESC')
            ->setParameter('station_id', $station_id)
            ->getArrayResult();

        /** @var View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'stations/requests/index', [
            'requests' => $requests,
        ]);
    }

    public function deleteAction(Request $request, Response $response, $station_id, $request_id): Response
    {
        $media = $this->em->getRepository(Entity\StationRequest::class)->findOneBy([
            'id' => $request_id,
            'station_id' => $station_id,
            'played_at' => 0
        ]);

        if ($media instanceof Entity\StationRequest) {
            $this->em->remove($media);
            $this->em->flush();

            $this->flash->alert('<b>Request deleted!</b>', 'green');
        }

        return $response->redirectToRoute('stations:requests:index', ['station' => $station_id]);
    }
}