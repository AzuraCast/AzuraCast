<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin\Debug;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_Debug_Station',
    required: ['*'],
    type: 'object'
)]
final readonly class DebugStation
{
    public function __construct(
        #[OA\Property]
        public int $id,
        #[OA\Property]
        public string $name,
        #[OA\Property]
        public string $clearQueueUrl,
        #[OA\Property]
        public string $getNextSongUrl,
        #[OA\Property]
        public string $getNowPlayingUrl
    ) {
    }
}
