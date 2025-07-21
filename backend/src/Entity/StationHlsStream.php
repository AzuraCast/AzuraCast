<?php

declare(strict_types=1);

namespace App\Entity;

use App\Radio\Enums\StreamFormats;
use App\Utilities\Strings;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[
    OA\Schema(type: "object"),
    ORM\Entity,
    ORM\Table(name: 'station_hls_streams'),
    Attributes\Auditable
]
final class StationHlsStream implements
    Stringable,
    Interfaces\StationAwareInterface,
    Interfaces\StationCloneAwareInterface,
    Interfaces\IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;
    use Traits\TruncateInts;
    use Traits\ValidateMaxBitrate;

    #[
        ORM\ManyToOne(inversedBy: 'hls_streams'),
        ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')
    ]
    public Station $station;

    public function setStation(Station $station): void
    {
        $this->station = $station;
    }

    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    public private(set) int $station_id;

    #[
        OA\Property(example: "aac_lofi"),
        ORM\Column(length: 100),
        Assert\NotBlank
    ]
    public string $name = '' {
        set => $this->truncateString(Strings::getProgrammaticString($value), 100);
    }

    #[
        OA\Property(example: "aac"),
        ORM\Column(type: 'string', length: 10, nullable: true, enumType: StreamFormats::class)
    ]
    public ?StreamFormats $format = StreamFormats::Aac;

    #[
        OA\Property(example: 128),
        ORM\Column(type: 'smallint', nullable: true)
    ]
    public ?int $bitrate = 128;

    #[Assert\Callback]
    public function hasValidBitrate(ExecutionContextInterface $context): void
    {
        $this->doValidateMaxBitrate(
            $context,
            $this->station->max_bitrate,
            $this->bitrate,
            'bitrate'
        );
    }

    #[
        ORM\Column,
        Attributes\AuditIgnore
    ]
    public int $listeners = 0;

    public function __construct(Station $station)
    {
        $this->station = $station;
    }

    public function __toString(): string
    {
        return $this->station . ' HLS Stream: ' . $this->name;
    }
}
