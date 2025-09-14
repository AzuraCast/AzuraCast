<?php

declare(strict_types=1);

namespace App\Entity\Api\Stations\Vue;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Stations_Vue_SftpUsersProps',
    required: ['*'],
    type: 'object'
)]
final readonly class SftpUsersProps
{
    public function __construct(
        #[OA\Property]
        public string $connectionUrl,
        #[OA\Property]
        public ?string $connectionIp,
        #[OA\Property]
        public int $connectionPort
    ) {
    }
}
