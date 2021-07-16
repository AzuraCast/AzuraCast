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
            ->setParameter('source', Entity\StationPlaylist::SOURCE_SONGS)
            ->getArrayResult();

        $files_count = $em->createQuery(
            <<<'DQL'
                SELECT COUNT(sm.id) FROM App\Entity\StationMedia sm
                WHERE sm.storage_location = :storageLocation
            DQL
        )->setParameter('storageLocation', $station->getMediaStorageLocation())
            ->getSingleScalarResult();

        $mediaStorage = $station->getMediaStorageLocation();

        return $request->getView()->renderToResponse(
            $response,
            'stations/files/index',
            [
                'show_sftp' => SftpGo::isSupportedForStation($station),
                'playlists' => $playlists,
                'custom_fields' => $customFieldRepo->fetchArray(),
                'mime_types' => MimeType::getProcessableTypes(),
                'space_used' => $mediaStorage->getStorageUsed(),
                'space_total' => $mediaStorage->getStorageAvailable(),
                'space_percent' => $mediaStorage->getStorageUsePercentage(),
                'files_count' => $files_count,
            ]
        );
    }
}
