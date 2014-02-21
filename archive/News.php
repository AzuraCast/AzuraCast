<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="news")
 * @Entity
 */
class News extends \DF\Doctrine\Entity
{
    public function __construct()
    {
        $this->is_featured = false;
        $this->is_approved = true;
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="guid", type="string", length=128, nullable=true) */
    protected $guid;

    /** @Column(name="type", type="string", length=40, nullable=true) */
    protected $type;

    /** @Column(name="source", type="string", length=40, nullable=true) */
    protected $source;

    /** @Column(name="is_featured", type="boolean", nullable=true) */
    protected $is_featured;

    /** @Column(name="is_approved", type="boolean", nullable=true) */
    protected $is_approved;

    public function getTypeName()
    {
        $type_names = self::getTypeNames();
        return $type_names[$this->type];
    }

    /** @Column(name="author_id", type="integer", nullable=true) */
    protected $author_id;

    /** @Column(name="timestamp", type="integer", nullable=true) */
    protected $timestamp;

    /** @Column(name="title", type="string", length=400, nullable=true) */
    protected $title;

    /** @Column(name="author", type="string", length=100, nullable=true) */
    protected $author;

    /** @Column(name="body", type="text", nullable=true) */
    protected $body;

    /** @Column(name="image_url", type="string", length=100, nullable=true) */
    protected $image_url;

    /** @Column(name="web_url", type="string", length=100, nullable=true) */
    protected $web_url;

    /**
     * Static Functions
     */

    public static function getTypeNames()
    {
        return array(
            'pvl'       => 'Network News',
            'station'   => 'Station News',
            'artist'    => 'Artist Updates',
        );
    }
}