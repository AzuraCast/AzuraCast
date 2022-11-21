<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Entity;
use App\Enums\StationFeatures;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Media\MimeType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

final class FilesAction
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Entity\Repository\CustomFieldRepository $customFieldRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $station = $request->getStation();

        $playlists = $this->em->createQuery(
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

        $backendEnum = $station->getBackendTypeEnum();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsMedia',
            id: 'media-manager',
            title: __('Music Files'),
            props: [
                'listUrl' => $router->fromHere('api:stations:files:list'),
                'batchUrl' => $router->fromHere('api:stations:files:batch'),
                'uploadUrl' => $router->fromHere('api:stations:files:upload'),
                'listDirectoriesUrl' => $router->fromHere('api:stations:files:directories'),
                'mkdirUrl' => $router->fromHere('api:stations:files:mkdir'),
                'renameUrl' => $router->fromHere('api:stations:files:rename'),
                'quotaUrl' => $router->fromHere('api:stations:quota', [
                    'type' => Entity\Enums\StorageLocationTypes::StationMedia->value,
                ]),
                'initialPlaylists' => $playlists,
                'customFields' => $this->customFieldRepo->fetchArray(),
                'validMimeTypes' => MimeType::getProcessableTypes(),
                'stationTimeZone' => $station->getTimezone(),
                'showSftp' => StationFeatures::Sftp->supportedForStation($station),
                'sftpUrl' => $router->fromHere('stations:sftp_users:index'),
                'supportsImmediateQueue' => $backendEnum->isEnabled(),
            ],
        );
    }
}
