<?php
namespace App\Entity;

use App\ApiUtilities;
use InvalidArgumentException;
use NowPlaying\Result\CurrentSong;
use Psr\Http\Message\UriInterface;

class Song implements SongInterface
{
    use Traits\HasSongFields;

    public function __construct(?SongInterface $song = null)
    {
        if (null !== $song) {
            $this->setSong($song);
        }
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
    public function api(
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
            throw new InvalidArgumentException('$songText parameter must be a string, array, or instance of ' . self::class . ' or ' . CurrentSong::class . '.');
        }

        // Strip non-alphanumeric characters
        $song_text = mb_substr($songText, 0, 150, 'UTF-8');
        $hash_base = mb_strtolower(str_replace([' ', '-'], ['', ''], $song_text), 'UTF-8');

        return md5($hash_base);
    }

    public static function createFromApiSong(Api\Song $apiSong): self
    {
        $song = new self;
        $song->setText($apiSong->text);
        $song->setTitle($apiSong->title);
        $song->setArtist($apiSong->artist);
        $song->updateSongId();

        return $song;
    }

    public static function createFromNowPlayingSong(CurrentSong $currentSong): self
    {
        $song = new self;
        $song->setText($currentSong->text);
        $song->setTitle($currentSong->title);
        $song->setArtist($currentSong->artist);
        $song->updateSongId();

        return $song;
    }

    public static function createFromArray(array $songRow): self
    {
        $currentSong = new CurrentSong(
            $songRow['text'] ?? null,
            $songRow['title'] ?? null,
            $songRow['artist'] ?? null
        );
        return self::createFromNowPlayingSong($currentSong);
    }

    public static function createFromText(string $songText): self
    {
        $currentSong = new CurrentSong($songText);
        return self::createFromNowPlayingSong($currentSong);
    }
}
