<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Streamers;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator;
use App\Utilities\File;
use Psr\Http\Message\ResponseInterface;

/**
 * @extends AbstractApiCrudController<Entity\StationStreamerBroadcast>
 */
final class BroadcastsController extends AbstractApiCrudController
{
    protected string $entityClass = Entity\StationStreamerBroadcast::class;

    public function listAction(
        ServerRequest $request,
        Response $response,
        string $station_id,
        ?string $id = null
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

        $router = $request->getRouter();
        $isInternal = ('true' === $request->getParam('internal', 'false'));
        $fsRecordings = (new StationFilesystems($station))->getRecordingsFilesystem();

        $paginator->setPostprocessor(
            function ($row) use ($id, $router, $isInternal, $fsRecordings) {
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

                $routeParams = [
                    'broadcast_id' => $row->getId(),
                ];
                if (null === $id) {
                    $routeParams['id'] = $row->getStreamer()->getId();
                }

                if (!empty($recordingPath) && $fsRecordings->fileExists($recordingPath)) {
                    $return['recording'] = [
                        'path' => $recordingPath,
                        'size' => $fsRecordings->fileSize($recordingPath),
                        'links' => [
                            'download' => $router->fromHere(
                                routeName: 'api:stations:streamer:broadcast:download',
                                routeParams: $routeParams,
                                absolute: !$isInternal
                            ),
                        ],
                    ];
                } else {
                    $return['recording'] = [];
                }

                $return['links'] = [
                    'delete' => $router->fromHere(
                        routeName: 'api:stations:streamer:broadcast:delete',
                        routeParams: $routeParams,
                        absolute: !$isInternal
                    ),
                ];

                return $return;
            }
        );

        return $paginator->write($response);
    }

    public function downloadAction(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $id,
        string $broadcast_id
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
        string $station_id,
        string $id,
        string $broadcast_id
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
        }

        $this->em->remove($broadcast);
        $this->em->flush();

        return $response->withJson(Entity\Api\Status::deleted());
    }

    private function getRecord(Entity\Station $station, int|string $id): ?Entity\StationStreamerBroadcast
    {
        /** @var Entity\StationStreamerBroadcast|null $broadcast */
        $broadcast = $this->em->getRepository(Entity\StationStreamerBroadcast::class)->findOneBy(
            [
                'id' => (int)$id,
                'station' => $station,
            ]
        );
        return $broadcast;
    }

    private function getStreamer(Entity\Station $station, int|string $id): ?Entity\StationStreamer
    {
        /** @var Entity\StationStreamer|null $streamer */
        $streamer = $this->em->getRepository(Entity\StationStreamer::class)->findOneBy(
            [
                'id' => (int)$id,
                'station' => $station,
            ]
        );
        return $streamer;
    }
}
