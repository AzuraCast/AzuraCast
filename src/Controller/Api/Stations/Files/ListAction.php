<?php

namespace App\Controller\Api\Stations\Files;

use App\Entity;
use App\Flysystem\FilesystemManager;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Utilities;
use Doctrine\ORM\EntityManagerInterface;
use Jhofm\FlysystemIterator\Options\Options;
use Psr\Http\Message\ResponseInterface;

use const SORT_ASC;
use const SORT_DESC;

class ListAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        FilesystemManager $filesystem,
        Entity\Repository\StationRepository $stationRepo
    ): ResponseInterface {
        $station = $request->getStation();
        $router = $request->getRouter();

        $storageLocation = $station->getMediaStorageLocation();
        $fs = $filesystem->getFilesystemForAdapter($storageLocation->getStorageAdapter(), true);

        if ((bool)$request->getParam('flushCache', false)) {
            $fs->clearCache();
        }

        $result = [];

        $currentDir = $request->getParam('currentDirectory', '');

        $searchPhrase = trim($request->getParam('searchPhrase', ''));

        $pathLike = (empty($currentDir))
            ? '%'
            : $currentDir . '/%';

        $media_query = $em->createQueryBuilder()
            ->select('partial sm.{
                id,
                unique_id,
                art_updated_at,
                path,
                length,
                length_text,
                artist,
                title,
                album,
                genre
            }')
            ->addSelect('partial spm.{id}, partial sp.{id, name}')
            ->addSelect('partial smcf.{id, field_id, value}')
            ->from(Entity\StationMedia::class, 'sm')
            ->leftJoin('sm.custom_fields', 'smcf')
            ->leftJoin('sm.playlists', 'spm')
            ->leftJoin('spm.playlist', 'sp')
            ->where('sm.storage_location = :storageLocation')
            ->andWhere('sm.path LIKE :path')
            ->andWhere('(sp.station IS NULL OR sp.station = :station)')
            ->setParameter('storageLocation', $station->getMediaStorageLocation())
            ->setParameter('station', $station)
            ->setParameter('path', $pathLike);

        // Apply searching
        if (!empty($searchPhrase)) {
            if (strpos($searchPhrase, 'playlist:') === 0) {
                $playlist_name = substr($searchPhrase, 9);
                $media_query->andWhere('sp.name = :playlist_name')
                    ->setParameter('playlist_name', $playlist_name);
            } else {
                $media_query->andWhere('(sm.title LIKE :query OR sm.artist LIKE :query)')
                    ->setParameter('query', '%' . $searchPhrase . '%');
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
                ->setParameter('path', $currentDir . '%')
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
                : (string)$router->named(
                    'api:stations:media:art',
                    [
                        'station_id' => $station->getId(),
                        'media_id' => $media_row['unique_id'] . '-' . $media_row['art_updated_at'],
                    ]
                );

            $media_in_dir[$media_row['path']] = [
                    'is_playable' => ($media_row['length'] !== 0),
                    'length' => $media_row['length'],
                    'length_text' => $media_row['length_text'],
                    'artist' => $media_row['artist'],
                    'title' => $media_row['title'],
                    'album' => $media_row['album'],
                    'genre' => $media_row['genre'],
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
                        'api:stations:file:download',
                        ['station_id' => $station->getId(), 'id' => $media_row['id']],
                        [],
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
        if (!empty($searchPhrase)) {
            foreach ($media_in_dir as $short_path => $media_row) {
                $files[] = $short_path;
            }
        } else {
            $filesIterator = $fs->createIterator($currentDir, [
                Options::OPTION_IS_RECURSIVE => false,
            ]);

            $protectedPaths = [Entity\StationMedia::DIR_ALBUM_ART, Entity\StationMedia::DIR_WAVEFORMS];

            foreach ($filesIterator as $fileRow) {
                if ($currentDir === '' && in_array($fileRow['path'], $protectedPaths, true)) {
                    continue;
                }

                $files[] = $fileRow['path'];
            }
        }

        foreach ($files as $short) {
            $meta = $fs->getMetadata($short);

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
                'rename_url' => (string)$router->named(
                    'api:stations:files:rename',
                    ['station_id' => $station->getId()],
                    ['file' => $short]
                ),
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

        $sort = $request->getParam('sort');
        if (!empty($sort)) {
            $sort_by[] = $sort;
            $sort_by[] = ('desc' === strtolower($request->getParam('sortOrder', 'asc')))
                ? SORT_DESC
                : SORT_ASC;
        } else {
            $sort_by[] = 'name';
            $sort_by[] = SORT_ASC;
        }

        $result = Utilities::arrayOrderBy($result, $sort_by);

        $num_results = count($result);

        $page = (int)($request->getParam('current', 1));
        $row_count = (int)($request->getParam('rowCount', 15));

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
