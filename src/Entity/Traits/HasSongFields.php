<?php

namespace App\Entity\Traits;

use App\Entity\Interfaces\SongInterface;
use App\Entity\Song;
use Doctrine\ORM\Mapping as ORM;

/**
 * @OA\Schema(type="object")
 */
trait HasSongFields
{
    use TruncateStrings;

    /** @OA\Property() */
    #[ORM\Column(length: 50)]
    protected string $song_id;

    /** @OA\Property() */
    #[ORM\Column(length: 303)]
    protected ?string $text = null;

    /** @OA\Property() */
    #[ORM\Column(length: 150)]
    protected ?string $artist = null;

    /** @OA\Property() */
    #[ORM\Column(length: 150)]
    protected ?string $title = null;

    public function setSong(SongInterface $song): void
    {
        $this->title = $this->truncateNullableString($song->getTitle(), 303);
        $this->artist = $this->truncateNullableString($song->getArtist(), 150);
        $this->text = $this->truncateNullableString($song->getText(), 150);

        // Force setting the text field if it's not otherwise set.
        $this->setText($this->getText());
        $this->updateSongId();
    }

    public function getSongId(): string
    {
        return $this->song_id;
    }

    public function updateSongId(): void
    {
        $this->song_id = Song::getSongHash($this->getText());
    }

    public function getText(): ?string
    {
        return $this->text ?? $this->artist . ' - ' . $this->title;
    }

    protected function setTextFromArtistAndTitle(string $separator = ' - '): void
    {
        $this->setText($this->artist . $separator . $this->title);
    }

    public function setText(?string $text): void
    {
        $oldText = $this->text;
        $this->text = $this->truncateNullableString($text, 303);

        if (0 !== strcmp($oldText, $this->text)) {
            $this->updateSongId();
        }
    }

    public function getArtist(): ?string
    {
        return $this->artist;
    }

    public function setArtist(?string $artist): void
    {
        $oldArtist = $this->artist;
        $this->artist = $this->truncateNullableString($artist, 150);

        if (0 !== strcmp($oldArtist, $this->artist)) {
            $this->setTextFromArtistAndTitle();
        }
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $oldTitle = $this->title;
        $this->title = $this->truncateNullableString($title, 150);

        if (0 !== strcmp($oldTitle, $this->title)) {
            $this->setTextFromArtistAndTitle();
        }
    }
}
