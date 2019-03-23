<?php
namespace App\Controller\Stations\Reports;

use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;

class RequestsController
{
    /** @var EntityManager */
    protected $em;

    /** @var string */
    protected $csrf_namespace = 'stations_requests';

    /**
     * @param EntityManager $em
     * @see \App\Provider\StationsProvider
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function __invoke(Request $request, Response $response, $station_id): ResponseInterface
    {
        $requests = $this->em->createQuery(/** @lang DQL */'SELECT 
            sr, sm, s 
            FROM App\Entity\StationRequest sr
            JOIN sr.track sm
            JOIN sm.song s
            WHERE sr.station_id = :station_id
            ORDER BY sr.timestamp DESC')
            ->setParameter('station_id', $station_id)
            ->getArrayResult();

        return $request->getView()->renderToResponse($response, 'stations/reports/requests', [
            'requests' => $requests,
            'csrf' => $request->getSession()->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function deleteAction(Request $request, Response $response, $station_id, $request_id, $csrf_token): ResponseInterface
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

        return $response->withRedirect($request->getRouter()->fromHere('stations:reports:requests'));
    }
}
