<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="statistics")
 * @Entity
 */
class Statistic extends \DF\Doctrine\Entity
{
    public function __construct()
    {
        $this->timestamp = new \DateTime('NOW');
        $this->total = 0;
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="timestamp", type="datetime") */
    protected $timestamp;

    /** @Column(name="total_overall", type="integer", nullable=true) */
    protected $total_overall;

    /** @Column(name="total_stations", type="json", nullable=true) */
    protected $total_stations;

    public static function post($nowplaying)
    {
        $timestamp = new \DateTime(date('Y-m-d H:i').':00');
        $stat = self::getRepository()->findOneBy(array('timestamp' => $timestamp));

        $total_overall = 0;
        $total_stations = array();

        foreach($nowplaying as $short_code => $info)
        {
            $total_overall += (int)$info['listeners'];
            $total_stations[$short_code] = (int)$info['listeners'];
        }

        if ($stat instanceof self)
        {
            if ($stat->total_overall < $total_overall)
            {
                $stat->total_overall = $total_overall;
                $stat->total_stations = $total_stations;
                $stat->save();
            }
        }
        else
        {
            $stat = new self;
            $stat->timestamp = $timestamp;
            $stat->total_overall = $total_overall;
            $stat->total_stations = $total_stations;
            $stat->save();
        }
    }
}