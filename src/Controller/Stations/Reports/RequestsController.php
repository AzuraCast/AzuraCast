<?php
namespace App\Controller\Stations\Reports;

use App\Entity;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $station_id): ResponseInterface
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

        return RequestHelper::getView($request)->renderToResponse($response, 'stations/reports/requests', [
            'requests' => $requests,
            'csrf' => RequestHelper::getSession($request)->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function deleteAction(ServerRequestInterface $request, ResponseInterface $response, $station_id, $request_id, $csrf_token): ResponseInterface
    {
        RequestHelper::getSession($request)->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        $media = $this->em->getRepository(Entity\StationRequest::class)->findOneBy([
            'id' => $request_id,
            'station_id' => $station_id,
            'played_at' => 0
        ]);

        if ($media instanceof Entity\StationRequest) {
            $this->em->remove($media);
            $this->em->flush();

            RequestHelper::getSession($request)->flash('<b>Request deleted!</b>', 'green');
        }

        return ResponseHelper::withRedirect($response, RequestHelper::getRouter($request)->fromHere('stations:reports:requests'));
    }
}
