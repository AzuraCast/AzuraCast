<?php

namespace App\Entity\Api\Admin;

use App\Entity;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_Admin_StorageLocation")
 */
class StorageLocation extends Entity\StorageLocation
{
    use Entity\Api\Traits\HasLinks;

    /**
     * The URI associated with the storage location.
     *
     * @OA\Property(example="/var/azuracast/www")
     * @var string
     */
    public string $uri;

    /**
     * @OA\Property(
     *     @OA\Items(
     *         type="string",
     *         example="AzuraTest Radio"
     *     )
     * )
     * @var array|null The stations using this storage location, if any.
     */
    public ?array $stations = [];
}
