<?php
namespace App\Controller\Stations;

use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;

class RequestsController
{
    /** @var EntityManager */
    protected $em;

    /** @var string */
    protected $csrf_namespace = 'stations_requests';

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function indexAction(Request $request, Response $response, $station_id): Response
    {
        $requests = $this->em->createQuery('SELECT sr, sm, s FROM '.Entity\StationRequest::class.' sr
            JOIN sr.track sm
            JOIN sm.song s
            WHERE sr.station_id = :station_id
            ORDER BY sr.timestamp DESC')
            ->setParameter('station_id', $station_id)
            ->getArrayResult();

        return $request->getView()->renderToResponse($response, 'stations/requests/index', [
            'requests' => $requests,
            'csrf' => $request->getSession()->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function deleteAction(Request $request, Response $response, $station_id, $request_id, $csrf_token): Response
    {
        $request->getSession()->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        $media = $this->em->getRepository(Entity\StationRequest::class)->findOneBy([
            'id' => $request_id,
            'station_id' => $station_id,
            'played_at' => 0
        ]);

        if ($media instanceof Entity\StationRequest) {
            $this->em->remove($media);
            $this->em->flush();

            $request->getSession()->flash('<b>Request deleted!</b>', 'green');
        }

        return $response->redirectToRoute('stations:requests:index', ['station' => $station_id]);
    }
}
