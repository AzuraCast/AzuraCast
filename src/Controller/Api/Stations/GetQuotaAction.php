<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Entity\StorageLocation;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class GetQuotaAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $type = StorageLocation::TYPE_STATION_MEDIA
    ): ResponseInterface {
        $storageLocation = $request->getStation()->getStorageLocation($type);

        return $response->withJson([
            'used'            => $storageLocation->getStorageUsed(),
            'used_bytes'      => (string)$storageLocation->getStorageUsedBytes(),
            'used_percent'    => $storageLocation->getStorageUsePercentage(),
            'available'       => $storageLocation->getStorageAvailable(),
            'available_bytes' => (string)$storageLocation->getStorageAvailableBytes(),
            'quota'           => $storageLocation->getStorageQuota(),
            'quota_bytes'     => (string)$storageLocation->getStorageQuotaBytes(),
        ]);
    }
}
