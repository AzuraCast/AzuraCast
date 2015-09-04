<?php
namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use PVL\Utilities;

/**
 * @Table(name="song_submissions")
 * @Entity
 */
class SongSubmission extends \DF\Doctrine\Entity
{
    use Traits\FileUploads;

    public function __construct()
    {
        $this->created = time();
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /** @Column(name="hash", type="string", length=50) */
    protected $hash;

    /** @Column(name="user_id", type="integer", nullable=true) */
    protected $user_id;

    /** @Column(name="created_timestamp", type="integer") */
    protected $created;

    /** @Column(name="title", type="string", length=150, nullable=true) */
    protected $title;

    /** @Column(name="artist", type="string", length=150, nullable=true) */
    protected $artist;

    /** @Column(name="song_url", type="string", length=255, nullable=true) */
    protected $song_url;

    public function setSongUrl($new_song_url)
    {
        if ($this->_processFile('song_url', $new_song_url))
        {
            $this->song_url = $new_song_url;
        }
    }

    /** @Column(name="song_metadata", type="json", nullable=true) */
    protected $song_metadata;

    /** @Column(name="stations", type="json", nullable=true) */
    protected $stations;

    /**
     * @ManyToOne(targetEntity="Song")
     * @JoinColumns({ @JoinColumn(name="hash", referencedColumnName="id", onDelete="CASCADE") })
     */
    protected $song;

    /**
     * @ManyToOne(targetEntity="User")
     * @JoinColumns({ @JoinColumn(name="user_id", referencedColumnName="uid", onDelete="CASCADE") })
     */
    protected $user;
}