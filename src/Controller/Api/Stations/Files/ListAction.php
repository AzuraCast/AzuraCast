<?php

namespace App\Controller\Api\Stations\Files;

use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator;
use App\Utilities;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use League\Flysystem\StorageAttributes;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

class ListAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        CacheInterface $cache,
        Entity\Repository\StationRepository $stationRepo
    ): ResponseInterface {
        $router = $request->getRouter();

        $station = $request->getStation();
        $storageLocation = $station->getMediaStorageLocation();

        $fsStation = new StationFilesystems($station);
        $fs = $fsStation->getMediaFilesystem();

        $currentDir = $request->getParam('currentDirectory', '');

        $searchPhrase = trim($request->getParam('searchPhrase', ''));
        $isSearch = !empty($searchPhrase);

        $cacheKeyParts = [
            'files_list',
            $station->getId(),
            (!empty($currentDir)) ? 'dir_' . rawurlencode($currentDir) : 'root',
        ];

        if ($isSearch) {
            $cacheKeyParts[] = 'search_' . rawurlencode($searchPhrase);
        }

        $cacheKey = implode('.', $cacheKeyParts);

        $flushCache = (bool)$request->getParam('flushCache', false);

        if (!$flushCache && $cache->has($cacheKey)) {
            $result = $cache->get($cacheKey);
        } else {
            $result = [];

            $pathLike = (empty($currentDir))
                ? '%'
                : $currentDir . '/%';

            $mediaQueryBuilder = $em->createQueryBuilder()
                ->select(['sm', 'spm', 'sp', 'smcf'])
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
            $foldersInDirQuery = $em->createQuery(
                <<<'DQL'
                    SELECT spf, sp
                    FROM App\Entity\StationPlaylistFolder spf
                    JOIN spf.playlist sp
                    WHERE spf.station = :station
                    AND spf.path LIKE :path
                DQL
            )->setParameter('station', $station)
                ->setParameter('path', $pathLike);

            $unprocessableMediaQuery = $em->createQuery(
                <<<'DQL'
                    SELECT upm
                    FROM App\Entity\UnprocessableMedia upm
                    WHERE upm.storage_location = :storageLocation
                    AND upm.path LIKE :path
                DQL
            )->setParameter('storageLocation', $storageLocation)
                ->setParameter('path', $pathLike);

            if (!empty($searchPhrase)) {
                if ('special:unprocessable' === $searchPhrase) {
                    $mediaInDirRaw = [];

                    $unprocessableMediaRaw = $unprocessableMediaQuery->toIterable(
                        [],
                        $unprocessableMediaQuery::HYDRATE_ARRAY
                    );
                } else {
                    if ('special:duplicates' === $searchPhrase) {
                        $mediaQueryBuilder->andWhere(
                            $mediaQueryBuilder->expr()->in(
                                'sm.song_id',
                                <<<'DQL'
                                    SELECT sm2.song_id FROM
                                    App\Entity\StationMedia sm2
                                    WHERE sm2.storage_location = :storageLocation
                                    GROUP BY sm2.song_id
                                    HAVING COUNT(sm2.id) > 1
                                DQL
                            )
                        );
                    } elseif (0 === strpos($searchPhrase, 'playlist:')) {
                        [, $playlistName] = explode(':', $searchPhrase, 2);

                        $mediaQueryBuilder->andWhere('sp.name = :playlist_name')
                            ->setParameter('playlist_name', $playlistName);
                    } else {
                        $mediaQueryBuilder->andWhere('(sm.title LIKE :query OR sm.artist LIKE :query)')
                            ->setParameter('query', '%' . $searchPhrase . '%');
                    }

                    $mediaQuery = $mediaQueryBuilder->getQuery();
                    $mediaInDirRaw = $mediaQuery->getArrayResult();

                    $unprocessableMediaRaw = [];
                }

                $foldersInDirRaw = [];
            } else {
                // Avoid loading subfolder media.
                $mediaQueryBuilder->andWhere('sm.path NOT LIKE :pathWithSubfolders')
                    ->setParameter('pathWithSubfolders', $pathLike . '/%');

                $mediaQuery = $mediaQueryBuilder->getQuery();
                $mediaInDirRaw = $mediaQuery->getArrayResult();

                $foldersInDirRaw = $foldersInDirQuery->getArrayResult();

                $unprocessableMediaRaw = $unprocessableMediaQuery->toIterable(
                    [],
                    $unprocessableMediaQuery::HYDRATE_ARRAY
                );
            }

            // Process all database results.
            $mediaInDir = [];
            foreach ($mediaInDirRaw as $row) {
                $media = new Entity\Api\FileListMedia();

                $media->id = (string)$row['song_id'];
                $media->title = (string)$row['title'];
                $media->artist = (string)$row['artist'];
                $media->text = $row['artist'] . ' - ' . $row['title'];
                $media->album = (string)$row['album'];
                $media->genre = (string)$row['genre'];

                $media->is_playable = ($row['length'] !== 0);
                $media->length = $row['length'];
                $media->length_text = $row['length_text'];

                $media->art = (0 === $row['art_updated_at'])
                    ? (string)$stationRepo->getDefaultAlbumArtUrl($station)
                    : (string)$router->named(
                        'api:stations:media:art',
                        [
                            'station_id' => $station->getId(),
                            'media_id' => $row['unique_id'] . '-' . $row['art_updated_at'],
                        ]
                    );

                foreach ((array)$row['custom_fields'] as $custom_field) {
                    $media->custom_fields[$custom_field['field_id']] = $custom_field['value'];
                }

                $media->links = [
                    'play' => (string)$router->named(
                        'api:stations:files:play',
                        ['station_id' => $station->getId(), 'id' => $row['id']],
                        [],
                        true
                    ),
                    'edit' => (string)$router->named(
                        'api:stations:file',
                        ['station_id' => $station->getId(), 'id' => $row['id']]
                    ),
                    'art' => (string)$router->named(
                        'api:stations:media:art-internal',
                        ['station_id' => $station->getId(), 'media_id' => $row['id']]
                    ),
                    'waveform' => (string)$router->named(
                        'api:stations:media:waveform',
                        [
                            'station_id' => $station->getId(),
                            'media_id' => $row['unique_id'] . '-' . $row['art_updated_at'],
                        ]
                    ),
                ];

                $playlists = [];
                foreach ($row['playlists'] as $spmRow) {
                    if (isset($spmRow['playlist'])) {
                        $playlists[] = [
                            'id' => $spmRow['playlist']['id'],
                            'name' => $spmRow['playlist']['name'],
                        ];
                    }
                }

                $mediaInDir[$row['path']] = [
                    'media' => $media,
                    'playlists' => $playlists,
                ];
            }

            $foldersInDir = [];
            foreach ($foldersInDirRaw as $folderRow) {
                if (!isset($foldersInDir[$folderRow['path']])) {
                    $foldersInDir[$folderRow['path']] = [
                        'playlists' => [],
                    ];
                }

                $foldersInDir[$folderRow['path']]['playlists'][] = [
                    'id' => $folderRow['playlist']['id'],
                    'name' => $folderRow['playlist']['name'],
                ];
            }

            $unprocessableMedia = [];
            foreach ($unprocessableMediaRaw as $unprocessableRow) {
                $unprocessableMedia[$unprocessableRow['path']] = $unprocessableRow['error'];
            }

            if (!empty($searchPhrase)) {
                if ('special:unprocessable' === $searchPhrase) {
                    $files = array_keys($unprocessableMedia);
                } else {
                    $files = array_keys($mediaInDir);
                }
            } else {
                $protectedPaths = [Entity\StationMedia::DIR_ALBUM_ART, Entity\StationMedia::DIR_WAVEFORMS];

                $files = $fs->listContents($currentDir, false)->filter(
                    function (StorageAttributes $attributes) use ($currentDir, $protectedPaths) {
                        return !($currentDir === '' && in_array($attributes->path(), $protectedPaths, true));
                    }
                );
            }

            foreach ($files as $file) {
                $row = new Entity\Api\FileList();

                if ($file instanceof StorageAttributes) {
                    $row->path = $file->path();
                    $row->timestamp = $file->lastModified() ?? 0;
                    $row->is_dir = $file->isDir();
                } else {
                    $row->path = $file;
                    $row->timestamp = $fs->lastModified($file) ?? 0;
                    $row->is_dir = false;
                }

                $row->size = ($row->is_dir) ? 0 : $fs->fileSize($row->path);

                $shortname = (!empty($searchPhrase))
                    ? $row->path
                    : basename($row->path);

                $max_length = 60;
                if (mb_strlen($shortname) > $max_length) {
                    $shortname = mb_substr($shortname, 0, $max_length - 15) . '...' . mb_substr($shortname, -12);
                }
                $row->path_short = $shortname;

                $row->media = new Entity\Api\FileListMedia();

                if (isset($mediaInDir[$row->path])) {
                    $row->media = $mediaInDir[$row->path]['media'];
                    $row->text = $row->media->text;
                    $row->playlists = (array)$mediaInDir[$row->path]['playlists'];
                } elseif ($row->is_dir) {
                    $row->text = __('Directory');

                    if (isset($foldersInDir[$row->path])) {
                        $row->playlists = (array)$foldersInDir[$row->path]['playlists'];
                    }
                } elseif (isset($unprocessableMedia[$row->path])) {
                    $row->text = __(
                        'File Not Processed: %s',
                        Utilities\Strings::truncateText($unprocessableMedia[$row->path])
                    );
                } else {
                    $row->text = __('File Processing');
                }

                $row->links = [
                    'download' => (string)$router->named(
                        'api:stations:files:download',
                        ['station_id' => $station->getId()],
                        ['file' => $row->path]
                    ),
                    'rename' => (string)$router->named(
                        'api:stations:files:rename',
                        ['station_id' => $station->getId()],
                        ['file' => $row->path]
                    ),
                ];

                $result[] = $row;
            }

            $cache->set($cacheKey, $result, 300);
        }

        // Apply array flattening for internal results
        $isInternal = (bool)$request->getParam('internal', false);

        $sort = $request->getParam('sort');
        $sortOrder = ('desc' === strtolower($request->getParam('sortOrder', 'asc')))
            ? Criteria::DESC
            : Criteria::ASC;

        if ($isInternal || !empty($sort)) {
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

        $sortBy = ['is_dir' => Criteria::DESC];

        if ('special:duplicates' === $searchPhrase) {
            $sortBy['media_id'] = Criteria::ASC;
        } elseif (!empty($sort)) {
            $sortBy[$sort] = $sortOrder;
        } else {
            $sortBy['path'] = Criteria::ASC;
        }

        $resultCollection = $resultCollection->matching(Criteria::create()->orderBy($sortBy));

        $paginator = Paginator::fromCollection($resultCollection, $request);
        return $paginator->write($response);
    }
}
