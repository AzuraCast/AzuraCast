<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use Doctrine\ORM\Mapping as ORM;
use NowPlaying\Result\Client;

#[
    ORM\Entity,
    ORM\Table(name: 'listener'),
    ORM\Index(columns: ['timestamp_end', 'timestamp_start'], name: 'idx_timestamps')
]
class Listener implements IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    #[ORM\Column(nullable: false)]
    protected int $station_id;

    #[ORM\ManyToOne(inversedBy: 'history')]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected Station $station;

    #[ORM\Column(nullable: true)]
    protected ?int $mount_id = null;

    #[ORM\ManyToOne(targetEntity: StationMount::class)]
    #[ORM\JoinColumn(name: 'mount_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?StationMount $mount = null;

    #[ORM\Column(nullable: true)]
    protected ?int $remote_id = null;

    #[ORM\ManyToOne(targetEntity: StationRemote::class)]
    #[ORM\JoinColumn(name: 'remote_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?StationRemote $remote = null;

    #[ORM\Column]
    protected int $listener_uid;

    #[ORM\Column(length: 45)]
    protected string $listener_ip;

    #[ORM\Column(length: 255)]
    protected string $listener_user_agent;

    #[ORM\Column(length: 32)]
    protected string $listener_hash;

    #[ORM\Column]
    protected int $timestamp_start;

    #[ORM\Column]
    protected int $timestamp_end;

    public function __construct(Station $station, Client $client)
    {
        $this->station = $station;

        $this->timestamp_start = time();
        $this->timestamp_end = 0;

        $this->listener_uid = (int)$client->uid;
        $this->listener_user_agent = $this->truncateString($client->userAgent);
        $this->listener_ip = $client->ip;
        $this->listener_hash = self::calculateListenerHash($client);
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function getMount(): ?StationMount
    {
        return $this->mount;
    }

    public function getMountId(): ?int
    {
        return $this->mount_id;
    }

    public function setMount(?StationMount $mount): void
    {
        $this->mount = $mount;
    }

    public function getRemote(): ?StationRemote
    {
        return $this->remote;
    }

    public function getRemoteId(): ?int
    {
        return $this->remote_id;
    }

    public function setRemote(?StationRemote $remote): void
    {
        $this->remote = $remote;
    }

    public function getListenerUid(): int
    {
        return $this->listener_uid;
    }

    public function getListenerIp(): string
    {
        return $this->listener_ip;
    }

    public function getListenerUserAgent(): string
    {
        return $this->listener_user_agent;
    }

    public function getListenerHash(): string
    {
        return $this->listener_hash;
    }

    public function getTimestampStart(): int
    {
        return $this->timestamp_start;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp_start;
    }

    public function getTimestampEnd(): int
    {
        return $this->timestamp_end;
    }

    public function setTimestampEnd(int $timestamp_end): void
    {
        $this->timestamp_end = $timestamp_end;
    }

    public function getConnectedSeconds(): int
    {
        return $this->timestamp_end - $this->timestamp_start;
    }

    /**
     * Filter clients to exclude any listeners that shouldn't be included (i.e. relays).
     *
     * @param array $clients
     *
     * @return mixed[]
     */
    public static function filterClients(array $clients): array
    {
        return array_filter(
            $clients,
            static function ($client) {
                // Ignore clients with the "Icecast" UA as those are relays and not listeners.
                return !(false !== stripos($client['user_agent'], 'Icecast'));
            }
        );
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
