<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Utilities\Types;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_ListenerLocation',
    type: 'object'
)]
final class ListenerLocation
{
    #[OA\Property(
        description: 'The approximate city of the listener.',
        example: 'Austin'
    )]
    public ?string $city = null;

    #[OA\Property(
        description: 'The approximate region/state of the listener.',
        example: 'Texas'
    )]
    public ?string $region = null;

    #[OA\Property(
        description: 'The approximate country of the listener.',
        example: 'United States'
    )]
    public ?string $country = null;

    #[OA\Property(
        description: 'A description of the location.',
        example: 'Austin, Texas, US'
    )]
    public string $description;

    #[OA\Property(
        description: 'Latitude.',
        example: '30.000000'
    )]
    public ?float $lat = null;

    #[OA\Property(
        description: 'Latitude.',
        example: '-97.000000'
    )]
    public ?float $lon = null;

    public static function fromArray(array $row): self
    {
        $api = new self();
        $api->city = Types::stringOrNull($row['city'] ?? null);
        $api->region = Types::stringOrNull($row['region'] ?? null);
        $api->country = Types::stringOrNull($row['country'] ?? null);
        $api->description = Types::string($row['description'] ?? null);
        $api->lat = Types::floatOrNull($row['lat'] ?? null);
        $api->lon = Types::floatOrNull($row['lon'] ?? null);
        return $api;
    }
}
