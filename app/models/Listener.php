<?php
namespace Entity;

/**
 * @Table(name="listener", indexes={
 *   @index(name="update_idx", columns={"listener_uid", "listener_ip"}),
 * })
 * @Entity(repositoryClass="Entity\Repository\ListenerRepository")
 */
class Listener extends \App\Doctrine\Entity
{
    public function __construct()
    {
        $this->timestamp_start = time();
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /** @Column(name="station_id", type="integer") */
    protected $station_id;

    /** @Column(name="listener_uid", type="integer") */
    protected $listener_uid;

    /** @Column(name="listener_ip", type="string", length=45) */
    protected $listener_ip;

    /** @Column(name="listener_user_agent", type="string", length=255) */
    protected $listener_user_agent;

    /** @Column(name="timestamp_start", type="integer") */
    protected $timestamp_start;

    public function getTimestamp()
    {
        return $this->timestamp_start;
    }

    /** @Column(name="timestamp_end", type="integer") */
    protected $timestamp_end;

    public function getConnectedSeconds()
    {
        return $this->timestamp_end - $this->timestamp_start;
    }

    /**
     * @ManyToOne(targetEntity="Station", inversedBy="history")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $station;
}