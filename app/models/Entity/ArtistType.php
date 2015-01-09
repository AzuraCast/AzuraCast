<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="artist_type")
 * @Entity
 */
class ArtistType extends \DF\Doctrine\Entity
{
    public function __construct()
    {
        $this->artists = new ArrayCollection;
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /** @Column(name="name", type="string", length=50) */
    protected $name;

    /** @Column(name="icon", type="string", length=50) */
    protected $icon;

    /** @ManyToMany(targetEntity="Artist", mappedBy="types") */
    protected $artists;

    /**
     * Static Functions
     */

    public static function getTypeNames()
    {
        $records_raw = self::fetchArray();
        $records = array();

        foreach($records_raw as $record)
            $records[$record['id']] = $record['name'];

        return $records;
    }

    public static function getTypeIcons()
    {
        $records_raw = self::fetchArray();
        $records = array();

        foreach($records_raw as $record)
            $records[$record['id']] = $record['icon'];

        return $records;
    }

    public static function getTypeTotals()
    {
        $totals = \DF\Cache::get('artist_totals');

        if (!$totals)
        {
            $totals = array();

            $records = self::fetchAll();
            foreach($records as $record)
            {
                $totals['types'][$record->id] = array(
                    'name' => $record->name,
                    'icon' => $record->icon,
                    'count' => count($record->artists),
                );
            }

            $totals['overall'] = array(
                'count' => count(Artist::fetchArray()),
            );

            \DF\Cache::save($totals, 'artist_totals', array(), 600);
        }

        return $totals;
    }
}