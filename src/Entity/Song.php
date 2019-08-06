<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Psr\Http\Message\UriInterface;

/**
 * @ORM\Table(name="songs", indexes={
 *   @ORM\Index(name="search_idx", columns={"text", "artist", "title"})
 * })
 * @ORM\Entity(repositoryClass="App\Entity\Repository\SongRepository")
 */
class Song
{
    use Traits\TruncateStrings;

    public const SYNC_THRESHOLD = 604800; // 604800 = 1 week

    /**
     * @ORM\Column(name="id", type="string", length=50)
     * @ORM\Id
     * @var string
     */
    protected $id;

    /**
     * @ORM\Column(name="text", type="string", length=150, nullable=true)
     * @var string|null
     */
    protected $text;

    /**
     * @ORM\Column(name="artist", type="string", length=150, nullable=true)
     * @var string|null
     */
    protected $artist;

    /**
     * @ORM\Column(name="title", type="string", length=150, nullable=true)
     * @var string|null
     */
    protected $title;

    /**
     * @ORM\Column(name="created", type="integer")
     * @var int
     */
    protected $created;

    /**
     * @ORM\Column(name="play_count", type="integer")
     * @var int
     */
    protected $play_count = 0;

    /**
     * @ORM\Column(name="last_played", type="integer")
     * @var int
     */
    protected $last_played = 0;

    /**
     * @ORM\OneToMany(targetEntity="SongHistory", mappedBy="song")
     * @ORM\OrderBy({"timestamp" = "DESC"})
     * @var Collection
     */
    protected $history;

    /**
     * Song constructor.
     * @param array $song_info
     */
    public function __construct(array $song_info)
    {

        $this->created = time();
        $this->history = new ArrayCollection;
        $this->update($song_info);
    }

    /**
     * Given an array of song information possibly containing artist, title, text
     * or any combination of those, update this entity to reflect this metadata.
     *
     * @param array $song_info
     */
    public function update(array $song_info): void
    {
        if (empty($song_info['text'])) {
            if (!empty($song_info['artist'])) {
                $song_info['text'] = $song_info['artist'] . ' - ' . $song_info['title'];
            } else {
                $song_info['text'] = $song_info['title'];
            }
        }

        $this->text = $this->_truncateString($song_info['text'], 150);
        $this->title = $this->_truncateString($song_info['title'], 150);
        $this->artist = $this->_truncateString($song_info['artist'], 150);

        $new_song_hash = self::getSongHash($song_info);

        if (null === $this->id) {
            $this->id = $new_song_hash;
        } else if ($this->id !== $new_song_hash) {
            throw new \Azura\Exception('New song data supplied would not produce the same song ID.');
        }
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * @return string|null
     */
    public function getArtist(): ?string
    {
        return $this->artist;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @return int
     */
    public function getCreated(): int
    {
        return $this->created;
    }

    /**
     * @return int
     */
    public function getPlayCount(): int
    {
        return $this->play_count;
    }

    /**
     * @return int
     */
    public function getLastPlayed(): int
    {
        return $this->last_played;
    }

    /**
     * Increment the play counter and last-played items.
     */
    public function played(): void
    {
        ++$this->play_count;
        $this->last_played = time();
    }

    /**
     * @return Collection
     */
    public function getHistory(): Collection
    {
        return $this->history;
    }

    /**
     * Retrieve the API version of the object/array.
     *
     * @param \App\ApiUtilities $api_utils
     * @param UriInterface|null $base_url
     * @return Api\Song
     */
    public function api(\App\ApiUtilities $api_utils, UriInterface $base_url = null): Api\Song
    {
        $response = new Api\Song;
        $response->id = (string)$this->id;
        $response->text = (string)$this->text;
        $response->artist = (string)$this->artist;
        $response->title = (string)$this->title;
        $response->art = $api_utils->getDefaultAlbumArtUrl($base_url);

        $response->custom_fields = $api_utils->getCustomFields();

        return $response;
    }

    /**
     * @param array|object|string $song_info
     * @return string
     */
    public static function getSongHash($song_info): string
    {
        // Handle various input types.
        if ($song_info instanceof self) {
            $song_info = [
                'text' => $song_info->getText(),
                'artist' => $song_info->getArtist(),
                'title' => $song_info->getTitle(),
            ];
        } elseif (!is_array($song_info)) {
            $song_info = [
                'text' => $song_info,
            ];
        }

        // Generate hash.
        if (!empty($song_info['text'])) {
            $song_text = $song_info['text'];
        } elseif (!empty($song_info['artist'])) {
            $song_text = $song_info['artist'] . ' - ' . $song_info['title'];
        } else {
            $song_text = $song_info['title'];
        }

        // Strip non-alphanumeric characters
        $song_text = mb_substr($song_text, 0, 150, 'UTF-8');
        $hash_base = mb_strtolower(str_replace([' ', '-'], ['', ''], $song_text), 'UTF-8');

        return md5($hash_base);
    }
}
