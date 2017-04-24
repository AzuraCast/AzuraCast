<?php
namespace Entity;

/**
 * @Table(name="station_mounts")
 * @Entity(repositoryClass="Entity\Repository\StationMountRepository")
 * @HasLifecycleCallbacks
 */
class StationMount extends \App\Doctrine\Entity
{
    public function __construct()
    {
        $this->name = '';
        $this->is_default = false;
        $this->enable_autodj = true;

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
        $this->name = '/' . ltrim($new_name, '/');
    }

    /** @Column(name="is_default", type="boolean", nullable=false) */
    protected $is_default;

    /** @Column(name="fallback_mount", type="string", length=100, nullable=true) */
    protected $fallback_mount;

    /** @Column(name="relay_url", type="string", length=255, nullable=true) */
    protected $relay_url;

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