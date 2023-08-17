<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\SongInterface;
use InvalidArgumentException;
use NowPlaying\Result\CurrentSong;

class Song implements SongInterface
{
    use Traits\HasSongFields;

    public final const OFFLINE_SONG_ID = '5a6a865199cf5df73b1417326d2ff24f';

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

    public static function getSongHash(Song|array|string|CurrentSong $songText): string
    {
        // Handle various input types.
        if ($songText instanceof self) {
            return self::getSongHash($songText->getText() ?? '');
        }
        if ($songText instanceof CurrentSong) {
            return self::getSongHash($songText->text);
        }
        if (is_array($songText)) {
            return self::getSongHash($songText['text'] ?? '');
        }

        if (!is_string($songText)) {
            throw new InvalidArgumentException(
                sprintf(
                    '$songText parameter must be a string, array, or instance of %s or %s.',
                    self::class,
                    CurrentSong::class
                )
            );
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
        $song->setText($apiSong->text);
        $song->setTitle($apiSong->title);
        $song->setArtist($apiSong->artist);
        $song->updateSongId();

        return $song;
    }

    public static function createFromNowPlayingSong(CurrentSong $currentSong): self
    {
        $song = new self();
        $song->setText($currentSong->text);
        $song->setTitle($currentSong->title);
        $song->setArtist($currentSong->artist);
        $song->updateSongId();

        return $song;
    }

    public static function createFromArray(array $songRow): self
    {
        $currentSong = new CurrentSong(
            $songRow['text'] ?? '',
            $songRow['title'] ?? '',
            $songRow['artist'] ?? ''
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
        $song->setSongId(self::OFFLINE_SONG_ID);
        return $song;
    }
}
