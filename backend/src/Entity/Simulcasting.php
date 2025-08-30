<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enums\SimulcastingStatus;
use App\Entity\Repository\SimulcastingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SimulcastingRepository::class)]
#[ORM\Table(name: 'station_simulcasting')]
class Simulcasting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: Station::class, inversedBy: 'simulcasting_streams')]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Station $station;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $adapter;

    #[ORM\Column(type: Types::STRING, length: 500)]
    private string $stream_key;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: SimulcastingStatus::class)]
    private SimulcastingStatus $status;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $error_message = null;

    #[ORM\Column(type: Types\DateTimeImmutable::class)]
    private \DateTimeImmutable $created_at;

    #[ORM\Column(type: Types\DateTimeImmutable::class)]
    private \DateTimeImmutable $updated_at;

    public function __construct(Station $station, string $name, string $adapter, string $stream_key)
    {
        $this->station = $station;
        $this->name = $name;
        $this->adapter = $adapter;
        $this->stream_key = $stream_key;
        $this->status = SimulcastingStatus::Stopped;
        $this->created_at = new \DateTimeImmutable();
        $this->updated_at = new \DateTimeImmutable();
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
        $this->updated_at = new \DateTimeImmutable();
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
        $this->updated_at = new \DateTimeImmutable();
    }

    public function getStreamKey(): string
    {
        return $this->stream_key;
    }

    public function setStreamKey(string $stream_key): void
    {
        $this->stream_key = $stream_key;
        $this->updated_at = new \DateTimeImmutable();
    }

    public function getStatus(): SimulcastingStatus
    {
        return $this->status;
    }

    public function setStatus(SimulcastingStatus $status): void
    {
        $this->status = $status;
        $this->updated_at = new \DateTimeImmutable();
    }

    public function getErrorMessage(): ?string
    {
        return $this->error_message;
    }

    public function setErrorMessage(?string $error_message): void
    {
        $this->error_message = $error_message;
        $this->updated_at = new \DateTimeImmutable();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updated_at;
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
}

