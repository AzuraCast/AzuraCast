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
        $this->signup_enabled = true;
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="name", type="string", length=400, nullable=true) */
    protected $name;

    /** @Column(name="location", type="string", length=400, nullable=true) */
    protected $location;

    /** @Column(name="start_date", type="date") */
    protected $start_date;

    /** @Column(name="end_date", type="date") */
    protected $end_date;

    public function getRange()
    {
        return Schedule::getRangeText($this->start_date->getTimestamp(), $this->end_date->getTimestamp(), TRUE);
    }

    /** @Column(name="web_url", type="string", length=250, nullable=true) */
    protected $web_url;

    /** @Column(name="image_url", type="string", length=150, nullable=true) */
    protected $image_url;

    public function setImageUrl($new_url)
    {
        if ($new_url)
        {
            if ($this->image_url && $this->image_url != $new_url)
                @unlink(DF_UPLOAD_FOLDER.DIRECTORY_SEPARATOR.$this->image_url);

            $new_path = DF_UPLOAD_FOLDER.DIRECTORY_SEPARATOR.$new_url;
            \DF\Image::resizeImage($new_path, $new_path, 1150, 200);

            $this->image_url = $new_url;
        }
    }

    /** @Column(name="signup_notes", type="text", nullable=true) */
    protected $signup_notes;

    /** @Column(name="signup_enabled", type="boolean") */
    protected $signup_enabled;

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

        return $conventions;
    }
}