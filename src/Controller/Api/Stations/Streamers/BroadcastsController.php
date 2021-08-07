<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Streamers;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator;
use App\Utilities;
use App\Utilities\File;
use Psr\Http\Message\ResponseInterface;

/**
 * @extends AbstractApiCrudController<Entity\StationStreamerBroadcast>
 */
class BroadcastsController extends AbstractApiCrudController
{
    protected string $entityClass = Entity\StationStreamerBroadcast::class;

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param int|null $id
     */
    public function listAction(
        ServerRequest $request,
        Response $response,
        ?int $id = null
    ): ResponseInterface {
        $station = $request->getStation();

        if (null !== $id) {
            $streamer = $this->getStreamer($station, $id);

            if (null === $streamer) {
                return $response->withStatus(404)
                    ->withJson(Entity\Api\Error::notFound());
            }

            $query = $this->em->createQuery(
                <<<'DQL'
                    SELECT ssb
                    FROM App\Entity\StationStreamerBroadcast ssb
                    WHERE ssb.station = :station AND ssb.streamer = :streamer
                    ORDER BY ssb.timestampStart DESC
                DQL
            )->setParameter('station', $station)
                ->setParameter('streamer', $streamer);
        } else {
            $query = $this->em->createQuery(
                <<<'DQL'
                    SELECT ssb, ss
                    FROM App\Entity\StationStreamerBroadcast ssb
                    JOIN ssb.streamer ss
                    WHERE ssb.station = :station
                    ORDER BY ssb.timestampStart DESC
                DQL
            )->setParameter('station', $station);
        }

        $paginator = Paginator::fromQuery($query, $request);

        $is_bootgrid = $paginator->isFromBootgrid();
        $router = $request->getRouter();

        $fsRecordings = (new StationFilesystems($station))->getRecordingsFilesystem();

        $paginator->setPostprocessor(
            function ($row) use ($id, $is_bootgrid, $router, $fsRecordings) {
                $return = $this->toArray($row);

                unset($return['recordingPath']);
                $recordingPath = $row->getRecordingPath();

                if (null === $id) {
                    $streamer = $row->getStreamer();
                    $return['streamer'] = [
                        'id' => $streamer->getId(),
                        'streamer_username' => $streamer->getStreamerUsername(),
                        'display_name' => $streamer->getDisplayName(),
                    ];
                }

                if (!empty($recordingPath) && $fsRecordings->fileExists($recordingPath)) {
                    $routeParams = [
                        'broadcast_id' => $row->getId(),
                    ];
                    if (null === $id) {
                        $routeParams['id'] = $row->getStreamer()->getId();
                    }

                    $return['recording'] = [
                        'path' => $recordingPath,
                        'size' => $fsRecordings->fileSize($recordingPath),
                        'links' => [
                            'download' => (string)$router->fromHere(
                                'api:stations:streamer:broadcast:download',
                                $routeParams,
                                [],
                                true
                            ),
                            'delete' => (string)$router->fromHere(
                                'api:stations:streamer:broadcast:delete',
                                $routeParams,
                                [],
                                true
                            ),
                        ],
                    ];
                } else {
                    $return['recording'] = [];
                }

                if ($is_bootgrid) {
                    return Utilities\Arrays::flattenArray($return, '_');
                }

                return $return;
            }
        );

        return $paginator->write($response);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param int $broadcast_id
     */
    public function downloadAction(
        ServerRequest $request,
        Response $response,
        int $broadcast_id
    ): ResponseInterface {
        $station = $request->getStation();
        $broadcast = $this->getRecord($station, $broadcast_id);

        if (null === $broadcast) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $recordingPath = $broadcast->getRecordingPath();

        if (empty($recordingPath)) {
            return $response->withStatus(400)
                ->withJson(new Entity\Api\Error(400, __('No recording available.')));
        }

        $filename = basename($recordingPath);

        $fsRecordings = (new StationFilesystems($station))->getRecordingsFilesystem();

        return $response->streamFilesystemFile(
            $fsRecordings,
            $recordingPath,
            File::sanitizeFileName($broadcast->getStreamer()->getDisplayName()) . '_' . $filename
        );
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        int $broadcast_id
    ): ResponseInterface {
        $station = $request->getStation();
        $broadcast = $this->getRecord($station, $broadcast_id);

        if (null === $broadcast) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $recordingPath = $broadcast->getRecordingPath();

        if (!empty($recordingPath)) {
            $fsRecordings = (new StationFilesystems($station))->getRecordingsFilesystem();

            $fsRecordings->delete($recordingPath);

            $broadcast->clearRecordingPath();
            $this->em->persist($broadcast);
            $this->em->flush();
        }

        return $response->withJson(new Entity\Api\Status());
    }

    protected function getRecord(Entity\Station $station, int $id): ?Entity\StationStreamerBroadcast
    {
        /** @var Entity\StationStreamerBroadcast|null $broadcast */
        $broadcast = $this->em->getRepository(Entity\StationStreamerBroadcast::class)->findOneBy(
            [
                'id' => $id,
                'station' => $station,
            ]
        );
        return $broadcast;
    }

    protected function getStreamer(Entity\Station $station, int $id): ?Entity\StationStreamer
    {
        /** @var Entity\StationStreamer|null $streamer */
        $streamer = $this->em->getRepository(Entity\StationStreamer::class)->findOneBy(
            [
                'id' => $id,
                'station' => $station,
            ]
        );
        return $streamer;
    }
}
