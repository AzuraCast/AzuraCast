<?php

namespace App\Controller\Api\Stations\Files;

use App\Entity;
use App\Flysystem\FilesystemManager;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator;
use App\Utilities;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Jhofm\FlysystemIterator\Options\Options;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

use const SORT_ASC;
use const SORT_DESC;

class ListAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        CacheInterface $cache,
        FilesystemManager $filesystem,
        Entity\Repository\StationRepository $stationRepo
    ): ResponseInterface {
        $station = $request->getStation();
        $router = $request->getRouter();

        $storageLocation = $station->getMediaStorageLocation();
        $fs = $filesystem->getFilesystemForAdapter($storageLocation->getStorageAdapter(), true);

        $flushCache = (bool)$request->getParam('flushCache', false);
        if ($flushCache) {
            $fs->clearCache();
        }

        $currentDir = $request->getParam('currentDirectory', '');
        $searchPhrase = trim($request->getParam('searchPhrase', ''));

        $cacheKeyParts = [
            'files_list',
            $station->getId(),
            'dir_' . rawurlencode($currentDir),
            'search_' . rawurlencode($searchPhrase),
        ];
        $cacheKey = implode('.', $cacheKeyParts);

        if (!$flushCache && $cache->has($cacheKey)) {
            $result = $cache->get($cacheKey);
        } else {
            $result = [];

            $pathLike = (empty($currentDir))
                ? '%'
                : $currentDir . '/%';

            $media_query = $em->createQueryBuilder()
                ->select(
                    'partial sm.{id, unique_id, art_updated_at, path, length, length_text, '
                    . 'artist, title, album, genre}'
                )
                ->addSelect('partial spm.{id}, partial sp.{id, name}')
                ->addSelect('partial smcf.{id, field_id, value}')
                ->from(Entity\StationMedia::class, 'sm')
                ->leftJoin('sm.custom_fields', 'smcf')
                ->leftJoin('sm.playlists', 'spm')
                ->leftJoin('spm.playlist', 'sp', Expr\Join::WITH, 'sp.station = :station')
                ->where('sm.storage_location = :storageLocation')
                ->andWhere('sm.path LIKE :path')
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

                $unprocessableMediaRaw = [];
            } else {
                // Avoid loading subfolder media.
                $media_query->andWhere('sm.path NOT LIKE :pathWithSubfolders')
                    ->setParameter('pathWithSubfolders', $pathLike . '/%');

                $folders_in_dir_raw = $em->createQuery(
                    <<<'DQL'
                    SELECT spf, partial sp.{id, name}
                    FROM App\Entity\StationPlaylistFolder spf
                    JOIN spf.playlist sp
                    WHERE spf.station = :station
                    AND spf.path LIKE :path
                DQL
                )->setParameter('station', $station)
                    ->setParameter('path', $currentDir . '%')
                    ->getArrayResult();

                $unprocessableMediaRaw = $em->createQuery(
                    <<<'DQL'
                    SELECT upm
                    FROM App\Entity\UnprocessableMedia upm
                    WHERE upm.storage_location = :storageLocation
                    AND upm.path LIKE :path
                DQL
                )->setParameter('storageLocation', $storageLocation)
                    ->setParameter('path', $currentDir . '%')
                    ->getArrayResult();
            }

            $media_in_dir_raw = $media_query->getQuery()
                ->getArrayResult();

            // Process all database results.
            $mediaInDir = [];

            foreach ($media_in_dir_raw as $media_row) {
                $playlists = [];
                foreach ($media_row['playlists'] as $playlist_row) {
                    if (isset($playlist_row['playlist'])) {
                        $playlists[] = [
                            'id' => $playlist_row['playlist']['id'],
                            'name' => $playlist_row['playlist']['name'],
                        ];
                    }
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

                $mediaInDir[$media_row['path']] = [
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
                        'edit_url' => (string)$router->named(
                            'api:stations:file',
                            ['station_id' => $station->getId(), 'id' => $media_row['id']]
                        ),
                        'play_url' => (string)$router->named(
                            'api:stations:files:play',
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

                $folders_in_dir[$folder_row['path']]['playlists'][] = [
                    'id' => $folder_row['playlist']['id'],
                    'name' => $folder_row['playlist']['name'],
                ];
            }

            $unprocessableMedia = [];
            foreach ($unprocessableMediaRaw as $unprocessableRow) {
                $unprocessableMedia[$unprocessableRow['path']] = $unprocessableRow['error'];
            }

            if (!empty($searchPhrase)) {
                $files = array_keys($mediaInDir);
            } else {
                $filesIterator = $fs->createIterator(
                    $currentDir,
                    [
                        Options::OPTION_IS_RECURSIVE => false,
                    ]
                );

                $protectedPaths = [Entity\StationMedia::DIR_ALBUM_ART, Entity\StationMedia::DIR_WAVEFORMS];

                $files = [];
                foreach ($filesIterator as $fileRow) {
                    if ($currentDir === '' && in_array($fileRow['path'], $protectedPaths, true)) {
                        continue;
                    }

                    $files[] = $fileRow['path'];
                }
            }

            foreach ($files as $path) {
                $meta = $fs->getMetadata($path);

                if ('dir' === $meta['type']) {
                    $media = ['name' => __('Directory'), 'playlists' => [], 'is_playable' => false];

                    if (isset($folders_in_dir[$path])) {
                        $media['playlists'] = $folders_in_dir[$path]['playlists'];
                    }
                } elseif (isset($mediaInDir[$path])) {
                    $media = $mediaInDir[$path];
                } elseif (isset($unprocessableMedia[$path])) {
                    $media = [
                        'name' => __(
                            'File Not Processed: %s',
                            Utilities\Strings::truncateText($unprocessableMedia[$path])
                        ),
                    ];
                } else {
                    $media = [
                        'name' => __('File Processing'),
                    ];
                }

                $media['playlists'] ??= [];
                $media['is_playable'] ??= false;

                $max_length = 60;
                $shortname = $meta['basename'];
                if (mb_strlen($shortname) > $max_length) {
                    $shortname = mb_substr($shortname, 0, $max_length - 15) . '...' . mb_substr($shortname, -12);
                }

                $result_row = [
                    'mtime' => $meta['timestamp'],
                    'size' => $meta['size'],
                    'name' => $path,
                    'path' => $path,
                    'text' => $shortname,
                    'is_dir' => ('dir' === $meta['type']),
                    'download_url' => (string)$router->named(
                        'api:stations:files:download',
                        ['station_id' => $station->getId()],
                        ['file' => $path]
                    ),
                    'rename_url' => (string)$router->named(
                        'api:stations:files:rename',
                        ['station_id' => $station->getId()],
                        ['file' => $path]
                    ),
                ];

                foreach ($media as $media_key => $media_val) {
                    $result_row['media_' . $media_key] = $media_val;
                }

                $result[] = $result_row;
            }

            $cache->set($cacheKey, $result, 300);
        }

        // Apply sorting
        $sort = $request->getParam('sort');
        $sortOrder = ('desc' === strtolower($request->getParam('sortOrder', 'asc')))
            ? SORT_DESC
            : SORT_ASC;

        $sortBy = ['is_dir', SORT_DESC];

        if (!empty($sort)) {
            $sortBy[] = $sort;
            $sortBy[] = $sortOrder;
        } else {
            $sortBy[] = 'name';
            $sortBy[] = SORT_ASC;
        }

        $result = Utilities\Arrays::arrayOrderBy($result, $sortBy);

        $paginator = Paginator::fromArray($result, $request);
        return $paginator->write($response);
    }
}
