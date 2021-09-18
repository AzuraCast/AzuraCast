<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class StorageLocationsAction
{
    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $router = $request->getRouter();

        return $request->getView()->renderToResponse(
            $response,
            'system/vue',
            [
                'title' => __('Storage Locations'),
                'id' => 'admin-storage-locations',
                'component' => 'Vue_AdminStorageLocations',
                'props' => [
                    'listUrl' => (string)$router->fromHere('api:admin:storage_locations'),
                ],
            ]
        );
    }
}
