<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Vue;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Enums\PlaylistSources;
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

        $backendEnum = $station->getBackendType();

        return $response->withJson([
            'initialPlaylists' => $playlists,
            'customFields' => $this->customFieldRepo->fetchArray(),
            'validMimeTypes' => MimeType::getProcessableTypes(),
            'showSftp' => StationFeatures::Sftp->supportedForStation($station),
            'supportsImmediateQueue' => $backendEnum->isEnabled(),
        ]);
    }
}
