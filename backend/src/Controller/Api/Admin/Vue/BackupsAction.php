<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Vue;

use App\Container\EnvironmentAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Enums\StorageLocationTypes;
use App\Entity\Repository\StorageLocationRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class BackupsAction implements SingleActionInterface
{
    use EnvironmentAwareTrait;

    public function __construct(
        private readonly StorageLocationRepository $storageLocationRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        return $response->withJson([
            'isDocker' => $this->environment->isDocker(),
            'storageLocations' => $this->storageLocationRepo->fetchSelectByType(
                StorageLocationTypes::Backup
            ),
        ]);
    }
}
