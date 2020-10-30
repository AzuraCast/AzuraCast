<?php

namespace App\Controller\Stations\Reports;

use App\Entity;
use App\Flysystem\FilesystemManager;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class DuplicatesController
{
    protected EntityManagerInterface $em;

    protected Entity\Repository\StationMediaRepository $mediaRepo;

    protected FilesystemManager $filesystem;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\StationMediaRepository $mediaRepo,
        FilesystemManager $filesystem
    ) {
        $this->em = $em;
        $this->mediaRepo = $mediaRepo;
        $this->filesystem = $filesystem;
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $dupesRaw = $this->em->createQuery(/** @lang DQL */ 'SELECT
            sm, spm, sp
            FROM App\Entity\StationMedia sm
            LEFT JOIN sm.playlists spm
            LEFT JOIN spm.playlist sp
            WHERE sm.storage_location = :storageLocation
            AND (sp.id IS NULL OR sp.station = :station)
            AND sm.song_id IN (
                SELECT sm2.song_id FROM
                App\Entity\StationMedia sm2
                WHERE sm2.storage_location = :storageLocation
                GROUP BY sm2.song_id
                HAVING COUNT(sm2.id) > 1
            )
            ORDER BY sm.song_id ASC, sm.mtime ASC')
            ->setParameteR('storageLocation', $station->getMediaStorageLocation())
            ->setParameter('station', $station)
            ->getArrayResult();

        $dupes = [];
        foreach ($dupesRaw as $row) {
            $row['playlists'] = array_column($row['playlists'], 'playlist');
            $dupes[$row['song_id']][] = $row;
        }

        return $request->getView()->renderToResponse($response, 'stations/reports/duplicates', [
            'dupes' => $dupes ?? [],
        ]);
    }

    public function deleteAction(ServerRequest $request, Response $response, $media_id): ResponseInterface
    {
        $station = $request->getStation();
        $fs = $this->filesystem->getForStation($station);

        $media = $this->mediaRepo->find($media_id, $station);

        if ($media instanceof Entity\StationMedia) {
            $fs->delete($media->getPathUri());

            $this->em->remove($media);
            $this->em->flush();

            $request->getFlash()->addMessage('<b>Duplicate file deleted!</b>', Flash::SUCCESS);
        }

        return $response->withRedirect(
            $request->getRouter()->named(
                'stations:reports:duplicates',
                ['station_id' => $station->getId()]
            )
        );
    }
}
