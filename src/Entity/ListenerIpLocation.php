<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[
    ORM\Entity,
    ORM\Table(name: 'listener_ip_locations'),
]
class ListenerIpLocation
{
    use Traits\TruncateStrings;

    #[
        ORM\Column(length: 45, nullable: false),
        ORM\Id
    ]
    protected string $ip;

    #[ORM\Column(length: 255)]
    protected string $location;

    #[ORM\Column(length: 150, nullable: true)]
    protected ?string $region = null;

    #[ORM\Column(length: 150, nullable: true)]
    protected ?string $city = null;

    #[ORM\Column(length: 2, nullable: true)]
    protected ?string $country = null;

    #[ORM\Column(type: 'decimal', precision: 20, scale: 16, nullable: true)]
    protected ?float $lat = null;

    #[ORM\Column(type: 'decimal', precision: 20, scale: 16, nullable: true)]
    protected ?float $lon = null;

    #[ORM\OneToMany(mappedBy: 'ipLocation', targetEntity: Listener::class)]
    protected Collection $listeners;

    public function __construct(string $ip)
    {
        $this->ip = $ip;
        $this->listeners = new ArrayCollection();
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): void
    {
        $this->location = $this->truncateString($location, 255);
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): void
    {
        $this->region = $this->truncateNullableString($region, 150);
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): void
    {
        $this->city = $this->truncateNullableString($city, 150);
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): void
    {
        $this->country = $this->truncateNullableString($country, 2);
    }

    public function getLat(): ?float
    {
        return $this->lat;
    }

    public function setLat(?float $lat): void
    {
        $this->lat = $lat;
    }

    public function getLon(): ?float
    {
        return $this->lon;
    }

    public function setLon(?float $lon): void
    {
        $this->lon = $lon;
    }

    /**
     * @return Collection<Listener>
     */
    public function getListeners(): Collection
    {
        return $this->listeners;
    }

    public static function fromIpGeolocation(
        string $ip,
        array $ipInfo = []
    ): self {
        $record = new self($ip);

        if (empty($ipInfo)) {
            return $record;
        }

        if (isset($ipInfo['status']) && $ipInfo['status'] === 'error') {
            $record->setLocation('Internal/Reserved IP');
            return $record;
        }

        $region = $ipInfo['subdivisions'][0]['names']['en'] ?? null;
        $city = $ipInfo['city'][0]['names']['en'] ?? null;
        $country = $ipInfo['country']['iso_code'] ?? null;

        $location = implode(
            ', ',
            array_filter([
                $region,
                $city,
                $country,
            ])
        );

        $record->setRegion($region);
        $record->setCity($city);
        $record->setCountry($country);
        $record->setLocation($location);

        $record->setLat($ipInfo['location']['latitude'] ?? null);
        $record->setLon($ipInfo['location']['longitude'] ?? null);
        return $record;
    }
}
