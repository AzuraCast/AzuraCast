<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use NowPlaying\Result\Client;

#[
    ORM\Entity,
    ORM\Table(name: 'listener'),
    ORM\Index(name: 'idx_timestamps', columns: ['timestamp_end', 'timestamp_start']),
    ORM\Index(name: 'idx_statistics_country', columns: ['location_country']),
    ORM\Index(name: 'idx_statistics_os', columns: ['device_os_family']),
    ORM\Index(name: 'idx_statistics_browser', columns: ['device_browser_family'])
]
final class Listener implements
    Interfaces\IdentifiableEntityInterface,
    Interfaces\StationAwareInterface
{
    use Traits\HasAutoIncrementId;

    #[ORM\ManyToOne(inversedBy: 'history')]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public readonly Station $station;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    public private(set) int $station_id;

    #[ORM\ManyToOne(targetEntity: StationMount::class)]
    #[ORM\JoinColumn(name: 'mount_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    public readonly ?StationMount $mount;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: true, insertable: false, updatable: false)]
    public private(set) ?int $mount_id = null;

    #[ORM\ManyToOne(targetEntity: StationRemote::class)]
    #[ORM\JoinColumn(name: 'remote_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    public readonly ?StationRemote $remote;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: true, insertable: false, updatable: false)]
    public private(set) ?int $remote_id = null;

    #[ORM\ManyToOne(targetEntity: StationHlsStream::class)]
    #[ORM\JoinColumn(name: 'hls_stream_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    public readonly ?StationHlsStream $hls_stream;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: true, insertable: false, updatable: false)]
    public private(set) ?int $hls_stream_id = null;

    #[ORM\Column]
    public int $listener_uid;

    #[ORM\Column(length: 45)]
    public string $listener_ip;

    #[ORM\Column(length: 255)]
    public string $listener_user_agent;

    #[ORM\Column(length: 32)]
    public string $listener_hash;

    #[ORM\Column(type: 'datetime_immutable', precision: 6)]
    public DateTimeImmutable $timestamp_start;

    #[ORM\Column(type: 'datetime_immutable', precision: 6, nullable: true)]
    public ?DateTimeImmutable $timestamp_end;

    #[ORM\Embedded(class: ListenerLocation::class, columnPrefix: 'location_')]
    public ListenerLocation $location;

    #[ORM\Embedded(class: ListenerDevice::class, columnPrefix: 'device_')]
    public ListenerDevice $device;

    public function __construct(
        Station $station,
        ?StationMount $mount,
        ?StationRemote $remote,
        ?StationHlsStream $hls_stream,
        int $listener_uid,
        string $listener_ip,
        string $listener_user_agent,
        string $listener_hash,
        DateTimeImmutable $timestamp_start,
        ?DateTimeImmutable $timestamp_end,
        ListenerLocation $location,
        ListenerDevice $device
    ) {
        $this->station = $station;
        $this->mount = $mount;
        $this->remote = $remote;
        $this->hls_stream = $hls_stream;
        $this->listener_uid = $listener_uid;
        $this->listener_ip = $listener_ip;
        $this->listener_user_agent = $listener_user_agent;
        $this->listener_hash = $listener_hash;
        $this->timestamp_start = $timestamp_start;
        $this->timestamp_end = $timestamp_end;
        $this->location = $location;
        $this->device = $device;
    }

    public static function getListenerSeconds(array $intervals): int
    {
        // Sort by start time.
        usort(
            $intervals,
            static function ($a, $b) {
                return $a['start'] <=> $b['start'];
            }
        );

        $seconds = 0;

        while (count($intervals) > 0) {
            $currentInterval = array_shift($intervals);
            $start = $currentInterval['start'];
            $end = $currentInterval['end'];

            foreach ($intervals as $intervalKey => $interval) {
                // Starts after this interval ends; no more entries to process
                if ($interval['start'] > $end) {
                    break;
                }

                // Extend the current interval's end
                if ($interval['end'] > $end) {
                    $end = $interval['end'];
                }

                unset($intervals[$intervalKey]);
            }

            $seconds += $end - $start;
        }

        return $seconds;
    }

    public static function calculateListenerHash(Client $client): string
    {
        $hashParts = $client->ip . $client->userAgent;
        if (!empty($client->mount)) {
            $hashParts .= $client->mount;
        }

        return md5($hashParts);
    }
}
