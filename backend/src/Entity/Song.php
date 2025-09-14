<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\SongInterface;
use NowPlaying\Result\CurrentSong;

final class Song implements SongInterface
{
    use Traits\HasSongFields;

    public final const string OFFLINE_SONG_ID = '5a6a865199cf5df73b1417326d2ff24f';

    public function __construct(?SongInterface $song = null)
    {
        if (null !== $song) {
            $this->setSong($song);
        }
    }

    public function __toString(): string
    {
        return 'Song ' . $this->text;
    }

    public static function getSongHash(Song|array|string|CurrentSong $songText): string
    {
        // Handle various input types.
        if ($songText instanceof self) {
            return self::getSongHash($songText->text ?? '');
        }
        if ($songText instanceof CurrentSong) {
            return self::getSongHash($songText->text);
        }
        if (is_array($songText)) {
            return self::getSongHash($songText['text'] ?? '');
        }

        $songText = mb_substr($songText, 0, 150, 'UTF-8');

        // Strip out characters that are likely to not be properly translated or relayed through the radio.
        $removeChars = [
            ' ',
            '-',
            '"',
            '\'',
            "\n",
            "\t",
            "\r",
        ];

        $songText = str_replace($removeChars, '', $songText);

        if (empty($songText)) {
            return self::OFFLINE_SONG_ID;
        }

        $hashBase = mb_strtolower($songText, 'UTF-8');
        return md5($hashBase);
    }

    public static function createFromApiSong(Api\Song $apiSong): self
    {
        $song = new self();
        $song->title = $apiSong->title;
        $song->artist = $apiSong->artist;
        $song->album = $apiSong->album;
        $song->text = $apiSong->text;
        $song->updateMetaFields();

        return $song;
    }

    public static function createFromNowPlayingSong(CurrentSong $currentSong): self
    {
        $song = new self();
        $song->title = $currentSong->title;
        $song->artist = $currentSong->artist;
        $song->album = $currentSong->album;
        $song->text = $currentSong->text;
        $song->updateMetaFields();

        return $song;
    }

    public static function createFromArray(array $songRow): self
    {
        $currentSong = new CurrentSong(
            $songRow['text'] ?? '',
            $songRow['title'] ?? '',
            $songRow['artist'] ?? '',
            $songRow['album'] ?? '',
        );
        return self::createFromNowPlayingSong($currentSong);
    }

    public static function createFromText(string $songText): self
    {
        $currentSong = new CurrentSong($songText);
        return self::createFromNowPlayingSong($currentSong);
    }

    public static function createOffline(?string $text = null): self
    {
        $song = self::createFromText($text ?? 'Station Offline');
        $song->song_id = self::OFFLINE_SONG_ID;
        return $song;
    }
}
