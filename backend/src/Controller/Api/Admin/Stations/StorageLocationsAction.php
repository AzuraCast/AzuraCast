<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Stations;

use App\Controller\Api\Admin\StationsController;
use App\Controller\SingleActionInterface;
use App\Entity\Station;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class StorageLocationsAction extends StationsController implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $newStorageLocationMessage = __('Create a new storage location based on the base directory.');

        $storageLocations = array_map(function ($locationType) use ($newStorageLocationMessage) {
            return $this->storageLocationRepo->fetchSelectByType(
                $locationType,
                true,
                $newStorageLocationMessage
            );
        }, Station::getStorageLocationTypes());

        return $response->withJson($storageLocations);
    }
}
