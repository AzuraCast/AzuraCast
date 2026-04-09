<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enums\ClockwheelRequestMode;
use App\Entity\Interfaces\IdentifiableEntityInterface;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[
    OA\Schema(type: 'object'),
    ORM\Entity,
    ORM\Table(name: 'station_playlist_child'),
    ORM\UniqueConstraint(name: 'idx_parent_position', columns: ['parent_playlist_id', 'position'])
]
final class StationPlaylistChild implements IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;

    #[ORM\ManyToOne(fetch: 'EAGER', inversedBy: 'child_items')]
    #[ORM\JoinColumn(name: 'parent_playlist_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public readonly StationPlaylist $parentPlaylist;

    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    public private(set) int $parent_playlist_id;

    #[ORM\ManyToOne(fetch: 'EAGER', inversedBy: 'parent_items')]
    #[ORM\JoinColumn(name: 'child_playlist_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    public readonly ?StationPlaylist $childPlaylist;

    #[ORM\Column(nullable: true, insertable: false, updatable: false)]
    public private(set) ?int $child_playlist_id;

    #[
        OA\Property(description: 'Position in the clockwheel sequence (0-based).'),
        ORM\Column(type: 'smallint'),
        Assert\PositiveOrZero
    ]
    public int $position = 0;

    #[
        OA\Property(description: 'Number of songs to play from this child playlist before advancing.', example: 1),
        ORM\Column(type: 'smallint'),
        Assert\Positive
    ]
    public int $song_count = 1;

    #[
        OA\Property(description: 'Request handling mode for this step.'),
        ORM\Column(type: 'string', length: 20, enumType: ClockwheelRequestMode::class)
    ]
    public ClockwheelRequestMode $request_mode = ClockwheelRequestMode::None;

    public function isRequestSlot(): bool
    {
        return null === $this->childPlaylist;
    }

    public function __construct(
        StationPlaylist $parentPlaylist,
        ?StationPlaylist $childPlaylist,
        int $position = 0,
        int $songCount = 1,
        ClockwheelRequestMode $requestMode = ClockwheelRequestMode::None
    ) {
        $this->parentPlaylist = $parentPlaylist;
        $this->childPlaylist = $childPlaylist;
        $this->position = $position;
        $this->song_count = max(1, $songCount);
        $this->request_mode = $requestMode;
    }
}
