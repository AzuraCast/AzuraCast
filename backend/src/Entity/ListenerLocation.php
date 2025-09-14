<?php

declare(strict_types=1);

namespace App\Entity;

use App\Utilities\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use OpenApi\Attributes as OA;

#[
    ORM\Embeddable,
    OA\Schema(
        schema: 'Api_ListenerLocation',
        type: 'object'
    )
]
final readonly class ListenerLocation implements JsonSerializable
{
    #[
        ORM\Column(length: 255, nullable: false),
        OA\Property(
            description: 'A description of the location.',
            example: 'Austin, Texas, US'
        )
    ]
    public string $description;

    #[
        ORM\Column(length: 150, nullable: true),
        OA\Property(
            description: 'The approximate region/state of the listener.',
            example: 'Texas'
        )
    ]
    public ?string $region;

    #[
        ORM\Column(length: 150, nullable: true),
        OA\Property(
            description: 'The approximate city of the listener.',
            example: 'Austin'
        )
    ]
    public ?string $city;

    #[
        ORM\Column(length: 2, nullable: true),
        OA\Property(
            description: 'The approximate country of the listener.',
            example: 'United States'
        )
    ]
    public ?string $country;

    #[
        ORM\Column(type: 'decimal', precision: 10, scale: 6, nullable: true),
        OA\Property(
            description: 'Latitude.',
            type: 'number',
            format: 'float',
            example: '30.000000',
            nullable: true
        )
    ]
    public ?string $lat;

    #[
        ORM\Column(type: 'decimal', precision: 10, scale: 6, nullable: true),
        OA\Property(
            description: 'Longitude.',
            type: 'number',
            format: 'float',
            example: '-97.000000',
            nullable: true
        )
    ]
    public ?string $lon;

    public function __construct(
        string $description,
        ?string $region,
        ?string $city,
        ?string $country,
        ?string $lat,
        ?string $lon
    ) {
        $this->description = $description;
        $this->region = $region;
        $this->city = $city;
        $this->country = $country;
        $this->lat = $lat;
        $this->lon = $lon;
    }

    public function jsonSerialize(): array
    {
        return [
            'description' => $this->description,
            'region' => $this->region,
            'city' => $this->city,
            'country' => $this->country,
            'lat' => Types::floatOrNull($this->lat),
            'lon' => Types::floatOrNull($this->lon),
        ];
    }

    public static function fromArray(array $row): self
    {
        return new self(
            description: Types::string($row['description'] ?? null),
            region: Types::stringOrNull($row['region'] ?? null),
            city: Types::stringOrNull($row['city'] ?? null),
            country: Types::stringOrNull($row['country'] ?? null),
            lat: Types::stringOrNull(Types::floatOrNull($row['lat'] ?? null)),
            lon: Types::stringOrNull(Types::floatOrNull($row['lon'] ?? null))
        );
    }
}
