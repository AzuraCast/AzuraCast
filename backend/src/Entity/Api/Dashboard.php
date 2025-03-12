<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Api\Traits\HasLinks;
use App\Traits\LoadFromParentObject;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Dashboard',
    type: 'object'
)]
final class Dashboard extends NowPlaying
{
    use LoadFromParentObject;
    use HasLinks;
}
