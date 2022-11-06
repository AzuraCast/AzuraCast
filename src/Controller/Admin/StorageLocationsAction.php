<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class StorageLocationsAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_AdminStorageLocations',
            id: 'admin-storage-locations',
            title: __('Storage Locations'),
            props: [
                'listUrl' => $router->fromHere('api:admin:storage_locations'),
            ],
        );
    }
}
