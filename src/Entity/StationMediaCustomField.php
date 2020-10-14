<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="station_media_custom_field")
 * @ORM\Entity
 */
class StationMediaCustomField
{
    use Traits\TruncateStrings;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(name="media_id", type="integer")
     * @var int
     */
    protected $media_id;

    /**
     * @ORM\ManyToOne(targetEntity="StationMedia", inversedBy="metadata")
     * @ORM\JoinColumn(name="media_id", referencedColumnName="id", onDelete="CASCADE")
     * @var StationMedia
     */
    protected $media;

    /**
     * @ORM\Column(name="field_id", type="integer")
     * @var int
     */
    protected $field_id;

    /**
     * @ORM\ManyToOne(targetEntity="CustomField", inversedBy="media_fields")
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id", onDelete="CASCADE")
     * @var CustomField
     */
    protected $field;

    /**
     * @ORM\Column(name="field_value", type="string", length=255, nullable=true)
     * @var string
     */
    protected $value;

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
        $this->value = $this->truncateString($value);
    }
}
