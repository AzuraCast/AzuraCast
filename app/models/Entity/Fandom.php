<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="fandoms")
 * @Entity
 */
class Fandom extends \DF\Doctrine\Entity
{
    public function __construct()
    {
        $this->songs = new ArrayCollection;
        $this->stations = new ArrayCollection;
        $this->podcasts = new ArrayCollection;
        $this->conventions = new ArrayCollection;
        $this->news = new ArrayCollection;
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /** @Column(name="name", type="string", length=50) */
    protected $name;

    /** @Column(name="abbr", type="string", length=10) */
    protected $abbr;

    /** @Column(name="class", type="string", length=50) */
    protected $class;


    /** @OneToMany(targetEntity="Song", mappedBy="fandom") */
    protected $songs;

    /** @OneToMany(targetEntity="Station", mappedBy="fandom") */
    protected $stations;

    /** @OneToMany(targetEntity="Podcast", mappedBy="fandom") */
    protected $podcasts;

    /** @OneToMany(targetEntity="Convention", mappedBy="fandom") */
    protected $conventions;

    /** @OneToMany(targetEntity="NetworkNews", mappedBy="fandom") */
    protected $news;

    /**
     * Return the first record in this set (by ID) or null if no records exist.
     *
     * @return Fandom|null
     */
    public static function fetchDefault()
    {
        static $record;
        if ($record instanceof self)
            return $record;

        try
        {
            $em = self::getEntityManager();
            $record = $em->createQuery('SELECT f FROM ' . __CLASS__ . ' f ORDER BY f.id ASC')
                ->setMaxResults(1)
                ->getOneOrNullResult();

            return $record;
        }
        catch(\Exception $e)
        {
            return NULL;
        }
    }
}