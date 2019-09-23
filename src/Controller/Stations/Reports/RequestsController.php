<?php
namespace App\Controller\Stations\Reports;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Azura\Session\Flash;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;

class RequestsController
{
    /** @var EntityManager */
    protected $em;

    /** @var string */
    protected $csrf_namespace = 'stations_requests';

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $requests = $this->em->createQuery(/** @lang DQL */ 'SELECT 
            sr, sm, s 
            FROM App\Entity\StationRequest sr
            JOIN sr.track sm
            JOIN sm.song s
            WHERE sr.station_id = :station_id
            ORDER BY sr.timestamp DESC')
            ->setParameter('station_id', $station->getId())
            ->getArrayResult();

        return $request->getView()->renderToResponse($response, 'stations/reports/requests', [
            'requests' => $requests,
            'csrf' => $request->getSession()->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        $request_id,
        $csrf
    ): ResponseInterface {
        $request->getSession()->getCsrf()->verify($csrf, $this->csrf_namespace);

        $station = $request->getStation();

        $media = $this->em->getRepository(Entity\StationRequest::class)->findOneBy([
            'id' => $request_id,
            'station_id' => $station->getId(),
            'played_at' => 0,
        ]);

        if ($media instanceof Entity\StationRequest) {
            $this->em->remove($media);
            $this->em->flush();

            $request->getSession()->flash('<b>Request deleted!</b>', Flash::SUCCESS);
        }

        return $response->withRedirect($request->getRouter()->fromHere('stations:reports:requests'));
    }
}
