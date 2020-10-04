<?php
namespace App\Entity;

use App\ApiUtilities;
use Doctrine\ORM\Mapping as ORM;
use NowPlaying\Result\CurrentSong;
use Psr\Http\Message\UriInterface;

class Song
{
    use Traits\TruncateStrings;

    /**
     * @ORM\Column(name="song_id", type="string", length=50)
     * @var string
     */
    protected $song_id;

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
     * @param self|Api\Song|CurrentSong|array|string|null $song
     */
    public function __construct($song)
    {
        if (null !== $song) {
            $this->setSong($song);
        }
    }

    /**
     * @param self|Api\Song|CurrentSong|array|string $song
     */
    public function setSong($song): void
    {
        if ($song instanceof self) {
            $this->setText($song->getText());
            $this->setTitle($song->getTitle());
            $this->setArtist($song->getArtist());
            $this->song_id = $song->getSongId();
            return;
        }

        if ($song instanceof Api\Song) {
            $this->setText($song->text);
            $this->setTitle($song->title);
            $this->setArtist($song->artist);
            $this->song_id = $song->id;
            return;
        }

        if (is_array($song)) {
            $song = new CurrentSong(
                $song['text'] ?? null,
                $song['title'] ?? null,
                $song['artist'] ?? null
            );
        } elseif (is_string($song)) {
            $song = new CurrentSong($song);
        }

        if ($song instanceof CurrentSong) {
            $this->setText($song->text);
            $this->setTitle($song->title);
            $this->setArtist($song->artist);
            $this->updateSongId();
            return;
        }

        throw new \InvalidArgumentException('$song must be an array or an instance of ' . CurrentSong::class . '.');
    }

    public function getSong(): self
    {
        return new self($this);
    }

    public function getSongId(): string
    {
        return $this->song_id;
    }

    public function updateSongId(): void
    {
        $this->song_id = self::getSongHash($this->getText());
    }

    public function getText(): ?string
    {
        return $this->text ?? $this->artist . ' - ' . $this->title;
    }

    public function setText(?string $text): void
    {
        $this->text = $this->truncateString($text, 150);
    }

    public function getArtist(): ?string
    {
        return $this->artist;
    }

    public function setArtist(?string $artist): void
    {
        $this->artist = $this->truncateString($artist, 150);
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $this->truncateString($title, 150);
    }

    public function __toString(): string
    {
        return 'Song ' . $this->song_id . ': ' . $this->artist . ' - ' . $this->title;
    }

    /**
     * Retrieve the API version of the object/array.
     *
     * @param ApiUtilities $api_utils
     * @param Station|null $station
     * @param UriInterface|null $base_url
     *
     * @return Api\Song
     */
    public function getSongApi(
        ApiUtilities $api_utils,
        ?Station $station = null,
        ?UriInterface $base_url = null
    ): Api\Song {
        $response = new Api\Song;
        $response->id = (string)$this->song_id;
        $response->text = (string)$this->text;
        $response->artist = (string)$this->artist;
        $response->title = (string)$this->title;
        $response->art = $api_utils->getDefaultAlbumArtUrl($station, $base_url);

        $response->custom_fields = $api_utils->getCustomFields();

        return $response;
    }

    /**
     * @param array|CurrentSong|self|string $songText
     *
     * @return string
     */
    public static function getSongHash($songText): string
    {
        // Handle various input types.
        if ($songText instanceof self) {
            return self::getSongHash($songText->getText());
        }
        if ($songText instanceof CurrentSong) {
            return self::getSongHash($songText->text);
        }
        if (is_array($songText)) {
            return self::getSongHash($songText['text'] ?? '');
        }

        if (!is_string($songText)) {
            throw new \InvalidArgumentException('$songText parameter must be a string, array, or instance of ' . self::class . ' or ' . CurrentSong::class . '.');
        }

        // Strip non-alphanumeric characters
        $song_text = mb_substr($songText, 0, 150, 'UTF-8');
        $hash_base = mb_strtolower(str_replace([' ', '-'], ['', ''], $song_text), 'UTF-8');

        return md5($hash_base);
    }
}
