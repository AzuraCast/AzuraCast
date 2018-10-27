<?php
namespace App\Entity;

/**
 * Station streamers (DJ accounts) allowed to broadcast to a station.
 *
 * @Table(name="station_streamers")
 * @Entity(repositoryClass="App\Entity\Repository\StationStreamerRepository")
 * @HasLifecycleCallbacks
 */
class StationStreamer
{
    use Traits\TruncateStrings;

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     * @var int
     */
    protected $id;

    /**
     * @Column(name="station_id", type="integer")
     * @var int
     */
    protected $station_id;

    /**
     * @ManyToOne(targetEntity="Station", inversedBy="streamers")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @Column(name="streamer_username", type="string", length=50, nullable=false)
     * @var string
     */
    protected $streamer_username;

    /**
     * @Column(name="streamer_password", type="string", length=50, nullable=false)
     * @var string
     */
    protected $streamer_password;

    /**
     * @Column(name="display_name", type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $display_name;

    /**
     * @Column(name="comments", type="text", nullable=true)
     * @var string|null
     */
    protected $comments;

    /**
     * @Column(name="is_active", type="boolean", nullable=false)
     * @var bool
     */
    protected $is_active;

	/**
     * @Column(name="reactivate_at", type="integer", nullable=true)
     * @var int|null
     */
    protected $reactivate_at;

    public function __construct(Station $station)
    {
        $this->station = $station;

        $this->is_active = true;
    }

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
     * @return string
     */
    public function getStreamerUsername(): string
    {
        return $this->streamer_username;
    }

    /**
     * @param string $streamer_username
     */
    public function setStreamerUsername(string $streamer_username)
    {
        $this->streamer_username = $this->_truncateString($streamer_username, 50);
    }

    /**
     * @return string
     */
    public function getStreamerPassword(): string
    {
        return $this->streamer_password;
    }

    /**
     * @param string $streamer_password
     */
    public function setStreamerPassword(string $streamer_password)
    {
        $this->streamer_password = $this->_truncateString($streamer_password, 50);
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return (!empty($this->display_name))
            ? $this->display_name
            :$this->streamer_username;
    }

    /**
     * @param null|string $display_name
     */
    public function setDisplayName(?string $display_name): void
    {
        $this->display_name = $this->_truncateString($display_name);
    }

    /**
     * @return null|string
     */
    public function getComments(): ?string
    {
        return $this->comments;
    }

    /**
     * @param null|string $comments
     */
    public function setComments(string $comments = null)
    {
        $this->comments = $comments;
    }

    /**
     * @return bool
     */
    public function getIsActive(): bool
    {
        return $this->is_active;
    }

    /**
     * @param bool $is_active
     */
    public function setIsActive(bool $is_active)
    {
        $this->is_active = $is_active;

        // Automatically set the "reactivate_at" flag to null if the DJ is for any reason reactivated.
        if (true === $is_active) {
            $this->reactivate_at = null;
        }
    }

	/**
     * @return int|null
     */
    public function getReactivateAt(): ?int
    {
        return $this->reactivate_at;
    }

    /**
     * @param int|null $reactivate_at
     */
    public function setReactivateAt(?int $reactivate_at)
    {
        $this->reactivate_at = $reactivate_at;
    }

    /**
     * Deactivate this streamer for the specified period of time.
     *
     * @param int $seconds
     */
    public function deactivateFor(int $seconds)
    {
        $this->is_active = false;
        $this->reactivate_at = time()+$seconds;
    }
}
