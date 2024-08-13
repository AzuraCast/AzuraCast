<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Container\EntityManagerAwareTrait;
use App\Controller\Api\Traits\CanSortResults;
use App\Controller\Api\Traits\HasMediaSearch;
use App\Controller\SingleActionInterface;
use App\Entity\Api\FileList;
use App\Entity\Api\FileListDir;
use App\Entity\Api\StationMedia as ApiStationMedia;
use App\Entity\Enums\FileTypes;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\RouterInterface;
use App\Http\ServerRequest;
use App\Media\MimeType;
use App\Paginator;
use App\Utilities\Strings;
use App\Utilities\Types;
use Doctrine\Common\Collections\Order;
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

        $currentDir = Types::string($request->getParam('currentDirectory'));

        $searchPhraseFull = Types::stringOrNull($request->getParam('searchPhrase'), true);
        $isSearch = null !== $searchPhraseFull;

        [$searchPhrase, $playlist, $special] = $this->parseSearchQuery(
            $station,
            $searchPhraseFull ?? ''
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

        $flushCache = Types::bool($request->getParam('flushCache'), false, true);

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
                        $mediaQueryBuilder->andWhere(
                            '(sm.title LIKE :query OR sm.artist LIKE :query OR sm.path LIKE :query)'
                        )->setParameter('query', '%' . $searchPhrase . '%');
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

            $folderPlaylists = [];
            foreach ($foldersInDirRaw as $folderRow) {
                if (!isset($folderPlaylists[$folderRow['path']])) {
                    $folderPlaylists[$folderRow['path']] = [];
                }

                $folderPlaylists[$folderRow['path']][] = $folderRow['playlist'];
            }

            /** @var array<string, FileListDir> $foldersInDir */
            $foldersInDir = array_map(
                function ($playlists) {
                    $row = new FileListDir();
                    $row->playlists = ApiStationMedia::aggregatePlaylists($playlists);
                    return $row;
                },
                $folderPlaylists
            );

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
                    $isDir = $file->isDir();

                    $row->path = $file->path();
                    $row->timestamp = $file->lastModified() ?? 0;
                    $row->size = (!$isDir && method_exists($file, 'fileSize')) ? $file->fileSize() : 0;
                } else {
                    $isDir = false;

                    $row->path = $file;
                    $row->timestamp = $fs->lastModified($file);
                    $row->size = $fs->fileSize($row->path);
                }

                $shortname = ($isSearch)
                    ? $row->path
                    : basename($row->path);

                $maxLength = 60;
                if (mb_strlen($shortname) > $maxLength) {
                    $shortname = mb_substr($shortname, 0, $maxLength - 15) . '...' . mb_substr($shortname, -12);
                }
                $row->path_short = $shortname;

                if (isset($mediaInDir[$row->path])) {
                    $row->type = FileTypes::Media;
                    $row->media = $mediaInDir[$row->path];
                    $row->text = $row->media->text;
                } elseif ($isDir) {
                    $row->type = FileTypes::Directory;
                    $row->text = __('Directory');
                    $row->dir = $foldersInDir[$row->path] ?? new FileListDir();
                } elseif (isset($unprocessableMedia[$row->path])) {
                    $row->type = FileTypes::UnprocessableFile;
                    $row->text = sprintf(
                        __('File Not Processed: %s'),
                        Strings::truncateText($unprocessableMedia[$row->path])
                    );
                } elseif (MimeType::isPathImage($row->path)) {
                    $row->type = FileTypes::CoverArt;
                    $row->text = __('Cover Art');
                } else {
                    $row->type = FileTypes::Other;
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
                $special,
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

    /**
     * @return array<string, ApiStationMedia>
     */
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
            'sm.mtime',
            'sm.uploaded_at',
            'sm.art_updated_at'
        );

        /** @var array<array{
         *     id: int,
         *     unique_id: string,
         *     song_id: string,
         *     path: string,
         *     artist: string | null,
         *     title: string | null,
         *     album: string | null,
         *     genre: string | null,
         *     isrc: string | null,
         *     length: string,
         *     mtime: int,
         *     uploaded_at: int,
         *     art_updated_at: int
         * }> $mediaInDirRaw
         */
        $mediaInDirRaw = $qb->getQuery()->getScalarResult();

        $mediaIds = array_column($mediaInDirRaw, 'id');

        // Fetch custom fields for all shown media.
        $customFieldsRaw = $this->em->createQuery(
            <<<'DQL'
            SELECT smcf.media_id, cf.short_name, smcf.value
            FROM App\Entity\StationMediaCustomField smcf JOIN smcf.field cf
            WHERE smcf.media_id IN (:ids)
            DQL
        )->setParameter('ids', $mediaIds)
            ->getScalarResult();

        $customFields = [];
        foreach ($customFieldsRaw as $row) {
            $customFields[$row['media_id']] ??= [];
            $customFields[$row['media_id']][$row['short_name']] = $row['value'];
        }

        // Fetch playlists for all shown media.
        $allPlaylistsRaw = $this->em->createQuery(
            <<<'DQL'
            SELECT spm, sp
            FROM App\Entity\StationPlaylistMedia spm
            JOIN spm.playlist sp
            WHERE sp.station = :station AND spm.media_id IN (:ids) 
            DQL
        )->setParameter('station', $station)
            ->setParameter('ids', $mediaIds)
            ->getArrayResult();

        $allPlaylists = [];
        foreach ($allPlaylistsRaw as $row) {
            $allPlaylists[$row['media_id']] ??= [];
            $allPlaylists[$row['media_id']][] = $row['playlist'];
        }

        $mediaInDir = [];
        foreach ($mediaInDirRaw as $row) {
            $id = $row['id'];

            $mediaInDir[$row['path']] = ApiStationMedia::fromArray(
                $row,
                [],
                $customFields[$id] ?? [],
                ApiStationMedia::aggregatePlaylists($allPlaylists[$id] ?? [])
            );
        }

        return $mediaInDir;
    }

    private static function sortRows(
        FileList $a,
        FileList $b,
        PropertyAccessorInterface $propertyAccessor,
        ?string $specialSearchPhrase = null,
        ?string $sort = null,
        Order $sortOrder = Order::Ascending
    ): int {
        if ('duplicates' === $specialSearchPhrase) {
            return $a->media?->song_id <=> $b->media?->song_id;
        }

        $isDirComp = ($b->type === FileTypes::Directory) <=> ($a->type === FileTypes::Directory);
        if (0 !== $isDirComp) {
            return $isDirComp;
        }

        if (!$sort) {
            $aVal = $a->path;
            $bVal = $b->path;
            return (Order::Ascending === $sortOrder) ? $aVal <=> $bVal : $bVal <=> $aVal;
        }

        return self::sortByDotNotation($a, $b, $propertyAccessor, $sort, $sortOrder);
    }

    private static function postProcessRow(
        FileList $row,
        RouterInterface $router,
        int $stationId
    ): FileList {
        if (null !== $row->media) {
            $routeParams = [
                'media_id' => $row->media->unique_id,
            ];

            if (0 !== $row->media->art_updated_at) {
                $routeParams['timestamp'] = $row->media->art_updated_at;
            }

            $row->media->art = $router->fromHere(
                'api:stations:media:art',
                routeParams: $routeParams
            );

            $row->media->links = [
                'self' => $router->fromHere(
                    'api:stations:file',
                    ['id' => $row->media->id],
                ),
                'play' => $router->fromHere(
                    'api:stations:files:play',
                    ['id' => $row->media->id],
                    [],
                    true
                ),
                'art' => $router->fromHere(
                    'api:stations:media:art',
                    [
                        'media_id' => $row->media->id,
                    ]
                ),
                'waveform' => $router->fromHere(
                    'api:stations:media:waveform',
                    [
                        'media_id' => $row->media->unique_id,
                        'timestamp' => $row->media->art_updated_at,
                    ]
                ),
                'waveform_cache' => $router->fromHere(
                    'api:stations:media:waveform-cache',
                    [
                        'media_id' => $row->media->unique_id,
                    ]
                ),
            ];
        }

        $row->links = [
            'download' => $router->fromHere(
                'api:stations:files:download',
                queryParams: ['file' => $row->path]
            ),
            'rename' => $router->fromHere(
                'api:stations:files:rename',
                queryParams: ['file' => $row->path]
            ),
        ];

        return $row;
    }
}
