<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Files;

use App\Cache\MediaListCache;
use App\Container\EntityManagerAwareTrait;
use App\Controller\Api\Traits\CanSortResults;
use App\Controller\Api\Traits\HasMediaSearch;
use App\Controller\SingleActionInterface;
use App\Entity\Api\FileList;
use App\Entity\Api\FileListDir;
use App\Entity\Api\StationMedia as ApiStationMedia;
use App\Entity\Api\StationMediaPlaylist;
use App\Entity\Enums\FileTypes;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\RouterInterface;
use App\Http\ServerRequest;
use App\Media\MimeType;
use App\OpenApi;
use App\Paginator;
use App\Utilities\Strings;
use App\Utilities\Types;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\QueryBuilder;
use League\Flysystem\StorageAttributes;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

#[
    OA\Get(
        path: '/station/{station_id}/files/list',
        operationId: 'getStationFileList',
        summary: 'List files in the media directory by path.',
        tags: [OpenApi::TAG_STATIONS_MEDIA],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'currentDirectory',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'searchPhrase',
                in: 'query',
                schema: new OA\Schema(type: 'string', nullable: true)
            ),
            new OA\Parameter(
                name: 'flushCache',
                in: 'query',
                schema: new OA\Schema(type: 'boolean', default: false, nullable: true),
            ),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        ref: FileList::class
                    )
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final class ListAction implements SingleActionInterface
{
    use CanSortResults;
    use EntityManagerAwareTrait;
    use HasMediaSearch;

    public function __construct(
        private readonly MediaListCache $mediaListCache,
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
        $storageLocation = $station->media_storage_location;

        $currentDir = Types::string($request->getParam('currentDirectory'));

        $searchPhraseFull = Types::stringOrNull($request->getParam('searchPhrase'), true);
        $isSearch = null !== $searchPhraseFull;

        [$searchPhrase, $playlist, $special] = $this->parseSearchQuery(
            $station,
            $searchPhraseFull ?? ''
        );

        $cache = $this->mediaListCache->getCacheForTag($storageLocation);

        $cacheKeyParts = [
            (!empty($currentDir)) ? 'dir_' . rawurlencode($currentDir) : 'root',
        ];

        if ($isSearch) {
            $cacheKeyParts[] = 'search_' . rawurlencode($searchPhraseFull);
        }
        $cacheKey = implode('.', $cacheKeyParts);

        $flushCache = Types::bool($request->getParam('flushCache'), false, true);
        if ($flushCache) {
            $cache->clear();
        }

        $cacheItem = $cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            /** @var array<int, FileList> $result */
            $result = $cacheItem->get();
        } else {
            $pathLike = (empty($currentDir))
                ? '%'
                : $currentDir . '/%';

            $fs = $this->stationFilesystems->getMediaFilesystem($station);

            $mediaQueryBuilder = $this->em->createQueryBuilder()
                ->select('sm')
                ->from(StationMedia::class, 'sm')
                ->where('sm.storage_location = :storageLocation')
                ->andWhere('sm.path LIKE :path')
                ->setParameter('storageLocation', $station->media_storage_location)
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

                $foldersInDir = [];
                $foldersAboveDir = [];
            } else {
                // Avoid loading subfolder media.
                $mediaQueryBuilder->andWhere('sm.path NOT LIKE :pathWithSubfolders')
                    ->setParameter('pathWithSubfolders', $pathLike . '/%');

                $foldersInDir = $this->getFoldersInDir($station, $currentDir);
                $foldersAboveDir = $this->getFoldersAboveDir($station, $currentDir);

                $unprocessableMediaRaw = $unprocessableMediaQuery->toIterable(
                    [],
                    $unprocessableMediaQuery::HYDRATE_ARRAY
                );
            }

            // Process all database results.
            $mediaInDir = $this->processMediaInDir($station, $mediaQueryBuilder);

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
                    $row->dir = new FileListDir();
                    $row->dir->playlists = StationMediaPlaylist::aggregate(
                        [
                            ...$foldersInDir[$row->path] ?? [],
                            ...$foldersAboveDir,
                        ]
                    );
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

            $cacheItem->set($result);
            $cacheItem->expiresAfter(60 * 5);
            $cache->save($cacheItem);
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
        $stationId = $station->id;

        $paginator->setPostprocessor(
            static fn(FileList $row) => self::postProcessRow($row, $router, $stationId)
        );

        return $paginator->write($response);
    }

    /**
     * @param Station $station
     * @param string $path
     * @return array<string, StationMediaPlaylist[]>
     */
    private function getFoldersInDir(
        Station $station,
        string $path
    ): array {
        $pathLike = (empty($path))
            ? '%'
            : $path . '/%';

        $foldersInDirQuery = $this->em->createQuery(
            <<<'DQL'
                SELECT spf, sp
                FROM App\Entity\StationPlaylistFolder spf
                JOIN spf.playlist sp
                WHERE spf.station = :station
                AND spf.path LIKE :path
                AND spf.path NOT LIKE :pathWithSubfolders
            DQL
        )->setParameter('station', $station)
            ->setParameter('path', $pathLike)
            ->setParameter('pathWithSubfolders', $pathLike . '/%');

        $return = [];
        foreach ($foldersInDirQuery->getArrayResult() as $row) {
            $return[$row['path']] ??= [];
            $return[$row['path']][] = new StationMediaPlaylist(
                id: $row['playlist']['id'],
                name: $row['playlist']['name'],
                short_name: StationPlaylist::generateShortName($row['playlist']['name'])
            );
        }

        return $return;
    }

    /**
     * @param Station $station
     * @param string $path
     * @return StationMediaPlaylist[]
     */
    private function getFoldersAboveDir(
        Station $station,
        string $path
    ): array {
        if (empty($path)) {
            return [];
        }

        $validPaths = [];
        $pathsSoFar = [];
        foreach (explode('/', $path) as $part) {
            $pathsSoFar[] = $part;
            $validPaths[] = implode('/', $pathsSoFar);
        }

        $foldersAboveDirQuery = $this->em->createQuery(
            <<<'DQL'
                SELECT spf, sp
                FROM App\Entity\StationPlaylistFolder spf
                JOIN spf.playlist sp
                WHERE spf.station = :station
                AND spf.path IN (:paths)
            DQL
        )->setParameter('station', $station)
            ->setParameter('paths', $validPaths);

        return array_map(
            fn(array $row) => new StationMediaPlaylist(
                id: $row['playlist']['id'],
                name: $row['playlist']['name'],
                short_name: StationPlaylist::generateShortName($row['playlist']['name']),
                folder: $row['path']
            ),
            $foldersAboveDirQuery->getArrayResult()
        );
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
            SELECT IDENTITY(smcf.media) AS media_id, cf.short_name, smcf.value
            FROM App\Entity\StationMediaCustomField smcf JOIN smcf.field cf
            WHERE IDENTITY(smcf.media) IN (:ids)
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
            SELECT spm, sp, spf
            FROM App\Entity\StationPlaylistMedia spm
            JOIN spm.playlist sp
            LEFT JOIN spm.folder spf
            WHERE sp.station = :station AND IDENTITY(spm.media) IN (:ids) 
            DQL
        )->setParameter('station', $station)
            ->setParameter('ids', $mediaIds)
            ->getArrayResult();

        $allPlaylists = [];
        foreach ($allPlaylistsRaw as $row) {
            $allPlaylists[$row['media_id']] ??= [];
            $allPlaylists[$row['media_id']][] = new StationMediaPlaylist(
                id: $row['playlist']['id'],
                name: $row['playlist']['name'],
                short_name: StationPlaylist::generateShortName($row['playlist']['name']),
                folder: $row['folder']['path'] ?? null
            );
        }

        $mediaInDir = [];
        foreach ($mediaInDirRaw as $row) {
            $id = $row['id'];

            $mediaInDir[$row['path']] = ApiStationMedia::fromArray(
                $row,
                [],
                $customFields[$id] ?? [],
                StationMediaPlaylist::aggregate($allPlaylists[$id] ?? [])
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
