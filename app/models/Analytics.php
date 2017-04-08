<?php
namespace Entity;

/**
 * @Table(name="analytics", indexes={
 *   @index(name="search_idx", columns={"type", "timestamp"})
 * })
 * @Entity
 */
class Analytics extends \App\Doctrine\Entity
{
    public function __construct()
    {
        $this->timestamp = time();
        $this->number_min = 0;
        $this->number_max = 0;
        $this->number_avg = 0;
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="station_id", type="integer", nullable=true) */
    protected $station_id;

    /** @Column(name="type", type="string", length=15) */
    protected $type;

    /** @Column(name="timestamp", type="integer") */
    protected $timestamp;

    /** @Column(name="number_min", type="integer") */
    protected $number_min;

    /** @Column(name="number_max", type="integer") */
    protected $number_max;

    /** @Column(name="number_avg", type="integer") */
    protected $number_avg;

    /**
     * @ManyToOne(targetEntity="Station")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $station;

    public function calculateFromArray($number_set)
    {
        $number_set = (array)$number_set;

        $this->number_min = (int)min($number_set);
        $this->number_max = (int)max($number_set);
        $this->number_avg = (int)(array_sum($number_set) / count($number_set));
    }
}