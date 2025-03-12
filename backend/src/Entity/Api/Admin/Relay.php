<?php

declare(strict_types=1);

namespace App\Entity\Api\Admin;

use App\Entity\Api\NowPlaying\StationMount;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_Admin_Relay',
    type: 'object'
)]
final class Relay
{
    #[OA\Property(
        description: 'Station ID',
        example: 1
    )]
    public int $id;

    #[OA\Property(
        description: 'Station name',
        example: 'AzuraTest Radio'
    )]
    public ?string $name = null;

    #[OA\Property(
        description: 'Station "short code", used for URL and folder paths',
        example: 'azuratest_radio'
    )]
    public ?string $shortcode = null;

    #[OA\Property(
        description: 'Station description',
        example: 'An AzuraCast station!'
    )]
    public ?string $description;

    #[OA\Property(
        description: 'Station homepage URL',
        example: 'https://www.azuracast.com/'
    )]
    public ?string $url;

    #[OA\Property(
        description: 'The genre of the station',
        example: 'Variety'
    )]
    public ?string $genre;

    #[OA\Property(
        description: 'Which broadcasting software (frontend) the station uses',
        example: 'shoutcast2'
    )]
    public ?string $type = null;

    #[OA\Property(
        description: 'The port used by this station to serve its broadcasts.',
        example: 8000
    )]
    public ?int $port = null;

    #[OA\Property(
        description: 'The relay password for the frontend (if applicable).',
        example: 'p4ssw0rd'
    )]
    public string $relay_pw;

    #[OA\Property(
        description: 'The administrator password for the frontend (if applicable).',
        example: 'p4ssw0rd'
    )]
    public string $admin_pw;

    /** @var StationMount[] */
    #[OA\Property]
    public array $mounts = [];
}
