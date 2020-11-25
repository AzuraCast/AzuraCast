<?php

namespace App\Entity\Traits;

use App\Entity\Song;
use App\Entity\SongInterface;
use Doctrine\ORM\Mapping as ORM;

trait HasSongFields
{
    use TruncateStrings;

    /**
     * @ORM\Column(name="song_id", type="string", length=50)
     * @var string
     */
    protected $song_id;

    /**
     * @ORM\Column(name="text", type="string", length=303, nullable=true)
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

    public function setSong(SongInterface $song): void
    {
        $this->title = $this->truncateString($song->getTitle(), 303);
        $this->artist = $this->truncateString($song->getArtist(), 150);
        $this->text = $this->truncateString($song->getText(), 150);

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
        $this->text = $this->truncateString($text, 303);

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
        $this->artist = $this->truncateString($artist, 150);

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
        $this->title = $this->truncateString($title, 150);

        if (0 !== strcmp($oldTitle, $this->title)) {
            $this->setTextFromArtistAndTitle();
        }
    }
}
