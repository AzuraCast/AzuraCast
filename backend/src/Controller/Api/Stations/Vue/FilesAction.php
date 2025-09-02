<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Vue;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Stations\Vue\FilesProps;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Repository\CustomFieldRepository;
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
                WHERE sp.station = :station AND sp.source = :source
                ORDER BY sp.name ASC
            DQL
        )->setParameter('station', $station)
            ->setParameter('source', PlaylistSources::Songs->value)
            ->getArrayResult();

        $backendEnum = $station->backend_type;

        return $response->withJson(
            new FilesProps(
                initialPlaylists: $playlists,
                customFields: $this->customFieldRepo->fetchArray(),
                validMimeTypes: MimeType::getProcessableTypes(),
                supportsImmediateQueue: $backendEnum->isEnabled()
            )
        );
    }
}
