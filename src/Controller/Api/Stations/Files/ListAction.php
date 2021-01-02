<?php

namespace App\Controller\Api\Stations\Files;

use App\Entity;
use App\Flysystem\FilesystemManager;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator;
use App\Utilities;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Jhofm\FlysystemIterator\Options\Options;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

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

        $isInternal = (bool)$request->getParam('internal', false);

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
                $media = new Entity\Api\FileListMedia();

                $media->title = (string)$media_row['title'];
                $media->artist = (string)$media_row['artist'];
                $media->text = $media_row['artist'] . ' - ' . $media_row['title'];
                $media->album = (string)$media_row['album'];
                $media->genre = (string)$media_row['genre'];

                $media->is_playable = ($media_row['length'] !== 0);
                $media->length = $media_row['length'];
                $media->length_text = $media_row['length_text'];

                $media->art = (0 === $media_row['art_updated_at'])
                    ? (string)$stationRepo->getDefaultAlbumArtUrl($station)
                    : (string)$router->named(
                        'api:stations:media:art',
                        [
                            'station_id' => $station->getId(),
                            'media_id' => $media_row['unique_id'] . '-' . $media_row['art_updated_at'],
                        ]
                    );

                foreach ($media_row['custom_fields'] as $custom_field) {
                    $media->custom_fields[$custom_field['field_id']] = $custom_field['value'];
                }

                $media->links = [
                    'play' => (string)$router->named(
                        'api:stations:files:play',
                        ['station_id' => $station->getId(), 'id' => $media_row['id']],
                        [],
                        true
                    ),
                    'edit' => (string)$router->named(
                        'api:stations:file',
                        ['station_id' => $station->getId(), 'id' => $media_row['id']]
                    ),
                    'art' => (string)$router->named(
                        'api:stations:media:art-internal',
                        ['station_id' => $station->getId(), 'media_id' => $media_row['id']]
                    ),
                    'waveform' => (string)$router->named(
                        'api:stations:media:waveform',
                        [
                            'station_id' => $station->getId(),
                            'media_id' => $media_row['unique_id'] . '-' . $media_row['art_updated_at'],
                        ]
                    ),
                ];

                $playlists = [];
                foreach ($media_row['playlists'] as $playlist_row) {
                    if (isset($playlist_row['playlist'])) {
                        $playlists[] = [
                            'id' => $playlist_row['playlist']['id'],
                            'name' => $playlist_row['playlist']['name'],
                        ];
                    }
                }

                $mediaInDir[$media_row['path']] = [
                    'media' => $media,
                    'playlists' => $playlists,
                ];
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

                $row = new Entity\Api\FileList();
                $row->path = $path;

                $max_length = 60;
                $shortname = $meta['basename'];
                if (mb_strlen($shortname) > $max_length) {
                    $shortname = mb_substr($shortname, 0, $max_length - 15) . '...' . mb_substr($shortname, -12);
                }
                $row->path_short = $shortname;

                $row->timestamp = $meta['timestamp'];
                $row->size = $meta['size'];
                $row->is_dir = ('dir' === $meta['type']);

                $row->media = new Entity\Api\FileListMedia();

                if (isset($mediaInDir[$path])) {
                    $row->media = $mediaInDir[$path]['media'];
                    $row->text = $row->media->text;
                    $row->playlists = (array)$mediaInDir[$path]['playlists'];
                } elseif ('dir' === $meta['type']) {
                    $row->text = __('Directory');

                    if (isset($folders_in_dir[$path])) {
                        $row->playlists = (array)$folders_in_dir[$path]['playlists'];
                    }
                } elseif (isset($unprocessableMedia[$path])) {
                    $row->text = __(
                        'File Not Processed: %s',
                        Utilities\Strings::truncateText($unprocessableMedia[$path])
                    );
                } else {
                    $row->text = __('File Processing');
                }

                $row->links = [
                    'download' => (string)$router->named(
                        'api:stations:files:download',
                        ['station_id' => $station->getId()],
                        ['file' => $path]
                    ),
                    'rename' => (string)$router->named(
                        'api:stations:files:rename',
                        ['station_id' => $station->getId()],
                        ['file' => $path]
                    ),
                ];

                $result[] = $row;
            }

            $cache->set($cacheKey, $result, 300);
        }

        // Apply array flattening for internal results
        if ($isInternal) {
            $result = array_map(
                function (Entity\Api\FileList $row) {
                    $playlists = $row->playlists;
                    $row->playlists = [];

                    $row = Utilities\Arrays::flattenArray($row, '_');
                    $row['playlists'] = $playlists;

                    return $row;
                },
                $result
            );
        }

        // Apply sorting
        $resultCollection = new ArrayCollection($result);

        $sort = $request->getParam('sort');
        $sortOrder = ('desc' === strtolower($request->getParam('sortOrder', 'asc')))
            ? Criteria::DESC
            : Criteria::ASC;

        $sortBy = ['is_dir' => Criteria::DESC];

        if (!empty($sort)) {
            $sortBy[$sort] = $sortOrder;
        } else {
            $sortBy['path'] = Criteria::ASC;
        }

        $resultCollection = $resultCollection->matching(Criteria::create()->orderBy($sortBy));

        $paginator = Paginator::fromCollection($resultCollection, $request);
        return $paginator->write($response);
    }
}
