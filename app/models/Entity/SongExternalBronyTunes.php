<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="song_external_bronytunes", indexes={
 *   @index(name="search_idx", columns={"hash"}),
 *   @index(name="sort_idx", columns={"timestamp"}),
 * })
 * @Entity
 */
class SongExternalBronyTunes extends \DF\Doctrine\Entity
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
     * @OneToOne(targetEntity="Song", inversedBy="external_bronytunes")
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

    /** @Column(name="album", type="string", length=150, nullable=true) */
    protected $album;

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

    /** @Column(name="youtube_url", type="string", length=255, nullable=true) */
    protected $youtube_url;

    /** @Column(name="purchase_url", type="string", length=255, nullable=true) */
    protected $purchase_url;

    /**
     * Static Functions
     */

    public static function processRemote($result)
    {
        return array(
            'id'        => $result['song_id'],
            'updated'   => time(),
            'artist'    => $result['artist_name'],
            'title'     => $result['name'],
            'album'     => $result['album'],
            'description' => $result['description'],
            'lyrics'    => $result['lyrics'],
            'web_url'   => 'http://bronytunes.com/songs/'.$result['song_id'],
            'image_url' => 'http://bronytunes.com/retrieve_artwork.php?song_id='.$result['song_id'].'&size=256',
            'download_url' => 'https://bronytunes.com/retrieve_song.php?song_id='.$result['song_id'].'&client_type=download',
            'youtube_url' => ($result['youtube_id']) ? 'http://youtu.be/'.$result['youtube_id'] : '',
            'purchase_url' => $result['purchase_link'],
        );
    }
}