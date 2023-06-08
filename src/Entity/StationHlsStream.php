<?php

declare(strict_types=1);

namespace App\Entity;

use App\Radio\Enums\StreamFormats;
use App\Utilities\Strings;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

#[
    OA\Schema(type: "object"),
    ORM\Entity,
    ORM\Table(name: 'station_hls_streams'),
    Attributes\Auditable
]
class StationHlsStream implements
    Stringable,
    Interfaces\StationCloneAwareInterface,
    Interfaces\IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;
    use Traits\TruncateInts;

    #[
        ORM\ManyToOne(inversedBy: 'hls_streams'),
        ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')
    ]
    protected Station $station;

    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    protected int $station_id;

    #[
        OA\Property(example: "aac_lofi"),
        ORM\Column(length: 100),
        Assert\NotBlank
    ]
    protected string $name = '';

    #[
        OA\Property(example: "aac"),
        ORM\Column(type: 'string', length: 10, nullable: true, enumType: StreamFormats::class)
    ]
    protected ?StreamFormats $format = StreamFormats::Aac;

    #[
        OA\Property(example: 128),
        ORM\Column(type: 'smallint', nullable: true)
    ]
    protected ?int $bitrate = 128;

    #[
        ORM\Column,
        Attributes\AuditIgnore
    ]
    protected int $listeners = 0;

    public function __construct(Station $station)
    {
        $this->station = $station;
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function setStation(Station $station): void
    {
        $this->station = $station;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $newName): void
    {
        // Ensure all mount point names start with a leading slash.
        $this->name = $this->truncateString(Strings::getProgrammaticString($newName), 100);
    }

    public function getFormat(): ?StreamFormats
    {
        return $this->format;
    }

    public function setFormat(?StreamFormats $format): void
    {
        $this->format = $format;
    }

    public function getBitrate(): ?int
    {
        return $this->bitrate;
    }

    public function setBitrate(?int $bitrate): void
    {
        $this->bitrate = $bitrate;
    }

    public function getListeners(): int
    {
        return $this->listeners;
    }

    public function setListeners(int $listeners): void
    {
        $this->listeners = $listeners;
    }

    public function __toString(): string
    {
        return $this->getStation() . ' HLS Stream: ' . $this->getName();
    }
}
