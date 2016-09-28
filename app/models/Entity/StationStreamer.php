<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * Station streamers (DJ accounts) allowed to broadcast to a station.
 *
 * @Table(name="station_streamers")
 * @Entity(repositoryClass="StationStreamerRepository")
 * @HasLifecycleCallbacks
 */
class StationStreamer extends \App\Doctrine\Entity
{
    public function __construct()
    {
        $this->is_active = true;
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="station_id", type="integer") */
    protected $station_id;

    /** @Column(name="streamer_username", type="string", length=50, nullable=false) */
    protected $streamer_username;

    /** @Column(name="streamer_password", type="string", length=50, nullable=false) */
    protected $streamer_password;

    /** @Column(name="comments", type="text", nullable=true) */
    protected $comments;

    /** @Column(name="is_active", type="boolean", nullable=false) */
    protected $is_active;

    /**
     * @ManyToOne(targetEntity="Station", inversedBy="streamers")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $station;
}

use App\Doctrine\Repository;

class StationStreamerRepository extends Repository
{
    /**
     * Attempt to authenticate a streamer.
     *
     * @param Station $station
     * @param $username
     * @param $password
     * @return bool
     */
    public function authenticate(Station $station, $username, $password)
    {
        // Extra safety check for the station's streamer status.
        if (!$station->enable_streamers)
            return false;

        $streamer = $this->findOneBy([
            'station_id' => $station->id,
            'streamer_username' => $username,
            'is_active' => 1
        ]);

        if (!($streamer instanceof StationStreamer))
            return false;

        return (strcmp($streamer->streamer_password, $password) === 0);
    }
}