<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(type="object", schema="Api_NewRecord")
 */
class NewRecord extends Status
{
    /**
     * @OA\Property(@OA\Items(
     *      type="string",
     *      example="http://localhost/api/record/1"
     * ))
     * @var array
     */
    public array $links = [];
}
