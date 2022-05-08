<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

#[ORM\Embeddable]
class ListenerLocation implements JsonSerializable
{
    #[ORM\Column(length: 255, nullable: false)]
    protected string $description = 'Unknown';

    #[ORM\Column(length: 150, nullable: true)]
    protected ?string $region = null;

    #[ORM\Column(length: 150, nullable: true)]
    protected ?string $city = null;

    #[ORM\Column(length: 2, nullable: true)]
    protected ?string $country = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 6, nullable: true)]
    protected ?float $lat = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 6, nullable: true)]
    protected ?float $lon = null;

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getLat(): ?float
    {
        return $this->lat;
    }

    public function getLon(): ?float
    {
        return $this->lon;
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
