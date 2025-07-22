<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

#[ORM\Embeddable]
final readonly class ListenerLocation implements JsonSerializable
{
    #[ORM\Column(length: 255, nullable: false)]
    public string $description;

    #[ORM\Column(length: 150, nullable: true)]
    public ?string $region;

    #[ORM\Column(length: 150, nullable: true)]
    public ?string $city;

    #[ORM\Column(length: 2, nullable: true)]
    public ?string $country;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 6, nullable: true)]
    public ?string $lat;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 6, nullable: true)]
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
            'lat' => $this->lat,
            'lon' => $this->lon,
        ];
    }
}
