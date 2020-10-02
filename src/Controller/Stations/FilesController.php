<?php
namespace App\Controller\Stations;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\SftpGo;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class FilesController
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em
    ): ResponseInterface {
        $station = $request->getStation();

        $playlists = $em->createQuery(/** @lang DQL */ 'SELECT sp.id, sp.name 
            FROM App\Entity\StationPlaylist sp 
            WHERE sp.station_id = :station_id AND sp.source = :source 
            ORDER BY sp.name ASC')
            ->setParameter('station_id', $station->getId())
            ->setParameter('source', Entity\StationPlaylist::SOURCE_SONGS)
            ->getArrayResult();

        $files_count = $em->createQuery(/** @lang DQL */ 'SELECT COUNT(sm.id) FROM App\Entity\StationMedia sm
            WHERE sm.station_id = :station_id')
            ->setParameter('station_id', $station->getId())
            ->getSingleScalarResult();

        // Get list of custom fields.
        $custom_fields_raw = $em->createQuery(/** @lang DQL */ 'SELECT cf.id, cf.short_name, cf.name FROM App\Entity\CustomField cf ORDER BY cf.name ASC')
            ->getArrayResult();

        $custom_fields = [];
        foreach ($custom_fields_raw as $row) {
            $custom_fields[] = [
                'display_key' => 'media_custom_' . $row['id'],
                'key' => $row['short_name'],
                'label' => $row['name'],
            ];
        }

        return $request->getView()->renderToResponse($response, 'stations/files/index', [
            'show_sftp' => SftpGo::isSupported(),
            'playlists' => $playlists,
            'custom_fields' => $custom_fields,
            'space_used' => $station->getStorageUsed(),
            'space_total' => $station->getStorageAvailable(),
            'space_percent' => $station->getStorageUsePercentage(),
            'files_count' => $files_count,
        ]);
    }


}
