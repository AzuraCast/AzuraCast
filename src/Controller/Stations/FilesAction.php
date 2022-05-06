<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Media\MimeType;
use App\Service\SftpGo;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class FilesAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        Entity\Repository\CustomFieldRepository $customFieldRepo
    ): ResponseInterface {
        $station = $request->getStation();

        $playlists = $em->createQuery(
            <<<'DQL'
                SELECT sp.id, sp.name
                FROM App\Entity\StationPlaylist sp
                WHERE sp.station_id = :station_id AND sp.source = :source
                ORDER BY sp.name ASC
            DQL
        )->setParameter('station_id', $station->getId())
            ->setParameter('source', Entity\Enums\PlaylistSources::Songs->value)
            ->getArrayResult();

        $router = $request->getRouter();

        $backend = $request->getStationBackend();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsMedia',
            id: 'media-manager',
            title: __('Music Files'),
            props: [
                'listUrl' => (string)$router->fromHere('api:stations:files:list'),
                'batchUrl' => (string)$router->fromHere('api:stations:files:batch'),
                'uploadUrl' => (string)$router->fromHere('api:stations:files:upload'),
                'listDirectoriesUrl' => (string)$router->fromHere('api:stations:files:directories'),
                'mkdirUrl' => (string)$router->fromHere('api:stations:files:mkdir'),
                'renameUrl' => (string)$router->fromHere('api:stations:files:rename'),
                'quotaUrl' => (string)$router->fromHere('api:stations:quota', [
                    'type' => Entity\Enums\StorageLocationTypes::StationMedia->value,
                ]),
                'initialPlaylists' => $playlists,
                'customFields' => $customFieldRepo->fetchArray(),
                'validMimeTypes' => MimeType::getProcessableTypes(),
                'stationTimeZone' => $station->getTimezone(),
                'showSftp' => SftpGo::isSupportedForStation($station),
                'sftpUrl' => (string)$router->fromHere('stations:sftp_users:index'),
                'supportsImmediateQueue' => $backend->supportsImmediateQueue(),
            ],
        );
    }
}
