<?php

declare(strict_types=1);

namespace App\Entity\Traits;

use App\Entity\Interfaces\EntityGroupsInterface;
use App\Entity\Interfaces\SongInterface;
use App\Entity\Song;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute as Serializer;

#[OA\Schema(type: 'object')]
trait HasSongFields
{
    use TruncateStrings;

    #[
        OA\Property,
        ORM\Column(length: 50),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public protected(set) string $song_id;

    #[
        OA\Property,
        ORM\Column(length: 512, nullable: true),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public ?string $text = null {
        get => $this->text;
        set => $this->truncateNullableString($value, 512);
    }

    #[
        OA\Property,
        ORM\Column(length: 255, nullable: true),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public ?string $artist = null {
        get => $this->artist;
        set => $this->truncateNullableString($value);
    }

    #[
        OA\Property,
        ORM\Column(length: 255, nullable: true),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public ?string $title = null {
        get => $this->title;
        set => $this->truncateNullableString($value);
    }

    #[
        OA\Property,
        ORM\Column(length: 200, nullable: true),
        Serializer\Groups([EntityGroupsInterface::GROUP_GENERAL, EntityGroupsInterface::GROUP_ALL])
    ]
    public ?string $album = null {
        get => $this->album;
        set => $this->truncateNullableString($value, 200);
    }

    public function setSong(SongInterface $song): void
    {
        $this->title = $song->title;
        $this->artist = $song->artist;
        $this->album = $song->album;
        $this->text = $song->text;
        $this->updateMetaFields();
    }

    public function updateMetaFields(string $separator = ' - '): void
    {
        // Force setting the text field if it's not otherwise set.
        if (null === $this->text) {
            $textParts = [
                trim($this->artist ?? ''),
                trim($this->album ?? ''),
                trim($this->title ?? ''),
            ];

            $this->text = implode($separator, array_filter($textParts));
        }

        $this->song_id = !empty($this->text)
            ? Song::getSongHash($this->text)
            : Song::OFFLINE_SONG_ID;
    }
}
