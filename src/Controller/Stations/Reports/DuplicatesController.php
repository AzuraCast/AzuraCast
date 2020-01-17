<?php
namespace App\Controller\Stations\Reports;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Filesystem;
use Azura\Session\Flash;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;

class DuplicatesController
{
    protected EntityManager $em;

    protected Entity\Repository\StationMediaRepository $mediaRepo;

    protected Filesystem $filesystem;

    public function __construct(
        EntityManager $em,
        Entity\Repository\StationMediaRepository $mediaRepo,
        Filesystem $filesystem
    ) {
        $this->em = $em;
        $this->mediaRepo = $mediaRepo;
        $this->filesystem = $filesystem;
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $duplicateSongIdsRaw = $this->em->createQuery(/** @lang DQL */ 'SELECT
            sm.song_id
            FROM App\Entity\StationMedia sm
            WHERE sm.station = :station GROUP BY sm.song_id HAVING COUNT(sm.id) > 1')
            ->setParameter('station', $station)
            ->getArrayResult();

        if (!empty($duplicateSongIdsRaw)) {
            $duplicateSongIds = array_column($duplicateSongIdsRaw, 'song_id');

            $dupesRaw = $this->em->createQuery(/** @lang DQL */ 'SELECT
                sm, s, spm, sp
                FROM App\Entity\StationMedia sm
                JOIN sm.song s
                LEFT JOIN sm.playlists spm
                LEFT JOIN spm.playlist sp
                WHERE sm.station = :station
                AND sm.song_id IN (:song_ids)
                ORDER BY sm.song_id ASC, sm.mtime ASC')
                ->setParameter('station', $station)
                ->setParameter('song_ids', $duplicateSongIds)
                ->getArrayResult();

            $dupes = [];
            foreach ($dupesRaw as $row) {
                $row['playlists'] = array_column($row['playlists'], 'playlist');
                $dupes[$row['song_id']][] = $row;
            }
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

        return $response->withRedirect($request->getRouter()->named('stations:reports:duplicates',
            ['station_id' => $station->getId()]));
    }
}
