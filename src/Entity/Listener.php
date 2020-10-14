<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use NowPlaying\Result\Client;

/**
 * @ORM\Table(name="listener", indexes={
 *     @ORM\Index(name="idx_timestamps", columns={"timestamp_end", "timestamp_start"}),
 * })
 * @ORM\Entity()
 */
class Listener
{
    use Traits\TruncateStrings;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(name="station_id", type="integer")
     * @var int
     */
    protected $station_id;

    /**
     * @ORM\ManyToOne(targetEntity="Station", inversedBy="history")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @ORM\Column(name="listener_uid", type="integer")
     * @var int
     */
    protected $listener_uid;

    /**
     * @ORM\Column(name="listener_ip", type="string", length=45)
     * @var string
     */
    protected $listener_ip;

    /**
     * @ORM\Column(name="listener_user_agent", type="string", length=255)
     * @var string
     */
    protected $listener_user_agent;

    /**
     * @ORM\Column(name="listener_hash", type="string", length=32)
     * @var string
     */
    protected $listener_hash;

    /**
     * @ORM\Column(name="timestamp_start", type="integer")
     * @var int
     */
    protected $timestamp_start;

    /**
     * @ORM\Column(name="timestamp_end", type="integer")
     * @var int
     */
    protected $timestamp_end;

    public function __construct(Station $station, Client $client)
    {
        $this->station = $station;

        $this->timestamp_start = time();
        $this->timestamp_end = 0;

        $this->listener_uid = (int)$client->uid;
        $this->listener_user_agent = $this->truncateString($client->userAgent) ?? '';
        $this->listener_ip = $client->ip;
        $this->listener_hash = self::calculateListenerHash($client);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStation(): Station
    {
        return $this->station;
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
        return array_filter($clients, function ($client) {
            // Ignore clients with the "Icecast" UA as those are relays and not listeners.
            return !(false !== stripos($client['user_agent'], 'Icecast'));
        });
    }

    public static function getListenerSeconds(array $intervals): int
    {
        // Sort by start time.
        usort($intervals, function ($a, $b) {
            return $a['start'] <=> $b['start'];
        });

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

    /**
     * @param array|Client $client
     */
    public static function calculateListenerHash($client): string
    {
        if ($client instanceof Client) {
            return md5($client->ip . $client->userAgent);
        }

        return md5($client['ip'] . $client['user_agent']);
    }
}
