<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use Doctrine\ORM\Mapping as ORM;

#[
    ORM\Entity,
    ORM\Table(name: 'station_media_custom_field')
]
class StationMediaCustomField implements IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    #[ORM\ManyToOne(inversedBy: 'metadata')]
    #[ORM\JoinColumn(name: 'media_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected StationMedia $media;

    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    protected int $media_id;

    #[ORM\ManyToOne(inversedBy: 'media_fields')]
    #[ORM\JoinColumn(name: 'field_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected CustomField $field;

    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    protected int $field_id;

    #[ORM\Column(name: 'field_value', length: 255, nullable: true)]
    protected ?string $value = null;

    public function __construct(StationMedia $media, CustomField $field)
    {
        $this->media = $media;
        $this->field = $field;
    }

    public function getMedia(): StationMedia
    {
        return $this->media;
    }

    public function getField(): CustomField
    {
        return $this->field;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value = null): void
    {
        $this->value = $this->truncateNullableString($value);
    }
}
