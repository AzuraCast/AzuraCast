<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="convention", indexes={
 *   @index(name="search_idx", columns={"start_date", "end_date"})
 * })
 * @Entity
 */
class Convention extends \DF\Doctrine\Entity
{
    public function __construct()
    {
        $this->coverage_level = 'full';
        $this->signup_enabled = true;

        $this->archives = new ArrayCollection();
        $this->signups = new ArrayCollection();
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
        if ($new_url)
        {
            if ($this->image_url && $this->image_url != $new_url)
                @unlink(DF_UPLOAD_FOLDER.DIRECTORY_SEPARATOR.$this->image_url);

            echo $new_url;

            $new_path = DF_UPLOAD_FOLDER.DIRECTORY_SEPARATOR.$new_url;
            \DF\Image::resizeImage($new_path, $new_path, 1150, 200);

            $this->image_url = $new_url;
        }
    }

    /** @Column(name="schedule_url", type="string", length=250, nullable=true) */
    protected $schedule_url;

    /** @Column(name="signup_notes", type="text", nullable=true) */
    protected $signup_notes;

    /** @Column(name="signup_enabled", type="boolean") */
    protected $signup_enabled;

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

    public static function getUpcomingConventions()
    {
        $em = self::getEntityManager();

        $start_timestamp = strtotime('-3 days');
        $end_timestamp = strtotime('+1 year');

        $conventions = $em->createQuery('SELECT c FROM '.__CLASS__.' c WHERE (c.start_date <= :end AND c.end_date >= :start) ORDER BY c.start_date ASC')
            ->setParameter('start', gmdate('Y-m-d', $start_timestamp))
            ->setParameter('end', gmdate('Y-m-d', $end_timestamp))
            ->useResultCache(true, 1800, 'pvl_upcoming_conventions')
            ->getArrayResult();

        $coverage = self::getCoverageLevels();
        array_walk($conventions, function(&$row, $key) use ($coverage) {
            $row['range'] = self::getDateRange($row['start_date'], $row['end_date']);
            $row['coverage'] = $coverage[$row['coverage_level']];
        });

        return $conventions;
    }

    public static function getConventionsWithArchives()
    {
        $em = self::getEntityManager();

        $conventions = $em->createQuery('SELECT c FROM '.__CLASS__.' c LEFT JOIN c.archives ca WHERE ca.id IS NOT NULL AND (c.start_date <= :now) ORDER BY c.start_date DESC')
            ->setParameter('now', gmdate('Y-m-d', time()))
            ->useResultCache(true, 1800, 'pvl_archived_conventions')
            ->getArrayResult();

        $coverage = self::getCoverageLevels();
        array_walk($conventions, function(&$row, $key) use ($coverage) {
            $row['range'] = self::getDateRange($row['start_date'], $row['end_date']);
            $row['coverage'] = $coverage[$row['coverage_level']];
        });

        return $conventions;
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
        $start_day = $start->format('M j');
        $start_year = $start->format('Y');

        $end_day = $end->format('M j');
        $end_year = $end->format('Y');

        if ($start_year == $end_year)
        {
            if ($start_day == $end_day)
                return $start_day.', '.$start_year;
            else
                return $start_day.' to '.$end_day.', '.$end_year;
        }
        else
        {
            return $start_day.', '.$start_year.' to '.$end_day.', '.$end_year;
        }
    }
}