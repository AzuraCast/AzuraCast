<?php
namespace Controller\Stations;

use Entity;
use App\Http\Request;
use App\Http\Response;

class RequestsController extends BaseController
{
    protected function permissions()
    {
        return $this->acl->isAllowed('view station reports', $this->station->getId());
    }

    public function indexAction(Request $request, Response $response): Response
    {
        $this->view->requests = $this->em->createQuery('SELECT sr, sm, s FROM Entity\StationRequest sr
            JOIN sr.track sm
            JOIN sm.song s
            WHERE sr.station_id = :station_id
            ORDER BY sr.timestamp DESC')
            ->setParameter('station_id', $this->station->getId())
            ->getArrayResult();
    }

    public function deleteAction(Request $request, Response $response): Response
    {
        $id = (int)$this->getParam('request_id');

        $media = $this->em->getRepository(Entity\StationRequest::class)->findOneBy([
            'id' => $id,
            'station_id' => $this->station->getId(),
            'played_at' => 0
        ]);

        if ($media instanceof Entity\StationRequest) {
            $this->em->remove($media);
            $this->em->flush();

            $this->alert('<b>Request deleted!</b>', 'green');
        }

        return $this->redirectFromHere(['action' => 'index', 'media_id' => null]);
    }
}