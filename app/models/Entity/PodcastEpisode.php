<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="podcast_episodes")
 * @Entity
 */
class PodcastEpisode extends \DF\Doctrine\Entity
{
    public function __construct()
    {
        $this->media_format = 'mixed';

        $this->is_notified = false;
        $this->play_count = 0;
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="podcast_id", type="integer") */
    protected $podcast_id;

    /** @Column(name="guid", type="string", length=128, nullable=true) */
    protected $guid;

    /** @Column(name="timestamp", type="integer") */
    protected $timestamp;

    /** @Column(name="media_format", type="string", length=50, nullable=true) */
    protected $media_format;

    /** @Column(name="title", type="string", length=400, nullable=true) */
    protected $title;

    /** @Column(name="body", type="text", nullable=true) */
    protected $body;

    /** @Column(name="web_url", type="string", length=255, nullable=true) */
    protected $web_url;

    /** @Column(name="is_notified", type="boolean") */
    protected $is_notified;

    /** @Column(name="play_count", type="integer") */
    protected $play_count;

    /**
     * @ManyToOne(targetEntity="Podcast", inversedBy="episodes")
     * @JoinColumns({
     *   @JoinColumn(name="podcast_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $podcast;

    public static function api($row)
    {
        if ($row instanceof self)
            $row = $row->toArray();

        unset($row['podcast_id']);
        unset($row['is_notified']);

        return $row;
    }
}