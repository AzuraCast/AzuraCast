<?php

declare(strict_types=1);

namespace App\Entity\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_StationPlaylistParentGroup',
    required: ['*'],
    type: 'object'
)]
final class StationPlaylistParentGroup
{
    #[OA\Property(
        description: 'The unique identifier of the parent group.',
        example: 1
    )]
    public int $id;

    #[OA\Property(
        description: 'The name of the parent group.',
        example: 'My Group'
    )]
    public string $name;
}
