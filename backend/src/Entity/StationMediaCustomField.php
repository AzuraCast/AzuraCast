<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use Doctrine\ORM\Mapping as ORM;

#[
    ORM\Entity,
    ORM\Table(name: 'station_media_custom_field')
]
final class StationMediaCustomField implements IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    #[ORM\ManyToOne(inversedBy: 'custom_fields')]
    #[ORM\JoinColumn(name: 'media_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public readonly StationMedia $media;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    public private(set) int $media_id;

    #[ORM\ManyToOne(inversedBy: 'media_fields')]
    #[ORM\JoinColumn(name: 'field_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public readonly CustomField $field;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    public private(set) int $field_id;

    #[ORM\Column(name: 'field_value', length: 255, nullable: true)]
    public ?string $value = null {
        set => $this->truncateNullableString($value);
    }

    public function __construct(StationMedia $media, CustomField $field)
    {
        $this->media = $media;
        $this->field = $field;
    }
}
