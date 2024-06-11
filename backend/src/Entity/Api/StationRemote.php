<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Api\Traits\HasLinks;
use App\Traits\LoadFromParentObject;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_StationRemote',
    type: 'object'
)]
final class StationRemote
{
    use LoadFromParentObject;
    use HasLinks;

    #[OA\Property]
    public ?int $id = null;

    #[OA\Property(example: '128kbps MP3')]
    public ?string $display_name = null;

    #[OA\Property(example: true)]
    public bool $is_visible_on_public_pages = true;

    #[OA\Property(example: 'icecast')]
    public string $type;

    #[OA\Property(example: 'true')]
    public bool $is_editable = true;

    #[OA\Property(example: false)]
    public bool $enable_autodj = false;

    #[OA\Property(example: 'mp3')]
    public ?string $autodj_format = null;

    #[OA\Property(example: 128)]
    public ?int $autodj_bitrate = null;

    #[OA\Property(example: 'https://custom-listen-url.example.com/stream.mp3')]
    public ?string $custom_listen_url = null;

    #[OA\Property(example: 'https://custom-url.example.com')]
    public string $url = '';

    #[OA\Property(example: '/stream.mp3')]
    public ?string $mount = null;

    #[OA\Property(example: 'password')]
    public ?string $admin_password = null;

    #[OA\Property(example: 8000)]
    public ?int $source_port = null;

    #[OA\Property(example: '/')]
    public ?string $source_mount = null;

    #[OA\Property(example: 'source')]
    public ?string $source_username = null;

    #[OA\Property(example: 'password')]
    public ?string $source_password = null;

    #[OA\Property(example: false)]
    public bool $is_public = false;

    #[OA\Property(
        description: 'The most recent number of unique listeners.',
        example: 10
    )]
    public int $listeners_unique = 0;

    #[OA\Property(
        description: 'The most recent number of total (non-unique) listeners.',
        example: 12
    )]
    public int $listeners_total = 0;
}
