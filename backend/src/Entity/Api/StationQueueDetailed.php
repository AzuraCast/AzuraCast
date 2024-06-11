<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Api\NowPlaying\StationQueue;
use App\Entity\Api\Traits\HasLinks;
use App\Traits\LoadFromParentObject;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_StationQueueDetailed',
    type: 'object'
)]
final class StationQueueDetailed extends StationQueue
{
    use LoadFromParentObject;
    use HasLinks;

    #[OA\Property(
        description: 'Indicates whether the song has been sent to the AutoDJ.',
    )]
    public bool $sent_to_autodj = false;

    #[OA\Property(
        description: 'Indicates whether the song has already been marked as played.',
    )]
    public bool $is_played = false;

    #[OA\Property(
        description: 'Custom AutoDJ playback URI, if it exists.',
        example: ''
    )]
    public ?string $autodj_custom_uri = null;

    #[OA\Property(
        description: 'Log entries on how the specific queue item was picked by the AutoDJ.',
        items: new OA\Items()
    )]
    public ?array $log = [];
}
