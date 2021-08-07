<?php

declare(strict_types=1);

namespace App\Entity\Api\Traits;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object")
 */
trait HasLinks
{
    /**
     * @OA\Property(
     *     @OA\Items(
     *         type="string",
     *         example="http://localhost/api/stations/1/queue/1"
     *     )
     * )
     * @var array
     */
    public array $links = [];
}
