<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Backups;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator;
use App\Flysystem\Attributes\FileAttributes;
use League\Flysystem\StorageAttributes;
use Psr\Http\Message\ResponseInterface;

final class GetAction
{
    public function __construct(
        private readonly Entity\Repository\StorageLocationRepository $storageLocationRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $router = $request->getRouter();

        $backups = [];
        $storageLocations = $this->storageLocationRepo->findAllByType(Entity\Enums\StorageLocationTypes::Backup);

        foreach ($storageLocations as $storageLocation) {
            /** @var StorageAttributes $file */
            foreach ($storageLocation->getFilesystem()->listContents('', true) as $file) {
                if ($file->isDir()) {
                    continue;
                }

                /** @var FileAttributes $file */
                $filename = $file->path();

                $pathEncoded = base64_encode($storageLocation->getId() . '|' . $filename);

                $backups[] = [
                    'path' => $filename,
                    'basename' => basename($filename),
                    'pathEncoded' => $pathEncoded,
                    'timestamp' => $file->lastModified(),
                    'size' => $file->fileSize(),
                    'storageLocationId' => $storageLocation->getId(),
                ];
            }
        }

        uasort(
            $backups,
            static function ($a, $b) {
                return $b['timestamp'] <=> $a['timestamp'];
            }
        );

        $paginator = Paginator::fromArray($backups, $request);
        $paginator->setPostprocessor(function ($row) use ($router) {
            $row['links'] = [
                'download' => $router->fromHere(
                    'api:admin:backups:download',
                    ['path' => $row['pathEncoded']]
                ),
                'delete' => $router->fromHere(
                    'api:admin:backups:delete',
                    ['path' => $row['pathEncoded']]
                ),
            ];
            return $row;
        });

        return $paginator->write($response);
    }
}
