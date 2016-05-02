<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="station_streams")
 * @Entity
 */
class StationStream extends \App\Doctrine\Entity
{
    public function __construct()
    {
        $this->is_default = 0;
        $this->is_active = 1;
        $this->hidden_from_player = 0;

        $this->history = new ArrayCollection;
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="station_id", type="integer") */
    protected $station_id;

    /** @Column(name="is_default", type="boolean") */
    protected $is_default;

    /** @Column(name="is_active", type="boolean") */
    protected $is_active;

    /** @Column(name="hidden_from_player", type="boolean") */
    protected $hidden_from_player;

    /** @Column(name="name", type="string", length=75, nullable=true) */
    protected $name;

    /** @Column(name="type", type="string", length=50, nullable=true) */
    protected $type;

    /** @Column(name="stream_url", type="string", length=150, nullable=true) */
    protected $stream_url;

    /** @Column(name="nowplaying_url", type="string", length=100, nullable=true) */
    protected $nowplaying_url;

    /** @Column(name="nowplaying_data", type="json", nullable=true) */
    protected $nowplaying_data;

    /**
     * @ManyToOne(targetEntity="Station", inversedBy="streams")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $station;

    /**
     * @OneToMany(targetEntity="SongHistory", mappedBy="station")
     * @OrderBy({"timestamp" = "DESC"})
     */
    protected $history;

    public function isPlaying()
    {
        $np_data = (array)$this->nowplaying_data;

        if (isset($np_data['status']))
            return ($np_data['status'] != 'offline');
        else
            return false;
    }

    /*
     * Static Functions
     */

    public static function getMainRadioStreams()
    {
        $em = self::getEntityManager();

        $streams_raw = $em->createQuery('SELECT ss.id FROM '.__CLASS__.' ss JOIN ss.station s WHERE ss.is_default = 1 AND ss.is_active = 1 AND s.category = :category')
            ->setParameter('category', 'audio')
            ->getArrayResult();

        $streams = array();
        foreach($streams_raw as $row)
            $streams[] = $row['id'];

        return $streams;
    }

    /**
     * Return an API standardized object.
     *
     * @param $row
     * @return array
     */
    public static function api($row)
    {
        return array(
            'id'            => (int)$row['id'],
            'name'          => $row['name'],
            'url'           => $row['stream_url'],
            'type'          => $row['type'],
            'is_default'    => (bool)$row['is_default'],
        );
    }
}