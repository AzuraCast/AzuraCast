<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="station_mounts")
 * @Entity(repositoryClass="StationMountRepository")
 * @HasLifecycleCallbacks
 */
class StationMount extends \App\Doctrine\Entity
{
    public function __construct()
    {
        $this->is_default = false;
        $this->enable_autodj = true;
        $this->enable_streamers = false;

        $this->autodj_format = 'mp3';
        $this->autodj_bitrate = 128;
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="station_id", type="integer") */
    protected $station_id;

    /** @Column(name="name", type="string", length=100) */
    protected $name;

    /**
     * Ensure all mountpoint names start with a leading slash.
     * @param $new_name
     */
    public function setName($new_name)
    {
        $this->name = '/'.ltrim($new_name, '/');
    }

    /** @Column(name="is_default", type="boolean", nullable=false) */
    protected $is_default;

    /** @Column(name="fallback_mount", type="string", length=100, nullable=true) */
    protected $fallback_mount;

    /** @Column(name="enable_streamers", type="boolean", nullable=false) */
    protected $enable_streamers;

    /** @Column(name="enable_autodj", type="boolean", nullable=false) */
    protected $enable_autodj;

    /** @Column(name="autodj_format", type="string", length=10, nullable=true) */
    protected $autodj_format;

    /** @Column(name="autodj_bitrate", type="smallint", nullable=true) */
    protected $autodj_bitrate;

    /** @Column(name="frontend_config", type="text", nullable=true) */
    protected $frontend_config;

    /**
     * @ManyToOne(targetEntity="Station", inversedBy="mounts")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $station;
}

use App\Doctrine\Repository;

class StationMountRepository extends Repository
{
    public function getDefaultMount(Station $station)
    {
        return $this->findOneBy(['station_id' => $station->id, 'is_default' => true]);
    }
}