<?php
namespace App\Controller\Api\Stations;

use App;
use Azura\Doctrine\Paginator;
use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;

class QueueController
{
    /** @var EntityManager */
    protected $em;

    /** @var App\ApiUtilities */
    protected $api_utils;

    /**
     * @param EntityManager $em
     * @param App\ApiUtilities $api_utils
     *
     * @see \App\Provider\ApiProvider
     */
    public function __construct(EntityManager $em, App\ApiUtilities $api_utils)
    {
        $this->em = $em;
        $this->api_utils = $api_utils;
    }

    public function __invoke(Request $request, Response $response, $station_id): ResponseInterface
    {
        $query = $this->em->createQuery('SELECT sh, sp, s, sm
            FROM ' . Entity\SongHistory::class . ' sh 
            LEFT JOIN sh.song s 
            LEFT JOIN sh.media sm
            LEFT JOIN sh.playlist sp 
            WHERE sh.station_id = :station_id
            AND sh.sent_to_autodj = 0
            AND sh.timestamp_start = 0
            AND sh.timestamp_end = 0
            ORDER BY sh.timestamp_cued DESC')
            ->setParameter('station_id', $station_id);

        $paginator = new Paginator($query);
        $paginator->setFromRequest($request);

        $is_bootgrid = $paginator->isFromBootgrid();
        $router = $request->getRouter();

        $paginator->setPostprocessor(function($sh_row) use ($is_bootgrid, $router) {

            /** @var Entity\SongHistory $sh_row */
            /** @var Entity\Api\QueuedSong $row */
            $row = $sh_row->api(new Entity\Api\QueuedSong, $this->api_utils);
            $row->resolveUrls($router);

            $row->links = [
                'self' => (string)$router->fromHere('api:stations:queue:record', ['id' => $sh_row->getId()], [], true),
            ];

            if ($is_bootgrid) {
                return App\Utilities::flatten_array($row, '_');
            }

            return $row;
        });

        return $paginator->write($response);
    }

    public function record(Request $request, Response $response, $station_id, $id): ResponseInterface
    {
        $sh_repo = $this->em->getRepository(Entity\SongHistory::class);

        $sh_record = $sh_repo->findOneBy([
            'station_id' => $station_id,
            'id' => $id,
        ]);

        if (!($sh_record instanceof Entity\SongHistory)) {
            return $response->withJson(new Entity\Api\Error(404, 'Record not found'));
        }

        switch($request->getMethod())
        {
            case 'DELETE':
                $this->em->remove($sh_record);
                $this->em->flush();

                return $response->withJson(new Entity\Api\Status(true, 'Record deleted.'));
                break;

            case 'GET':
            default:
                $router = $request->getRouter();

                /** @var Entity\Api\QueuedSong $row */
                $row = $sh_record->api(new Entity\Api\QueuedSong, $this->api_utils);
                $row->resolveUrls($router);

                $row->links = [
                    'self' => (string)$router->fromHere(null, [], [], true),
                ];

                return $response->withJson($row);
                break;
        }
    }
}
