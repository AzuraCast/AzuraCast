<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enums\SimulcastingStatus;
use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Entity\Repository\SimulcastingRepository;
use App\Entity\Traits\HasAutoIncrementId;
use App\Utilities\Strings;
use Stringable;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;

#[
    OA\Schema(type: "object"),
    ORM\Entity(repositoryClass: SimulcastingRepository::class),
    ORM\Table(name: 'station_simulcasting')
]
class Simulcasting implements 
Stringable,
Interfaces\StationAwareInterface,
Interfaces\StationCloneAwareInterface,
Interfaces\IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;
    use Traits\TruncateInts;

    #[
        OA\Property(type: 'string', example: 'My Facebook Stream'),
        ORM\Column(type: 'string', length: 255)
    ]
    public string $name;

    #[
        OA\Property(type: 'integer', format: 'int64'),
        ORM\ManyToOne(targetEntity: Station::class, inversedBy: 'simulcasting_streams'),
        ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')
    ]
    public Station $station;

    public function setStation(Station $station): void
    {
        $this->station = $station;
    }

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    public private(set) int $station_id;

    #[
        OA\Property(type: 'string', example: 'facebook'),
        ORM\Column(type: 'string', length: 50)
    ]
    public string $adapter;

    #[
        OA\Property(type: 'string', example: 'your_stream_key_here'),
        ORM\Column(type: 'string', length: 500)
    ]
    public string $stream_key;

    #[
        OA\Property(type: 'string', example: 'stopped'),
        ORM\Column(type: 'string', length: 20, enumType: SimulcastingStatus::class),
        Attributes\AuditIgnore
    ]
    public SimulcastingStatus $status;

    #[
        OA\Property(type: 'string', nullable: true, example: 'Connection failed'),
        ORM\Column(type: 'text', nullable: true),
        Attributes\AuditIgnore
    ]
    public ?string $error_message = null;

    public function __construct(Station $station, string $name, string $adapter, string $stream_key)
    {
        $this->station = $station;
        $this->name = $name;
        $this->adapter = $adapter;
        $this->stream_key = $stream_key;
        $this->status = SimulcastingStatus::Stopped;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function getAdapter(): string
    {
        return $this->adapter;
    }

    public function setAdapter(string $adapter): void
    {
        $this->adapter = $adapter;
    }

    public function getStreamKey(): string
    {
        return $this->stream_key;
    }

    public function setStreamKey(string $stream_key): void
    {
        $this->stream_key = $stream_key;
    }

    public function getStatus(): SimulcastingStatus
    {
        return $this->status;
    }

    public function setStatus(SimulcastingStatus $status): void
    {
        $this->status = $status;
    }

    public function getErrorMessage(): ?string
    {
        return $this->error_message;
    }

    public function setErrorMessage(?string $error_message): void
    {
        $this->error_message = $error_message;
    }



    public function isRunning(): bool
    {
        return $this->status === SimulcastingStatus::Running;
    }

    public function isStopped(): bool
    {
        return $this->status === SimulcastingStatus::Stopped;
    }

    public function hasError(): bool
    {
        return $this->status === SimulcastingStatus::Error;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}

