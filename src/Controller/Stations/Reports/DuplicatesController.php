<?php
namespace App\Controller\Stations\Reports;

use App\Radio\Filesystem;
use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;

class DuplicatesController
{
    /** @var EntityManager */
    protected $em;

    /** @var Filesystem */
    protected $filesystem;

    /**
     * @param EntityManager $em
     * @param Filesystem $filesystem
     *
     * @see \App\Provider\StationsProvider
     */
    public function __construct(EntityManager $em, Filesystem $filesystem)
    {
        $this->em = $em;
        $this->filesystem = $filesystem;
    }

    public function __invoke(Request $request, Response $response, $station_id): ResponseInterface
    {
        $media_raw = $this->em->createQuery(/** @lang DQL */'SELECT 
            sm, s, spm, sp 
            FROM App\Entity\StationMedia sm 
            JOIN sm.song s 
            LEFT JOIN sm.playlist_items spm 
            LEFT JOIN spm.playlist sp 
            WHERE sm.station_id = :station_id 
            ORDER BY sm.mtime ASC')
            ->setParameter('station_id', $station_id)
            ->getArrayResult();

        $dupes = [];
        $songs_to_compare = [];

        // Find exact duplicates and sort other songs into a searchable array.
        foreach ($media_raw as $media_row) {
            foreach($media_row['playlist_items'] as $playlist_item) {
                $media_row['playlists'][] = $playlist_item['playlist'];
            }

            if (isset($songs_to_compare[$media_row['song_id']])) {
                $dupes[] = [$songs_to_compare[$media_row['song_id']], $media_row];
            } else {
                $songs_to_compare[$media_row['song_id']] = $media_row;
            }
        }

        foreach ($songs_to_compare as $song_id => $media_row) {
            unset($songs_to_compare[$song_id]);

            $media_text = strtolower(preg_replace("/[^A-Za-z0-9]/", '', $media_row['song']['text']));

            $song_dupes = [];
            foreach ($songs_to_compare as $search_song_id => $search_media_row) {
                $search_media_text = strtolower(preg_replace("/[^A-Za-z0-9]/", '', $search_media_row['song']['text']));
                $similarity = levenshtein($media_text, $search_media_text);

                if ($similarity <= 5) {
                    $song_dupes[] = $search_media_row;
                }
            }

            if (count($song_dupes) > 0) {
                $song_dupes[] = $media_row;
                $dupes[] = $song_dupes;
            }
        }

        return $request->getView()->renderToResponse($response, 'stations/reports/duplicates', [
            'dupes' => $dupes,
        ]);
    }

    public function deleteAction(Request $request, Response $response, $station_id, $media_id): ResponseInterface
    {
        $station = $request->getStation();
        $fs = $this->filesystem->getForStation($station);

        /** @var Entity\Repository\StationMediaRepository $media_repo */
        $media_repo = $this->em->getRepository(Entity\StationMedia::class);
        $media = $media_repo->findOneBy([
            'id' => $media_id,
            'station_id' => $station_id
        ]);

        if ($media instanceof Entity\StationMedia) {
            $fs->delete($media->getPathUri());

            $this->em->remove($media);
            $this->em->flush();

            $request->getSession()->flash('<b>Duplicate file deleted!</b>', 'green');
        }

        return $response->withRedirect($request->getRouter()->named('stations:reports:duplicates', ['station' => $station_id]));
    }
}
