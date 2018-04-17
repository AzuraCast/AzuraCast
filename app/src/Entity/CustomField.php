<?php

namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @Table(name="custom_field")
 * @Entity
 */
class CustomField
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
     * @Column(name="name", type="string", length=255)
     * @var string
     */
    protected $name;

    /**
     * @Column(name="short_name", type="string", length=100, nullable=true)
     * @var string|null The programmatic name for the field. Can be auto-generated from the full name.
     */
    protected $short_name;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $this->_truncateString($name, 100);

        if (empty($this->short_name) && !empty($name)) {
            $this->setShortName(Station::getStationShortName($name));
        }
    }

    /**
     * @return null|string
     */
    public function getShortName(): ?string
    {
        return (!empty($this->short_name))
            ? $this->short_name
            : Station::getStationShortName($this->name);
    }

    /**
     * @param null|string $short_name
     */
    public function setShortName(?string $short_name): void
    {
        $short_name = trim($short_name);
        if (!empty($short_name)) {
            $this->short_name = $this->_truncateString($short_name, 100);
        }
    }
}