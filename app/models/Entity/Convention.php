<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="convention", indexes={
 *   @index(name="search_idx", columns={"start_date", "end_date"})
 * })
 * @Entity
 * @HasLifecycleCallbacks
 */
class Convention extends \DF\Doctrine\Entity
{
    use Traits\FileUploads;

    const DEFAULT_IMAGE_FULL = 'images/convention_default.png';

    public function __construct()
    {
        $this->coverage_level = 'full';
        $this->signup_enabled = true;

        $this->archives = new ArrayCollection();
        $this->signups = new ArrayCollection();
    }

    /**
     * @PreRemove
     */
    public function deleting()
    {
        $this->_deleteFile('image_url');
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="name", type="string", length=400) */
    protected $name;

    /** @Column(name="location", type="string", length=400, nullable=true) */
    protected $location;

    /** @Column(name="coverage_level", type="string", length=50) */
    protected $coverage_level;

    /** @Column(name="start_date", type="date") */
    protected $start_date;

    /** @Column(name="end_date", type="date") */
    protected $end_date;

    public function getRange()
    {
        return self::getDateRange($this->start_date, $this->end_date);
    }

    /** @Column(name="web_url", type="string", length=250, nullable=true) */
    protected $web_url;

    /** @Column(name="image_url", type="string", length=200, nullable=true) */
    protected $image_url;

    public function setImageUrl($new_url)
    {
        $this->_processAndCropImage('image_url', $new_url, 600, 300);
    }

    /** @Column(name="schedule_url", type="string", length=250, nullable=true) */
    protected $schedule_url;

    /** @Column(name="signup_notes", type="text", nullable=true) */
    protected $signup_notes;

    /** @Column(name="signup_enabled", type="boolean") */
    protected $signup_enabled;

    public function canSignup()
    {
        if ($this->signup_enabled)
        {
            $end_timestamp = $this->end_date->getTimestamp();
            if ($end_timestamp >= time())
                return true;
            else
                return false;
        }
        else
        {
            return false;
        }
    }

    /**
     * @OneToMany(targetEntity="ConventionSignup", mappedBy="convention")
     */
    protected $signups;

    /**
     * @OneToMany(targetEntity="ConventionArchive", mappedBy="convention")
     */
    protected $archives;

    /**
     * Static Functions
     */

    public static function getAllConventions()
    {
        $all_cons = \DF\Cache::get('homepage_conventions');

        if (!$all_cons)
        {
            $all_cons = array(
                'upcoming' => self::getUpcomingConventions(),
                'archived' => self::getConventionsWithArchives(),
            );

            \DF\Cache::save($all_cons, 'homepage_conventions', array(), 1800);
        }

        return $all_cons;
    }

    public static function getUpcomingConventions()
    {
        $em = self::getEntityManager();

        $start_timestamp = time();
        $end_timestamp = strtotime('+1 year');

        $conventions = $em->createQuery('SELECT c FROM '.__CLASS__.' c WHERE (c.end_date <= :end AND c.end_date >= :start) ORDER BY c.start_date ASC')
            ->setParameter('start', gmdate('Y-m-d', $start_timestamp))
            ->setParameter('end', gmdate('Y-m-d', $end_timestamp))
            ->getArrayResult();

        $coverage = self::getCoverageLevels();
        array_walk($conventions, function(&$row, $key) use ($coverage) {
            $row['short_name'] = self::getConventionShortName($row['name']);
            $row['image'] = self::getConventionImage($row);
            $row['range'] = self::getDateRange($row['start_date'], $row['end_date']);
            $row['coverage'] = $coverage[$row['coverage_level']];
        });

        return $conventions;
    }

    public static function getConventionsWithArchives()
    {
        $em = self::getEntityManager();

        $conventions = $em->createQuery('SELECT c FROM '.__CLASS__.' c LEFT JOIN c.archives ca WHERE ca.id IS NOT NULL AND (c.start_date <= :now) GROUP BY c.id ORDER BY c.start_date DESC')
            ->setParameter('now', gmdate('Y-m-d', time()))
            ->getArrayResult();

        $coverage = self::getCoverageLevels();

        array_walk($conventions, function(&$row, $key) use ($coverage) {
            $row['short_name'] = self::getConventionShortName($row['name']);
            $row['image'] = self::getConventionImage($row);
            $row['range'] = self::getDateRange($row['start_date'], $row['end_date']);
            $row['coverage'] = $coverage[$row['coverage_level']];
        });

        return $conventions;
    }

    public static function getShortNameLookup()
    {
        $short_names = array();
        $archived_conventions = self::getConventionsWithArchives();

        foreach($archived_conventions as $con)
            $short_names[$con['short_name']] = $con;

        return $short_names;
    }

    public static function getConventionShortName($name)
    {
        return strtolower(preg_replace("/[^A-Za-z0-9_]/", '', str_replace(' ', '_', $name)));
    }

    public static function getConventionImage($row)
    {
        if (isset($row['image_url']))
            return $row['image_url'];
        else
            return self::DEFAULT_IMAGE_FULL;
    }

    public static function getCoverageLevels()
    {
        return array(
            'streaming' => array(
                'text'      => 'PVL Full Streaming & Coverage',
                'icon'      => 'icon-globe',
                'short'     => '@',
            ),
            'full'      => array(
                'text'      => 'PVL Full Recorded Coverage',
                'icon'      => 'icon-star',
                'short'     => '*',
            ),
            'partial'   => array(
                'text'      => 'PVL Partial Recorded Coverage',
                'icon'      => 'icon-star-half-full',
                'short'     => '+',
            ),
            'none'      => array(
                'text'      => 'No Coverage',
                'icon'      => 'icon-flag',
                'short'     => '!',
            ),
        );
    }

    public static function getDateRange(\DateTime $start, \DateTime $end)
    {
        $start_month = $start->format('M');
        $start_day = $start->format('j');
        $start_year = $start->format('Y');

        $end_month = $end->format('M');
        $end_day = $end->format('j');
        $end_year = $end->format('Y');

        if ($start_year == $end_year)
        {
            if (strcmp($start_month, $end_month) == 0)
            {
                if ($start_day == $end_day)
                    return $start_month.' '.$start_day.', '.$end_year;
                else
                    return $start_month.' '.$start_day.'-'.$end_day.', '.$end_year;
            }
            else
            {
                return $start_month.' '.$start_day.' to '.$end_month.' '.$end_day.', '.$end_year;
            }
        }
        else
        {
            return $start_month.' '.$start_day.', '.$start_year.' to '.$end_month.' '.$end_day.', '.$end_year;
        }
    }

    public static function api($row)
    {
        $coverage_levels = self::getCoverageLevels();

        $con = array(
            'id'        => $row['id'],
            'name'      => $row['name'],
            'location'  => $row['location'],
            'coverage'  => $row['coverage_level'],
            'coverage_details' => $coverage_levels[$row['coverage_level']],
            'date_range' => self::getDateRange($row['start_date'], $row['end_date']),
            'start_date' => $row['start_date'],
            'end_date'  => $row['end_date'],
            'web_url'   => $row['web_url'],
        );

        if (!empty($row['image_url']))
            $con['image_url'] = \PVL\Url::upload($row['image_url']);

        if (!empty($row['thumbnail_url']))
            $con['thumbnail_url'] = \PVL\Url::upload($row['thumbnail_url']);

        return $con;
    }
}