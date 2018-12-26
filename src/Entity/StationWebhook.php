<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="station_webhooks", options={"charset"="utf8mb4", "collate"="utf8mb4_unicode_ci"})
 */
class StationWebhook
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
     * @ORM\Column(name="station_id", type="integer")
     * @var int
     */
    protected $station_id;

    /**
     * @ORM\ManyToOne(targetEntity="Station", inversedBy="webhooks")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @ORM\Column(name="name", type="string", length=100, nullable=true)
     * @var string|null The nickname of the webhook connector.
     */
    protected $name;

    /**
     * @ORM\Column(name="type", type="string", length=100)
     * @var string The type of webhook connector to use.
     */
    protected $type;

    /**
     * @ORM\Column(name="is_enabled", type="boolean")
     * @var bool
     */
    protected $is_enabled;

    /**
     * @ORM\Column(name="triggers", type="json_array", nullable=true)
     * @var array List of events that should trigger the webhook notification.
     */
    protected $triggers;

    /**
     * @ORM\Column(name="config", type="json_array", nullable=true)
     * @var array Detailed webhook configuration (if applicable)
     */
    protected $config;

    public function __construct(Station $station, $type)
    {
        $this->station = $station;
        $this->type = $type;

        $this->is_enabled = true;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Station
     */
    public function getStation(): Station
    {
        return $this->station;
    }

    /**
     * @return null|string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param null|string $name
     */
    public function setName(?string $name): void
    {
        $this->name = $this->_truncateString($name, 100);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->is_enabled;
    }

    /**
     * @return bool
     */
    public function toggleEnabled(): bool
    {
        $this->is_enabled = !$this->is_enabled;
        return $this->is_enabled;
    }

    /**
     * @param bool $is_enabled
     */
    public function setIsEnabled(bool $is_enabled): void
    {
        $this->is_enabled = $is_enabled;
    }

    /**
     * @return array
     */
    public function getTriggers(): array
    {
        return (array)$this->triggers;
    }

    /**
     * @param array|null $triggers
     */
    public function setTriggers(array $triggers = null): void
    {
        $this->triggers = $triggers;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return (array)$this->config;
    }

    /**
     * @param array|null $config
     */
    public function setConfig(array $config = null): void
    {
        $this->config = $config;
    }
}
