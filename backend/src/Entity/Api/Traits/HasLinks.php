<?php

declare(strict_types=1);

namespace App\Entity\Api\Traits;

use OpenApi\Attributes as OA;

#[OA\Schema(type: 'object')]
trait HasLinks
{
    #[OA\Property(
        type: 'object',
        readOnly: true,
        additionalProperties: new OA\AdditionalProperties(type: 'string')
    )]
    public array $links = [];
}
