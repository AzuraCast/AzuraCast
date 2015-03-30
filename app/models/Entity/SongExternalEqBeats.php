<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="song_external_eq_beats", indexes={
 *   @index(name="search_idx", columns={"hash"}),
 *   @index(name="sort_idx", columns={"timestamp"}),
 * })
 * @Entity
 */
class SongExternalEqBeats extends \DF\Doctrine\Entity
{
    use Traits\ExternalSongs;

    public function __construct()
    {
        $this->created = time();
        $this->updated = time();
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     */
    protected $id;

    /** @Column(name="hash", type="string", length=50) */
    protected $hash;

    /**
     * @OneToOne(targetEntity="Song", inversedBy="external_eqbeats")
     * @JoinColumns({ @JoinColumn(name="hash", referencedColumnName="id", onDelete="CASCADE") })
     */
    protected $song;

    /** @Column(name="created_timestamp", type="integer") */
    protected $created;

    /** @Column(name="timestamp", type="integer") */
    protected $updated;

    /** @Column(name="artist", type="string", length=150, nullable=true) */
    protected $artist;

    /** @Column(name="title", type="string", length=150, nullable=true) */
    protected $title;

    /** @Column(name="web_url", type="string", length=255, nullable=true) */
    protected $web_url;

    /** @Column(name="image_url", type="string", length=255, nullable=true) */
    protected $image_url;

    /** @Column(name="download_url", type="string", length=255, nullable=true) */
    protected $download_url;

    /**
     * Static Functions
     */

    public static function processRemote($result)
    {
        return array(
            'id'        => $result['id'],
            'created'   => round($result['timestamp']),
            'updated'   => time(),
            'artist'    => $result['artist']['name'],
            'title'     => $result['title'],
            'web_url'   => $result['link'],
            'image_url' => $result['download']['art'],
            'download_url' => $result['download']['mp3'],
        );
    }
}