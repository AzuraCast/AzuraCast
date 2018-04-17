<?php

namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @Table(name="station_media_custom_field")
 * @Entity
 */
class StationMediaCustomField
{
    use Traits\TruncateStrings;

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     * @var int
     */
    protected $id;

    /**
     * @Column(name="media_id", type="integer")
     * @var int
     */
    protected $media_id;

    /**
     * @ManyToOne(targetEntity="StationMedia", inversedBy="metadata")
     * @JoinColumn(name="media_id", referencedColumnName="id", onDelete="CASCADE")
     * @var StationMedia
     */
    protected $media;

    /**
     * @Column(name="field_id", type="integer")
     * @var int
     */
    protected $field_id;

    /**
     * @ManyToOne(targetEntity="CustomField", inversedBy="media_fields")
     * @JoinColumn(name="field_id", referencedColumnName="id", onDelete="CASCADE")
     * @var CustomField
     */
    protected $field;

    /**
     * @Column(name="field_value", type="string", length=255, nullable=true)
     * @var
     */
    protected $value;

    public function __construct(StationMedia $media, CustomField $field)
    {
        $this->media = $media;
        $this->field = $field;
    }

    /**
     * @return StationMedia
     */
    public function getMedia(): StationMedia
    {
        return $this->media;
    }

    /**
     * @return CustomField
     */
    public function getField(): CustomField
    {
        return $this->field;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = $this->_truncateString($value);
    }
}