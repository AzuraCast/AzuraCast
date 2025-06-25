<?php

declare(strict_types=1);

namespace App\Entity\Traits;

use App\Entity\Interfaces\EntityGroupsInterface;
use App\Entity\Interfaces\SongInterface;
use App\Entity\Song;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Annotation\Groups;

#[OA\Schema(type: 'object')]
trait HasSongFields
{
    use TruncateStrings;

    #[
        OA\Property,
        ORM\Column(length: 50),
        Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected string $song_id;

    #[
        OA\Property,
        ORM\Column(length: 512, nullable: true),
        Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected ?string $text = null;

    #[
        OA\Property,
        ORM\Column(length: 255, nullable: true),
        Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected ?string $artist = null;

    #[
        OA\Property,
        ORM\Column(length: 255, nullable: true),
        Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected ?string $title = null;

    #[
        OA\Property,
        ORM\Column(length: 200, nullable: true),
        Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    protected ?string $album = null;

    public function setSong(SongInterface $song): void
    {
        $this->title = $this->truncateNullableString($song->getTitle(), 512);
        $this->artist = $this->truncateNullableString($song->getArtist());
        $this->text = $this->truncateNullableString($song->getText());
        $this->album = $this->truncateNullableString($song->getAlbum(), 200);

        // Force setting the text field if it's not otherwise set.
        $this->setText($this->getText());
        $this->updateSongId();
    }

    public function getSongId(): string
    {
        return $this->song_id;
    }

    protected function setSongId(string $songId): void
    {
        $this->song_id = $songId;
    }

    public function updateSongId(): void
    {
        $text = $this->getText();
        $this->song_id = !empty($text)
            ? Song::getSongHash($text)
            : Song::OFFLINE_SONG_ID;
    }

    public function getText(): ?string
    {
        if (null === $this->text) {
            $this->setTextFromOtherFields();
        }

        return $this->text;
    }

    protected function setTextFromOtherFields(string $separator = ' - '): void
    {
        $textParts = [
            trim($this->artist ?? ''),
            trim($this->album ?? ''),
            trim($this->title ?? ''),
        ];

        $this->setText(implode($separator, array_filter($textParts)));
    }

    public function setText(?string $text): void
    {
        $oldText = $this->text;
        $this->text = $this->truncateNullableString($text, 512);

        if (0 !== strcmp($oldText ?? '', $this->text ?? '')) {
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
        $this->artist = $this->truncateNullableString($artist);

        if (0 !== strcmp($oldArtist ?? '', $this->artist ?? '')) {
            $this->setTextFromOtherFields();
        }
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $oldTitle = $this->title;
        $this->title = $this->truncateNullableString($title);

        if (0 !== strcmp($oldTitle ?? '', $this->title ?? '')) {
            $this->setTextFromOtherFields();
        }
    }

    public function getAlbum(): ?string
    {
        return $this->album;
    }

    public function setAlbum(?string $album): void
    {
        $oldAlbum = $this->album;
        $this->album = $this->truncateNullableString($album);

        if (0 !== strcmp($oldAlbum ?? '', $this->album ?? '')) {
            $this->setTextFromOtherFields();
        }
    }
}
