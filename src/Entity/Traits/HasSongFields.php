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

    public function setSong(SongInterface $song): void
    {
        $this->setText($song->getText());
        $this->setTitle($song->getTitle());
        $this->setArtist($song->getArtist());
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
}