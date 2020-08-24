<?php
namespace App\Controller\Api\Stations\Files;

use App\Entity;
use App\Flysystem\Filesystem;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Utilities;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use const SORT_ASC;
use const SORT_DESC;

class ListAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        Filesystem $filesystem,
        Entity\Repository\StationRepository $stationRepo
    ): ResponseInterface {
        $station = $request->getStation();
        $router = $request->getRouter();

        $fs = $filesystem->getForStation($station);
        $params = $request->getParams();

        if ($params['flushCache'] ?? false) {
            $fs->flushAllCaches();
        }

        $result = [];

        $file = $request->getAttribute('file');
        $filePath = $request->getAttribute('file_path');

        $search_phrase = trim($params['searchPhrase'] ?? '');

        $pathLike = (empty($file)) ? '%' : $file . '/%';

        $media_query = $em->createQueryBuilder()
            ->select('partial sm.{id, unique_id, art_updated_at, path, length, length_text, artist, title, album}')
            ->addSelect('partial spm.{id}, partial sp.{id, name}')
            ->addSelect('partial smcf.{id, field_id, value}')
            ->from(Entity\StationMedia::class, 'sm')
            ->leftJoin('sm.custom_fields', 'smcf')
            ->leftJoin('sm.playlists', 'spm')
            ->leftJoin('spm.playlist', 'sp')
            ->where('sm.station_id = :station_id')
            ->andWhere('sm.path LIKE :path')
            ->setParameter('station_id', $station->getId())
            ->setParameter('path', $pathLike);

        // Apply searching
        if (!empty($search_phrase)) {
            if (strpos($search_phrase, 'playlist:') === 0) {
                $playlist_name = substr($search_phrase, 9);
                $media_query->andWhere('sp.name = :playlist_name')
                    ->setParameter('playlist_name', $playlist_name);
            } else {
                $media_query->andWhere('(sm.title LIKE :query OR sm.artist LIKE :query)')
                    ->setParameter('query', '%' . $search_phrase . '%');
            }

            $folders_in_dir_raw = [];
        } else {
            // Avoid loading subfolder media.
            $media_query->andWhere('sm.path NOT LIKE :pathWithSubfolders')
                ->setParameter('pathWithSubfolders', $pathLike . '/%');

            $folders_in_dir_raw = $em->createQuery(/** @lang DQL */ 'SELECT 
                spf, partial sp.{id, name}
                FROM App\Entity\StationPlaylistFolder spf
                JOIN spf.playlist sp
                WHERE spf.station = :station
                AND spf.path LIKE :path')
                ->setParameter('station', $station)
                ->setParameter('path', $file . '%')
                ->getArrayResult();
        }

        $media_in_dir_raw = $media_query->getQuery()
            ->getArrayResult();

        // Process all database results.
        $media_in_dir = [];
        foreach ($media_in_dir_raw as $media_row) {
            $playlists = [];
            foreach ($media_row['playlists'] as $playlist_row) {
                $playlists[] = $playlist_row['playlist']['name'];
            }

            $custom_fields = [];
            foreach ($media_row['custom_fields'] as $custom_field) {
                $custom_fields['custom_' . $custom_field['field_id']] = $custom_field['value'];
            }

            $artImgSrc = (0 === $media_row['art_updated_at'])
                ? (string)$stationRepo->getDefaultAlbumArtUrl($station)
                : (string)$router->named('api:stations:media:art',
                    [
                        'station_id' => $station->getId(),
                        'media_id' => $media_row['unique_id'] . '-' . $media_row['art_updated_at'],
                    ]);

            $media_in_dir[$media_row['path']] = [
                    'is_playable' => ($media_row['length'] !== 0),
                    'length' => $media_row['length'],
                    'length_text' => $media_row['length_text'],
                    'artist' => $media_row['artist'],
                    'title' => $media_row['title'],
                    'album' => $media_row['album'],
                    'name' => $media_row['artist'] . ' - ' . $media_row['title'],
                    'art' => $artImgSrc,
                    'art_url' => (string)$router->named(
                        'api:stations:media:art-internal',
                        ['station_id' => $station->getId(), 'media_id' => $media_row['id']]
                    ),
                    'waveform_url' => (string)$router->named(
                        'api:stations:media:waveform',
                        [
                            'station_id' => $station->getId(),
                            'media_id' => $media_row['unique_id'] . '-' . $media_row['art_updated_at'],
                        ]
                    ),
                    'can_edit' => true,
                    'edit_url' => (string)$router->named(
                        'api:stations:file',
                        ['station_id' => $station->getId(), 'id' => $media_row['id']]
                    ),
                    'play_url' => (string)$router->named(
                        'api:stations:files:download',
                        ['station_id' => $station->getId()],
                        ['file' => $media_row['path']],
                        true
                    ),
                    'playlists' => $playlists,
                ] + $custom_fields;
        }

        $folders_in_dir = [];
        foreach ($folders_in_dir_raw as $folder_row) {
            if (!isset($folders_in_dir[$folder_row['path']])) {
                $folders_in_dir[$folder_row['path']] = [
                    'playlists' => [],
                ];
            }

            $folders_in_dir[$folder_row['path']]['playlists'][] = $folder_row['playlist']['name'];
        }

        $files = [];
        if (!empty($search_phrase)) {
            foreach ($media_in_dir as $short_path => $media_row) {
                $files[] = Filesystem::PREFIX_MEDIA . '://' . $short_path;
            }
        } else {
            $files_raw = $fs->listContents($filePath);
            foreach ($files_raw as $file) {
                $files[] = $file['filesystem'] . '://' . $file['path'];
            }
        }

        foreach ($files as $i) {
            $short = str_replace(Filesystem::PREFIX_MEDIA . '://', '', $i);
            $meta = $fs->getMetadata($i);

            if ('dir' === $meta['type']) {
                $media = ['name' => __('Directory'), 'playlists' => [], 'is_playable' => false];

                if (isset($folders_in_dir[$short])) {
                    $media['playlists'] = $folders_in_dir[$short]['playlists'];
                }
            } elseif (isset($media_in_dir[$short])) {
                $media = $media_in_dir[$short];
            } else {
                $media = ['name' => __('File Not Processed'), 'playlists' => [], 'is_playable' => false];
            }

            $max_length = 60;
            $shortname = $meta['basename'];
            if (mb_strlen($shortname) > $max_length) {
                $shortname = mb_substr($shortname, 0, $max_length - 15) . '...' . mb_substr($shortname, -12);
            }

            $result_row = [
                'mtime' => $meta['timestamp'],
                'size' => $meta['size'],
                'name' => $short,
                'path' => $short,
                'text' => $shortname,
                'is_dir' => ('dir' === $meta['type']),
                'can_rename' => true,
                'rename_url' => (string)$router->named('api:stations:files:rename', ['station_id' => $station->getId()],
                    ['file' => $short]),
            ];

            foreach ($media as $media_key => $media_val) {
                $result_row['media_' . $media_key] = $media_val;
            }

            $result[] = $result_row;
        }

        // Example from bootgrid docs:
        // current=1&rowCount=10&sort[sender]=asc&searchPhrase=&id=b0df282a-0d67-40e5-8558-c9e93b7befed

        // Apply sorting and limiting.
        $sort_by = ['is_dir', SORT_DESC];

        if (!empty($params['sort'])) {
            $sort_by[] = $params['sort'];
            $sort_by[] = (strtolower($params['sortOrder'] ?? 'asc') === 'desc') ? SORT_DESC : SORT_ASC;
        } else {
            $sort_by[] = 'name';
            $sort_by[] = SORT_ASC;
        }

        $result = Utilities::arrayOrderBy($result, $sort_by);

        $num_results = count($result);

        $page = (int)($params['current'] ?? 1);
        $row_count = (int)($params['rowCount'] ?? 15);

        if ($row_count === 0) {
            $row_count = $num_results;
        }

        if ($num_results > 0 && $row_count > 0) {
            $offset_start = ($page - 1) * $row_count;
            if ($offset_start >= $num_results) {
                $page = floor($num_results / $row_count);
                $offset_start = ($page - 1) * $row_count;
            }

            $return_result = array_slice($result, $offset_start, $row_count);
        } else {
            $return_result = [];
        }

        return $response->withJson([
            'current' => $page,
            'rowCount' => $row_count,
            'total' => $num_results,
            'rows' => $return_result,
        ]);
    }
}
