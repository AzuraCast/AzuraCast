<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Enums\StorageLocationTypes;
use App\Entity\Repository\CustomFieldRepository;
use App\Enums\StationFeatures;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Media\MimeType;
use Psr\Http\Message\ResponseInterface;

final class FilesAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly CustomFieldRepository $customFieldRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
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
            ->setParameter('source', PlaylistSources::Songs->value)
            ->getArrayResult();

        $router = $request->getRouter();

        $backendEnum = $station->getBackendType();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Stations/Media',
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
                    'type' => StorageLocationTypes::StationMedia->value,
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
