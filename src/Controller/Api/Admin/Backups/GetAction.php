<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Backups;

use App\Controller\SingleActionInterface;
use App\Entity\Enums\StorageLocationTypes;
use App\Entity\Repository\StorageLocationRepository;
use App\Flysystem\Attributes\FileAttributes;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator;
use League\Flysystem\StorageAttributes;
use Psr\Http\Message\ResponseInterface;

final class GetAction implements SingleActionInterface
{
    public function __construct(
        private readonly StorageLocationRepository $storageLocationRepo,
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
