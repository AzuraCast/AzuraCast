<?php

namespace App\Controller\Api\Stations\Streamers;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity;
use App\File;
use App\Flysystem\FilesystemManager;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator\QueryPaginator;
use App\Utilities;
use Psr\Http\Message\ResponseInterface;

class BroadcastsController extends AbstractApiCrudController
{
    protected string $entityClass = Entity\StationStreamerBroadcast::class;

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param FilesystemManager $filesystem
     * @param string|int $station_id
     * @param int $id
     */
    public function listAction(
        ServerRequest $request,
        Response $response,
        FilesystemManager $filesystem,
        $station_id,
        $id
    ): ResponseInterface {
        $station = $request->getStation();
        $streamer = $this->getStreamer($station, $id);

        if (null === $streamer) {
            return $response->withStatus(404)
                ->withJson(new Entity\Api\Error(404, __('Record not found!')));
        }

        $query = $this->em->createQuery(/** @lang DQL */ 'SELECT ssb
            FROM App\Entity\StationStreamerBroadcast ssb
            WHERE ssb.station = :station AND ssb.streamer = :streamer
            ORDER BY ssb.timestampStart DESC')
            ->setParameter('station', $station)
            ->setParameter('streamer', $streamer);

        $paginator = new QueryPaginator($query, $request);

        $is_bootgrid = $paginator->isFromBootgrid();
        $router = $request->getRouter();

        $fs = $filesystem->getForStation($station);

        $paginator->setPostprocessor(function ($row) use ($is_bootgrid, $router, $fs) {
            /** @var Entity\StationStreamerBroadcast $row */
            $return = $this->toArray($row);

            unset($return['recordingPath']);

            $recordingPath = $row->getRecordingPath();
            $recordingUri = FilesystemManager::PREFIX_RECORDINGS . '://' . $recordingPath;

            if ($fs->has($recordingUri)) {
                $recordingMeta = $fs->getMetadata($recordingUri);

                $return['recording'] = [
                    'path' => $recordingPath,
                    'size' => $recordingMeta['size'],
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
                return Utilities::flattenArray($return, '_');
            }

            return $return;
        });

        return $paginator->write($response);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param FilesystemManager $filesystem
     * @param string|int $station_id
     * @param int $id
     * @param int $broadcast_id
     */
    public function downloadAction(
        ServerRequest $request,
        Response $response,
        FilesystemManager $filesystem,
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

        $fs = $filesystem->getForStation($station);
        $filename = basename($recordingPath);

        $recordingPath = FilesystemManager::PREFIX_RECORDINGS . '://' . $recordingPath;

        return $fs->streamToResponse(
            $response,
            $recordingPath,
            File::sanitizeFileName($broadcast->getStreamer()->getDisplayName()) . '_' . $filename
        );
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        FilesystemManager $filesystem,
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
            $fs = $filesystem->getForStation($station);
            $recordingPath = FilesystemManager::PREFIX_RECORDINGS . '://' . $recordingPath;

            $fs->delete($recordingPath);

            $broadcast->clearRecordingPath();
            $this->em->persist($broadcast);
            $this->em->flush();
        }

        return $response->withJson(new Entity\Api\Status());
    }

    protected function getRecord(Entity\Station $station, int $id): ?Entity\StationStreamerBroadcast
    {
        /** @var Entity\StationStreamerBroadcast|null $broadcast */
        $broadcast = $this->em->getRepository(Entity\StationStreamerBroadcast::class)->findOneBy([
            'id' => $id,
            'station' => $station,
        ]);
        return $broadcast;
    }

    protected function getStreamer(Entity\Station $station, int $id): ?Entity\StationStreamer
    {
        /** @var Entity\StationStreamer|null $streamer */
        $streamer = $this->em->getRepository(Entity\StationStreamer::class)->findOneBy([
            'id' => $id,
            'station' => $station,
        ]);
        return $streamer;
    }
}
