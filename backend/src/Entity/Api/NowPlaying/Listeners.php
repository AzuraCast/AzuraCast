<?php

declare(strict_types=1);

namespace App\Entity\Api\NowPlaying;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_NowPlaying_Listeners',
    type: 'object'
)]
final class Listeners
{
    #[OA\Property(
        description: 'Total non-unique current listeners',
        example: 20
    )]
    public int $total = 0;

    #[OA\Property(
        description: 'Total unique current listeners',
        example: 15
    )]
    public int $unique = 0;

    #[OA\Property(
        description: 'Total non-unique current listeners (Legacy field, may be retired in the future.)',
        example: 20
    )]
    public int $current = 0;

    public function __construct(
        int $total = 0,
        ?int $unique = null
    ) {
        $this->total = $total;
        $this->current = $total;

        $this->unique = $unique ?? 0;
    }
}
