<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Container\EntityManagerAwareTrait;
use App\Controller\Api\Traits\CanSortResults;
use App\Controller\Api\Traits\HasMediaSearch;
use App\Controller\SingleActionInterface;
use App\Entity\Api\FileList;
use App\Entity\Api\FileListMedia;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\RouterInterface;
use App\Http\ServerRequest;
use App\Media\MimeType;
use App\Paginator;
use App\Utilities;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use League\Flysystem\StorageAttributes;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final class ListAction implements SingleActionInterface
{
    use CanSortResults;
    use EntityManagerAwareTrait;
    use HasMediaSearch;

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly StationFilesystems $stationFilesystems
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $router = $request->getRouter();

        $station = $request->getStation();
        $storageLocation = $station->getMediaStorageLocation();

        $fs = $this->stationFilesystems->getMediaFilesystem($station);

        $currentDir = $request->getParam('currentDirectory', '');

        $searchPhraseFull = trim($request->getParam('searchPhrase', ''));
        $isSearch = !empty($searchPhraseFull);

        [$searchPhrase, $playlist, $special] = $this->parseSearchQuery(
            $station,
            $searchPhraseFull
        );

        $cacheKeyParts = [
            'files_list',
            $storageLocation->getIdRequired(),
            (!empty($currentDir)) ? 'dir_' . rawurlencode($currentDir) : 'root',
        ];

        if ($isSearch) {
            $cacheKeyParts[] = 'search_' . rawurlencode($searchPhraseFull);
        }

        $cacheKey = implode('.', $cacheKeyParts);

        $flushCache = (bool)$request->getParam('flushCache', false);

        if (!$flushCache && $this->cache->has($cacheKey)) {
            /** @var array<int, FileList> $result */
            $result = $this->cache->get($cacheKey);
        } else {
            $pathLike = (empty($currentDir))
                ? '%'
                : $currentDir . '/%';

            $mediaQueryBuilder = $this->em->createQueryBuilder()
                ->select('sm')
                ->from(StationMedia::class, 'sm')
                ->where('sm.storage_location = :storageLocation')
                ->andWhere('sm.path LIKE :path')
                ->setParameter('storageLocation', $station->getMediaStorageLocation())
                ->setParameter('path', $pathLike);

            $foldersInDirQuery = $this->em->createQuery(
                <<<'DQL'
                    SELECT spf, sp
                    FROM App\Entity\StationPlaylistFolder spf
                    JOIN spf.playlist sp
                    WHERE spf.station = :station
                    AND spf.path LIKE :path
                DQL
            )->setParameter('station', $station)
                ->setParameter('path', $pathLike);

            $unprocessableMediaQuery = $this->em->createQuery(
                <<<'DQL'
                    SELECT upm
                    FROM App\Entity\UnprocessableMedia upm
                    WHERE upm.storage_location = :storageLocation
                    AND upm.path LIKE :path
                DQL
            )->setParameter('storageLocation', $storageLocation)
                ->setParameter('path', $pathLike);

            // Apply searching
            if ($isSearch) {
                if ('unprocessable' === $special) {
                    $mediaQueryBuilder = null;

                    $unprocessableMediaRaw = $unprocessableMediaQuery->toIterable(
                        [],
                        $unprocessableMediaQuery::HYDRATE_ARRAY
                    );
                } else {
                    if ('duplicates' === $special) {
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
                    } elseif ('unassigned' === $special) {
                        $mediaQueryBuilder->andWhere(
                            'sm.id NOT IN (SELECT spm2.media_id FROM App\Entity\StationPlaylistMedia spm2)'
                        );
                    } elseif (null !== $playlist) {
                        $mediaQueryBuilder->andWhere(
                            'sm.id IN (SELECT spm2.media_id FROM App\Entity\StationPlaylistMedia spm2 '
                            . 'WHERE spm2.playlist = :playlist)'
                        )->setParameter('playlist', $playlist);
                    }

                    if (!empty($searchPhrase)) {
                        $mediaQueryBuilder->andWhere('(sm.title LIKE :query OR sm.artist LIKE :query)')
                            ->setParameter('query', '%' . $searchPhrase . '%');
                    }

                    $unprocessableMediaRaw = [];
                }

                $foldersInDirRaw = [];
            } else {
                // Avoid loading subfolder media.
                $mediaQueryBuilder->andWhere('sm.path NOT LIKE :pathWithSubfolders')
                    ->setParameter('pathWithSubfolders', $pathLike . '/%');

                $foldersInDirRaw = $foldersInDirQuery->getArrayResult();

                $unprocessableMediaRaw = $unprocessableMediaQuery->toIterable(
                    [],
                    $unprocessableMediaQuery::HYDRATE_ARRAY
                );
            }

            // Process all database results.
            $mediaInDir = $this->processMediaInDir($station, $mediaQueryBuilder);

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
                    'short_name' => StationPlaylist::generateShortName(
                        $folderRow['playlist']['name']
                    ),
                ];
            }

            $unprocessableMedia = [];
            foreach ($unprocessableMediaRaw as $unprocessableRow) {
                $unprocessableMedia[$unprocessableRow['path']] = $unprocessableRow['error'];
            }

            if ($isSearch) {
                if ('unprocessable' === $special) {
                    /** @var string[] $files */
                    $files = array_keys($unprocessableMedia);
                } else {
                    /** @var string[] $files */
                    $files = array_keys($mediaInDir);
                }
            } else {
                $files = $fs->listContents($currentDir, false)->filter(
                    fn(StorageAttributes $attributes) => !StationFilesystems::isDotFile($attributes->path())
                );
            }

            $result = [];
            foreach ($files as $file) {
                $row = new FileList();

                if ($file instanceof StorageAttributes) {
                    $row->path = $file->path();
                    $row->timestamp = $file->lastModified() ?? 0;
                    $row->is_dir = $file->isDir();
                } else {
                    $row->path = $file;
                    $row->timestamp = $fs->lastModified($file);
                    $row->is_dir = false;
                }

                $row->size = ($row->is_dir) ? 0 : $fs->fileSize($row->path);

                $shortname = ($isSearch)
                    ? $row->path
                    : basename($row->path);

                $maxLength = 60;
                if (mb_strlen($shortname) > $maxLength) {
                    $shortname = mb_substr($shortname, 0, $maxLength - 15) . '...' . mb_substr($shortname, -12);
                }
                $row->path_short = $shortname;

                $row->media = new FileListMedia();

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
                    $row->text = sprintf(
                        __('File Not Processed: %s'),
                        Utilities\Strings::truncateText($unprocessableMedia[$row->path])
                    );
                } elseif (MimeType::isPathImage($row->path)) {
                    $row->is_cover_art = true;
                    $row->text = __('Cover Art');
                } else {
                    $row->text = __('File Processing');
                }

                $result[] = $row;
            }

            $this->cache->set($cacheKey, $result, 300);
        }

        // Apply sorting
        [$sort, $sortOrder] = $this->getSortFromRequest($request);

        $propertyAccessor = self::getPropertyAccessor();

        usort(
            $result,
            static fn(FileList $a, FileList $b) => self::sortRows(
                $a,
                $b,
                $propertyAccessor,
                $searchPhrase,
                $sort,
                $sortOrder
            )
        );

        $paginator = Paginator::fromArray($result, $request);

        // Add processor-intensive data for just this page.
        $stationId = $station->getIdRequired();

        $paginator->setPostprocessor(
            static fn(FileList $row) => self::postProcessRow($row, $router, $stationId)
        );

        return $paginator->write($response);
    }

    private function processMediaInDir(
        Station $station,
        ?QueryBuilder $qb = null
    ): array {
        if (null === $qb) {
            return [];
        }

        $qb->select(
            'sm.id',
            'sm.unique_id',
            'sm.song_id',
            'sm.path',
            'sm.artist',
            'sm.title',
            'sm.album',
            'sm.genre',
            'sm.isrc',
            'sm.length',
            'sm.length_text',
            'sm.art_updated_at'
        );

        $mediaInDirRaw = $qb->getQuery()->getScalarResult();

        $mediaIds = array_column($mediaInDirRaw, 'id');

        // Fetch custom fields for all shown media.
        $customFieldsRaw = $this->em->createQuery(
            <<<'DQL'
            SELECT smcf.media_id, smcf.field_id, smcf.value
            FROM App\Entity\StationMediaCustomField smcf
            WHERE smcf.media_id IN (:ids)
            DQL
        )->setParameter('ids', $mediaIds)
            ->getScalarResult();

        $customFields = [];
        foreach ($customFieldsRaw as $row) {
            $customFields[$row['media_id']] ??= [];
            $customFields[$row['media_id']][$row['field_id']] = $row['value'];
        }

        // Fetch playlists for all shown media.
        $allPlaylistsRaw = $this->em->createQuery(
            <<<'DQL'
            SELECT spm.media_id, spm.playlist_id, sp.name
            FROM App\Entity\StationPlaylistMedia spm
            JOIN spm.playlist sp
            WHERE sp.station = :station AND spm.media_id IN (:ids) 
            DQL
        )->setParameter('station', $station)
            ->setParameter('ids', $mediaIds)
            ->getScalarResult();

        $allPlaylists = [];
        foreach ($allPlaylistsRaw as $row) {
            $allPlaylists[$row['media_id']] ??= [];
            $allPlaylists[$row['media_id']][$row['playlist_id']] = $row['name'];
        }

        $mediaInDir = [];
        foreach ($mediaInDirRaw as $row) {
            $id = $row['id'];
            $media = new FileListMedia();

            $media->id = (string)$row['song_id'];
            $media->title = (string)$row['title'];
            $media->artist = (string)$row['artist'];
            $media->text = $row['artist'] . ' - ' . $row['title'];
            $media->album = (string)$row['album'];
            $media->genre = (string)$row['genre'];
            $media->isrc = (string)$row['isrc'];

            $media->is_playable = ($row['length'] !== 0);
            $media->length = (int)$row['length'];
            $media->length_text = $row['length_text'];

            $media->media_id = $id;
            $media->unique_id = $row['unique_id'];
            $media->art_updated_at = $row['art_updated_at'];

            $media->custom_fields = $customFields[$id] ?? [];

            $playlists = [];
            foreach ($allPlaylists[$id] ?? [] as $playlistId => $playlistName) {
                if (isset($playlists[$playlistId])) {
                    $playlists[$playlistId]['count']++;
                } else {
                    $playlists[$playlistId] = [
                        'id' => $playlistId,
                        'name' => $playlistName,
                        'short_name' => StationPlaylist::generateShortName($playlistName),
                        'count' => 1,
                    ];
                }
            }

            $mediaInDir[$row['path']] = [
                'media' => $media,
                'playlists' => array_values($playlists),
            ];
        }

        return $mediaInDir;
    }

    private static function sortRows(
        FileList $a,
        FileList $b,
        PropertyAccessorInterface $propertyAccessor,
        ?string $searchPhrase = null,
        ?string $sort = null,
        string $sortOrder = Criteria::ASC
    ): int {
        if ('special:duplicates' === $searchPhrase) {
            return $a->media->id <=> $b->media->id;
        }

        $isDirComp = $b->is_dir <=> $a->is_dir;
        if (0 !== $isDirComp) {
            return $isDirComp;
        }

        if (!$sort) {
            $aVal = $a->path;
            $bVal = $b->path;
            return (Criteria::ASC === $sortOrder) ? $aVal <=> $bVal : $bVal <=> $aVal;
        }

        return self::sortByDotNotation($a, $b, $propertyAccessor, $sort, $sortOrder);
    }

    private static function postProcessRow(
        FileList $row,
        RouterInterface $router,
        int $stationId
    ): FileList {
        if (null !== $row->media->media_id) {
            $artMediaId = $row->media->unique_id;
            if (0 !== $row->media->art_updated_at) {
                $artMediaId .= '-' . $row->media->art_updated_at;
            }

            $row->media->art = $router->named(
                'api:stations:media:art',
                [
                    'station_id' => $stationId,
                    'media_id' => $artMediaId,
                ]
            );

            $row->media->links = [
                'play' => $router->named(
                    'api:stations:files:play',
                    ['station_id' => $stationId, 'id' => $row->media->media_id],
                    [],
                    true
                ),
                'edit' => $router->named(
                    'api:stations:file',
                    ['station_id' => $stationId, 'id' => $row->media->media_id],
                ),
                'art' => $router->named(
                    'api:stations:media:art-internal',
                    ['station_id' => $stationId, 'media_id' => $row->media->media_id]
                ),
                'waveform' => $router->named(
                    'api:stations:media:waveform',
                    [
                        'station_id' => $stationId,
                        'media_id' => $row->media->unique_id . '-' . $row->media->art_updated_at,
                    ]
                ),
            ];
        }

        $row->links = [
            'download' => $router->named(
                'api:stations:files:download',
                ['station_id' => $stationId],
                ['file' => $row->path]
            ),
            'rename' => $router->named(
                'api:stations:files:rename',
                ['station_id' => $stationId],
                ['file' => $row->path]
            ),
        ];

        return $row;
    }
}
