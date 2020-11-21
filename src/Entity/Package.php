<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;
use App\Radio\Adapters;

/**
 * @ORM\Table(name="packages", indexes={
 *     @ORM\Index(name="idx_name", columns={"name"})
 * })
 * @ORM\Entity()
 * 
 * @OA\Schema(type="object", schema="Package")
 */
class Package
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @OA\Property(example=1)
     * @var int|null
     */
    protected $id;

    /**
     * @ORM\Column(name="name", type="string", length=100, nullable=false, unique=true)
     *
     * @OA\Property(example="AzuraTest Radio")
     *
     * @Assert\NotBlank()
     * @var string|null The full display name of the station.
     */
    protected $name;

    /**
     * @ORM\Column(name="is_enabled", type="boolean", nullable=false)
     *
     * @OA\Property(example=true)
     * @var bool If set to "false", prevents the package from being used for new signups but leaves it in the database.
     */
    protected $is_enabled = true;

    /**
     * @ORM\Column(name="bitrate", type="integer", nullable=false)
     * 
     * @var int returns the bitrate that the station belonging to this package will use
     */
    protected $bitrate = 328;

    /**
     * @ORM\Column(name="max_listeners", type="integer", nullable=false)
     * 
     * @var int returns the max listeners for this package, 0 or null = unlimited.
     */
    protected $max_listeners = 0;

     /**
     * @ORM\Column(name="frontend_type", type="string", length=100, nullable=false)
     *
     * @OA\Property(example="icecast")
     *
     * @Assert\Choice(choices={Adapters::FRONTEND_ICECAST, Adapters::FRONTEND_SHOUTCAST})
     * @var string The frontend adapter (icecast,shoutcast)
     */
    protected $frontend_type = Adapters::FRONTEND_ICECAST;

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Gets the name of the package
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the name of the package
     * 
     */
    public function setName(string $name): void 
    {
        $this->name = $name;
    }

    /**
     * Returns true if package is enabled, false otherwise
     */
    public function isEnabled(): bool
    {
        return $this->is_enabled;
    }

    /**
     * Sets if the package is enabled or disabled.
     */
    public function setIsEnabled(bool $is_enabled): void
    {
        $this->is_enabled = $is_enabled;
    }

    /**
     * Gets the bitrate for the package
     */
    public function getBitrate(): int 
    {
        return $this->bitrate;
    }

    /**
     * Sets the bitrate for the package
     */
    public function setBitrate(int $bitrate): void
    {
        $this->bitrate = $bitrate;
    }

    /**
     * Gets the max listeners for the package
     */
    public function getMaxListeners(): int
    {
        return $this->max_listeners;
    }

    /**
     * Sets the max number of listenrs for the package
     */
    public function setMaxListeners(int $max_listeners): void
    {
        $this->max_listeners = $max_listeners;
    }

    /**
     * Gets the frontend for this package
     */
    public function getFrontendType(): string
    {
        return $this->frontend_type;
    }

    public function setFrontendType(string $frontend_type): void
    {
        if (!in_array($frontend_type, array(Adapters::FRONTEND_ICECAST, Adapters::FRONTEND_SHOUTCAST))) {
            throw new \InvalidArgumentException("Invalid frontend.");
        }

        $this->frontend_type = $frontend_type;
    }

}
