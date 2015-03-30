<?php
namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use PVL\Utilities;

/**
 * @Table(name="song_external_pony_fm", indexes={
 *   @index(name="search_idx", columns={"hash"}),
 *   @index(name="sort_idx", columns={"timestamp"}),
 * })
 * @Entity
 */
class SongExternalPonyFm extends \DF\Doctrine\Entity
{
    use Traits\ExternalSongs;

    public function __construct()
    {
        $this->created = time();
        $this->updated = time();
    }

    const SYNC_THRESHOLD = 2592000; // 2592000 = 30 days, 86400 = 1 day

    /**
     * @Column(name="id", type="integer")
     * @Id
     */
    protected $id;

    /** @Column(name="hash", type="string", length=50) */
    protected $hash;

    /**
     * @OneToOne(targetEntity="Song", inversedBy="external_ponyfm")
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

    /** @Column(name="description", type="text", nullable=true) */
    protected $description;

    /** @Column(name="lyrics", type="text", nullable=true) */
    protected $lyrics;

    /** @Column(name="web_url", type="string", length=255, nullable=true) */
    protected $web_url;

    /** @Column(name="image_url", type="string", length=255, nullable=true) */
    protected $image_url;

    /** @Column(name="download_url", type="string", length=255, nullable=true) */
    protected $download_url;

    /** @Column(name="is_vocal", type="boolean") */
    protected $is_vocal;

    /** @Column(name="is_explicit", type="boolean") */
    protected $is_explicit;

    /**
     * Static Functions
     */

    public static function processRemote($result)
    {
        return array(
            'id'        => $result['id'],
            'created'   => Utilities::gstrtotime($result['published_at']['date']),
            'updated'   => time(),
            'artist'    => $result['user']['name'],
            'title'     => $result['title'],
            'description' => $result['description'],
            'lyrics'    => $result['lyrics'],
            'web_url'   => $result['url'],
            'image_url' => $result['covers']['normal'],
            'download_url' => str_replace('stream.mp3', 'dl.mp3', $result['streams']['mp3']),
            'is_vocal'  => (int)$result['is_vocal'],
            'is_explicit' => (int)$result['is_explicit'],
        );
    }
}