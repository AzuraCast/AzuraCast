<?php

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

class BroadcastsController extends AbstractApiCrudController
{
    protected string $entityClass = Entity\StationStreamerBroadcast::class;

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param string|int $station_id
     * @param int $id
     */
    public function listAction(
        ServerRequest $request,
        Response $response,
        $station_id,
        $id
    ): ResponseInterface {
        $station = $request->getStation();
        $streamer = $this->getStreamer($station, $id);

        if (null === $streamer) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Record not found!')));
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

        $paginator = Paginator::fromQuery($query, $request);

        $is_bootgrid = $paginator->isFromBootgrid();
        $router = $request->getRouter();

        $fsStation = new StationFilesystems($station);
        $fsRecordings = $fsStation->getRecordingsFilesystem();

        $paginator->setPostprocessor(
            function ($row) use ($is_bootgrid, $router, $fsRecordings) {
                /** @var Entity\StationStreamerBroadcast $row */
                $return = $this->toArray($row);

                unset($return['recordingPath']);

                $recordingPath = $row->getRecordingPath();

                if (!empty($recordingPath) && $fsRecordings->fileExists($recordingPath)) {
                    $return['recording'] = [
                        'path' => $recordingPath,
                        'size' => $fsRecordings->fileSize($recordingPath),
                        'links' => [
                            'download' => $router->fromHere(
                                'api:stations:streamer:broadcast:download',
                                ['broadcast_id' => $row->getId()],
                                [],
                                true
                            ),
                            'delete' => $router->fromHere(
                                'api:stations:streamer:broadcast:delete',
                                ['broadcast_id' => $row->getId()],
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
     * @param string|int $station_id
     * @param int $id
     * @param int $broadcast_id
     */
    public function downloadAction(
        ServerRequest $request,
        Response $response,
        $station_id,
        $id,
        $broadcast_id
    ): ResponseInterface {
        $station = $request->getStation();
        $broadcast = $this->getRecord($station, $broadcast_id);

        if (null === $broadcast) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Record not found!')));
        }

        $recordingPath = $broadcast->getRecordingPath();

        if (empty($recordingPath)) {
            return $response->withStatus(400)
                ->withJson(new Entity\Api\Error(400, __('No recording available.')));
        }

        $filename = basename($recordingPath);

        $fsStation = new StationFilesystems($station);
        $fsRecordings = $fsStation->getRecordingsFilesystem();

        return $response->streamFilesystemFile(
            $fsRecordings,
            $recordingPath,
            File::sanitizeFileName($broadcast->getStreamer()->getDisplayName()) . '_' . $filename
        );
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        $station_id,
        $id,
        $broadcast_id
    ): ResponseInterface {
        $station = $request->getStation();
        $broadcast = $this->getRecord($station, $broadcast_id);

        if (null === $broadcast) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Record not found!')));
        }

        $recordingPath = $broadcast->getRecordingPath();

        if (!empty($recordingPath)) {
            $fsStation = new StationFilesystems($station);
            $fsRecordings = $fsStation->getRecordingsFilesystem();

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
