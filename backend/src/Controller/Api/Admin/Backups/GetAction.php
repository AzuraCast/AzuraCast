<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Backups;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Admin\Backup;
use App\Entity\Enums\StorageLocationTypes;
use App\Entity\Repository\StorageLocationRepository;
use App\Flysystem\Attributes\FileAttributes;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Paginator;
use League\Flysystem\StorageAttributes;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/admin/backups',
        operationId: 'getBackups',
        summary: 'Return a list of all current backups.',
        tags: [OpenApi::TAG_ADMIN_BACKUPS],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        ref: Backup::class
                    )
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final readonly class GetAction implements SingleActionInterface
{
    public function __construct(
        private StorageLocationRepository $storageLocationRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $router = $request->getRouter();

        $backups = [];
        $storageLocations = $this->storageLocationRepo->findAllByType(StorageLocationTypes::Backup);

        foreach ($storageLocations as $storageLocation) {
            $fs = $this->storageLocationRepo->getAdapter($storageLocation)
                ->getFilesystem();

            /** @var StorageAttributes $file */
            foreach ($fs->listContents('', true) as $file) {
                if ($file->isDir()) {
                    continue;
                }

                /** @var FileAttributes $file */
                $filename = $file->path();

                $pathEncoded = base64_encode($storageLocation->id . '|' . $filename);

                $backups[] = new Backup(
                    $filename,
                    basename($filename),
                    $pathEncoded,
                    $file->lastModified() ?? 0,
                    $file->fileSize(),
                    $storageLocation->id
                );
            }
        }

        uasort(
            $backups,
            static function (Backup $a, Backup $b) {
                return $b->timestamp <=> $a->timestamp;
            }
        );

        $paginator = Paginator::fromArray($backups, $request);
        $paginator->setPostprocessor(function (Backup $row) use ($router) {
            $row->links = [
                'download' => $router->fromHere(
                    'api:admin:backups:download',
                    ['path' => $row->pathEncoded]
                ),
                'delete' => $router->fromHere(
                    'api:admin:backups:delete',
                    ['path' => $row->pathEncoded]
                ),
            ];
            return $row;
        });

        return $paginator->write($response);
    }
}
