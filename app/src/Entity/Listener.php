<?php
namespace Entity;

/**
 * @Table(name="listener", indexes={
 *   @index(name="update_idx", columns={"listener_hash"}),
 *   @index(name="search_idx", columns={"listener_uid", "timestamp_end"})
 * })
 * @Entity(repositoryClass="Entity\Repository\ListenerRepository")
 */
class Listener
{
    /**
     * Listener constructor.
     * @param Station $station
     * @param $client
     */
    public function __construct(Station $station, $client)
    {
        $this->station = $station;

        $this->timestamp_start = time();
        $this->timestamp_end = 0;

        $this->listener_uid = $client['uid'];
        $this->listener_user_agent = $client['user_agent'];
        $this->listener_ip = $client['ip'];
        $this->listener_hash = self::calculateListenerHash($client);
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @Column(name="station_id", type="integer")
     * @var int
     */
    protected $station_id;

    /**
     * @ManyToOne(targetEntity="Station", inversedBy="history")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @Column(name="listener_uid", type="integer")
     * @var int
     */
    protected $listener_uid;

    /**
     * @Column(name="listener_ip", type="string", length=45)
     * @var string
     */
    protected $listener_ip;

    /**
     * @Column(name="listener_user_agent", type="string", length=255)
     * @var string
     */
    protected $listener_user_agent;

    /**
     * @Column(name="listener_hash", type="string", length=32)
     * @var string
     */
    protected $listener_hash;

    /**
     * @Column(name="timestamp_start", type="integer")
     * @var int
     */
    protected $timestamp_start;

    /**
     * @Column(name="timestamp_end", type="integer")
     * @var int
     */
    protected $timestamp_end;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Station
     */
    public function getStation(): Station
    {
        return $this->station;
    }

    /**
     * @return int
     */
    public function getListenerUid(): int
    {
        return $this->listener_uid;
    }

    /**
     * @return string
     */
    public function getListenerIp(): string
    {
        return $this->listener_ip;
    }

    /**
     * @return string
     */
    public function getListenerUserAgent(): string
    {
        return $this->listener_user_agent;
    }

    /**
     * @return string
     */
    public function getListenerHash(): string
    {
        return $this->listener_hash;
    }

    /**
     * @return int
     */
    public function getTimestampStart(): int
    {
        return $this->timestamp_start;
    }

    /**
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp_start;
    }

    /**
     * @return int
     */
    public function getTimestampEnd(): int
    {
        return $this->timestamp_end;
    }

    /**
     * @param int $timestamp_end
     */
    public function setTimestampEnd(int $timestamp_end)
    {
        $this->timestamp_end = $timestamp_end;
    }

    /**
     * @return int
     */
    public function getConnectedSeconds(): int
    {
        return $this->timestamp_end - $this->timestamp_start;
    }

    /**
     * @param $client
     * @return string
     */
    public static function calculateListenerHash($client): string
    {
        return md5($client['ip'].$client['user_agent']);
    }
}