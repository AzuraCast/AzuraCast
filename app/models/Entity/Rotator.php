<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="rotators")
 * @Entity
 */
class Rotator extends \DF\Doctrine\Entity
{
    public function __construct()
    {
        $this->timestamp = time();
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="description", type="text", nullable=true) */
    protected $description;

    /** @Column(name="image_url", type="string", length=255, nullable=true) */
    protected $image_url;

    /** @Column(name="web_url", type="string", length=255, nullable=true) */
    protected $web_url;

    /** @Column(name="timestamp", type="integer") */
    protected $timestamp;

    /**
     * Static Functions
     */

    public static function fetch()
    {
        $records = self::fetchArray();
        shuffle($records);

        return $records;
    }
}