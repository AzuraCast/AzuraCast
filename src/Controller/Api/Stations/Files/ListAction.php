<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Entity;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\RouterInterface;
use App\Http\ServerRequest;
use App\Paginator;
use App\Utilities;
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

        $fs = (new StationFilesystems($station))->getMediaFilesystem();

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
                    } elseif (str_starts_with($searchPhrase, 'playlist:')) {
                        [, $playlistName] = explode(':', $searchPhrase, 2);

                        $playlist = $em->getRepository(Entity\StationPlaylist::class)
                            ->findOneBy(
                                [
                                    'station' => $station,
                                    'name' => $playlistName,
                                ]
                            );

                        if (!$playlist instanceof Entity\StationPlaylist) {
                            return $response->withStatus(400)
                                ->withJson(new Entity\Api\Error(400, 'Playlist not found.'));
                        }

                        $mediaQueryBuilder->andWhere(
                            'sm.id IN (SELECT spm2.media_id FROM App\Entity\StationPlaylistMedia spm2 '
                            . 'WHERE spm2.playlist = :playlist)'
                        )->setParameter('playlist', $playlist);
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
                $media->length = (int)$row['length'];
                $media->length_text = $row['length_text'];

                $media->media_id = $row['id'];
                $media->unique_id = $row['unique_id'];
                $media->art_updated_at = $row['art_updated_at'];

                foreach ((array)$row['custom_fields'] as $custom_field) {
                    $media->custom_fields[$custom_field['field_id']] = $custom_field['value'];
                }

                $playlists = [];
                foreach ($row['playlists'] as $spmRow) {
                    if (!isset($spmRow['playlist'])) {
                        continue;
                    }

                    $playlistId = $spmRow['playlist']['id'];
                    if (isset($playlists[$playlistId])) {
                        $playlists[$playlistId]['count']++;
                    } else {
                        $playlists[$playlistId] = [
                            'id' => $playlistId,
                            'name' => $spmRow['playlist']['name'],
                            'count' => 1,
                        ];
                    }
                }

                $mediaInDir[$row['path']] = [
                    'media' => $media,
                    'playlists' => array_values($playlists),
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

                $result[] = $row;
            }

            $cache->set($cacheKey, $result, 300);
        }

        // Apply sorting
        $sort = $request->getParam('sort');
        $sortOrder = ('desc' === strtolower($request->getParam('sortOrder', 'asc')))
            ? Criteria::DESC
            : Criteria::ASC;

        usort(
            $result,
            static function (Entity\Api\FileList $a, Entity\Api\FileList $b) use ($searchPhrase, $sort, $sortOrder) {
                return self::sortRows($a, $b, $searchPhrase, $sort, $sortOrder);
            }
        );

        $paginator = Paginator::fromArray($result, $request);

        // Add processor-intensive data for just this page.
        $stationId = $station->getIdRequired();
        $isInternal = (bool)$request->getParam('internal', false);
        $defaultAlbumArtUrl = (string)$stationRepo->getDefaultAlbumArtUrl($station);

        $paginator->setPostprocessor(
            static function (Entity\Api\FileList $row) use ($router, $stationId, $defaultAlbumArtUrl, $isInternal) {
                return self::postProcessRow($row, $router, $stationId, $defaultAlbumArtUrl, $isInternal);
            }
        );

        return $paginator->write($response);
    }

    protected static function sortRows(
        Entity\Api\FileList $a,
        Entity\Api\FileList $b,
        ?string $searchPhrase = null,
        ?string $sort = null,
        ?string $sortOrder = Criteria::ASC
    ): int {
        if ('special:duplicates' === $searchPhrase) {
            return $a->media->id <=> $b->media->id;
        }

        $isDirComp = $b->is_dir <=> $a->is_dir;
        if (0 !== $isDirComp) {
            return $isDirComp;
        }

        if (!empty($sort)) {
            if (str_starts_with($sort, 'media_custom_fields_')) {
                $property = str_replace('media_custom_fields_', '', $sort);
                $aVal = $a->media->custom_fields[$property] ?? null;
                $bVal = $b->media->custom_fields[$property] ?? null;

                return (Criteria::ASC === $sortOrder)
                    ? $aVal <=> $bVal
                    : $bVal <=> $aVal;
            }

            if (str_starts_with($sort, 'media_')) {
                $property = str_replace('media_', '', $sort);
                $aVal = property_exists($a->media, $property) ? $a->media->{$property} : null;
                $bVal = property_exists($b->media, $property) ? $b->media->{$property} : null;

                return (Criteria::ASC === $sortOrder)
                    ? $aVal <=> $bVal
                    : $bVal <=> $aVal;
            }

            $aVal = property_exists($a, $sort) ? $a->{$sort} : null;
            $bVal = property_exists($b, $sort) ? $b->{$sort} : null;

            return (Criteria::ASC === $sortOrder)
                ? $aVal <=> $bVal
                : $bVal <=> $aVal;
        }

        return $a->path <=> $b->path;
    }

    protected static function postProcessRow(
        Entity\Api\FileList $row,
        RouterInterface $router,
        int $stationId,
        string $defaultAlbumArtUrl,
        bool $isInternal
    ): Entity\Api\FileList|array {
        if (null !== $row->media->media_id) {
            $row->media->art = (0 === $row->media->art_updated_at)
                ? $defaultAlbumArtUrl
                : (string)$router->named(
                    'api:stations:media:art',
                    [
                        'station_id' => $stationId,
                        'media_id' => $row->media->unique_id . '-' . $row->media->art_updated_at,
                    ]
                );

            $row->media->links = [
                'play' => (string)$router->named(
                    'api:stations:files:play',
                    ['station_id' => $stationId, 'id' => $row->media->media_id],
                    [],
                    true
                ),
                'edit' => (string)$router->named(
                    'api:stations:file',
                    ['station_id' => $stationId, 'id' => $row->media->media_id],
                ),
                'art' => (string)$router->named(
                    'api:stations:media:art-internal',
                    ['station_id' => $stationId, 'media_id' => $row->media->media_id]
                ),
                'waveform' => (string)$router->named(
                    'api:stations:media:waveform',
                    [
                        'station_id' => $stationId,
                        'media_id' => $row->media->unique_id . '-' . $row->media->art_updated_at,
                    ]
                ),
            ];
        }

        $row->links = [
            'download' => (string)$router->named(
                'api:stations:files:download',
                ['station_id' => $stationId],
                ['file' => $row->path]
            ),
            'rename' => (string)$router->named(
                'api:stations:files:rename',
                ['station_id' => $stationId],
                ['file' => $row->path]
            ),
        ];

        if ($isInternal) {
            $playlists = $row->playlists;
            $row->playlists = [];

            $flatRow = Utilities\Arrays::flattenArray($row, '_');
            $flatRow['playlists'] = $playlists;
            return $flatRow;
        }

        return $row;
    }
}
