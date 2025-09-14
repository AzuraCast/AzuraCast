<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use App\Entity\Api\Traits\HasLinks;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_StorageLocation',
    type: 'object'
)]
final class StorageLocation
{
    use HasLinks;

    #[OA\Property(example: '75')]
    public ?int $storageUsedPercent = null;

    #[OA\Property(example: 'true')]
    public bool $isFull = true;

    #[OA\Property(
        description: 'The URI associated with the storage location.',
        example: '/var/azuracast/www'
    )]
    public string $uri;

    #[OA\Property(
        description: 'The stations using this storage location, if any.',
        items: new OA\Items(type: 'string', example: 'AzuraTest Radio')
    )]
    public ?array $stations = [];
}
