<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="archive_genre")
 * @Entity
 */
class ArchiveGenre extends \DF\Doctrine\Entity
{
    public function __construct()
    {
        $this->songs = new ArrayCollection;
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /** @Column(name="name", type="string", length=50) */
    protected $name;

    /** @ManyToMany(targetEntity="ArchiveSong", mappedBy="genres") */
    protected $songs;

    public static function getTotals()
    {
        $totals = \DF\Cache::get('archive_genre_totals');

        if (!$totals)
        {
            $em = self::getEntityManager();
            $conn = $em->getConnection();

            $totals_raw = $conn->fetchAll('SELECT ag.id, ag.name, COUNT(ashg.song_id) AS songs FROM archive_genre AS ag LEFT JOIN archive_song_has_genre AS ashg ON ashg.genre_id = ag.id GROUP BY ag.id ORDER BY ag.name ASC');

            $totals = array();
            foreach($totals_raw as $row)
            {
                $totals[$row['id']] = array(
                    'name' => $row['name'],
                    'total' => $row['songs'],
                );
            }

            \DF\Cache::save($totals, 'archive_genre_totals', array(), 600);
        }

        return $totals;
    }

    public static function getTop($num_to_show = 5)
    {
        $all_totals = self::getTotals();

        $totals = array_filter($all_totals, function($var) {
            return ($var['total'] > 2);
        });

        uasort($totals, function($a, $b) {
            return $b['total'] - $a['total'];
        });

        return array_slice($totals, 0, $num_to_show);
    }
}